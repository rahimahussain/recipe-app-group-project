<?php
/**
 * Rating Model
 * Manages recipe ratings with multiple dimensions
 * Matches the enterprise recipe application schema
 */

require_once __DIR__ . '/../config/database.php';

class Rating {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Submit or update a rating
     */
    public function submitRating(int $userId, array $data): array {
        try {
            // Validate overall rating (required)
            $overall = (int)($data['overall_rating'] ?? 0);
            if ($overall < 1 || $overall > 5) {
                return ['success' => false, 'message' => 'Overall rating must be between 1 and 5.'];
            }

            $recipeId = (int)($data['recipe_id'] ?? 0);
            if ($recipeId <= 0) {
                return ['success' => false, 'message' => 'Invalid recipe ID.'];
            }

            // Verify recipe exists
            $stmt = $this->db->prepare("SELECT id, author_id FROM recipes WHERE id = :id");
            $stmt->execute([':id' => $recipeId]);
            $recipe = $stmt->fetch();

            if (!$recipe) {
                return ['success' => false, 'message' => 'Recipe not found.'];
            }

            // Prevent self-rating
            if ($recipe['author_id'] === $userId) {
                return ['success' => false, 'message' => 'You cannot rate your own recipe.'];
            }

            // Validate optional ratings
            $taste = $this->validateOptionalRating($data['taste_rating'] ?? null);
            $difficulty = $this->validateOptionalRating($data['difficulty_rating'] ?? null);
            $aesthetics = $this->validateOptionalRating($data['aesthetics_rating'] ?? null);
            $comment = trim($data['comment'] ?? '');

            // Check if this is an update
            $isUpdate = false;
            $oldRating = null;

            $stmt = $this->db->prepare("
                SELECT * FROM ratings WHERE user_id = :user_id AND recipe_id = :recipe_id
            ");
            $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);
            if ($exists = $stmt->fetch()) {
                $isUpdate = true;
                $oldRating = $exists;
            }

            // Upsert rating
            $stmt = $this->db->prepare("
                INSERT INTO ratings 
                    (user_id, recipe_id, overall_rating, difficulty_rating, taste_rating, aesthetics_rating, comment, is_edited)
                VALUES 
                    (:user_id, :recipe_id, :overall, :difficulty, :taste, :aesthetics, :comment, :is_edited)
                ON DUPLICATE KEY UPDATE
                    overall_rating = VALUES(overall_rating),
                    difficulty_rating = VALUES(difficulty_rating),
                    taste_rating = VALUES(taste_rating),
                    aesthetics_rating = VALUES(aesthetics_rating),
                    comment = VALUES(comment),
                    is_edited = TRUE,
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':recipe_id' => $recipeId,
                ':overall' => $overall,
                ':difficulty' => $difficulty,
                ':taste' => $taste,
                ':aesthetics' => $aesthetics,
                ':comment' => $comment,
                ':is_edited' => $isUpdate ? 1 : 0
            ]);

            // Update recipe's aggregate ratings
            $this->updateRecipeAggregateRating($recipeId);

            // Log audit
            $this->logAudit($userId, 'Rating', $recipeId, $isUpdate ? 'update' : 'create', $oldRating, $data);

            // Create notification for recipe author
            if ($recipe['author_id'] !== $userId) {
                $this->createNotification(
                    $recipe['author_id'],
                    $isUpdate ? 'rating_updated' : 'new_rating',
                    $isUpdate ? 'Rating Updated' : 'New Rating',
                    "Someone " . ($isUpdate ? "updated their" : "left a") . " rating on your recipe!"
                );
            }

            return [
                'success' => true,
                'message' => $isUpdate ? 'Rating updated successfully.' : 'Rating submitted successfully.',
                'action' => $isUpdate ? 'updated' : 'created'
            ];

        } catch (PDOException $e) {
            error_log("Rating submission error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit rating. Please try again.'];
        }
    }

    /**
     * Get ratings for a recipe with pagination
     */
    public function getRecipeRatings(int $recipeId, int $page = 1, int $limit = 10, string $sortBy = 'newest'): array {
        try {
            $offset = ($page - 1) * $limit;

            $orderClause = match($sortBy) {
                'highest' => 'ORDER BY r.overall_rating DESC',
                'lowest' => 'ORDER BY r.overall_rating ASC',
                'oldest' => 'ORDER BY r.created_at ASC',
                default => 'ORDER BY r.created_at DESC'
            };

            // Get ratings
            $stmt = $this->db->prepare("
                SELECT 
                    r.id, r.overall_rating, r.difficulty_rating, r.taste_rating, 
                    r.aesthetics_rating, r.comment, r.is_edited,
                    r.moderation_status, r.created_at, r.updated_at,
                    u.username, u.first_name, u.last_name, u.profile_image_url,
                    u.role as user_role
                FROM ratings r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.recipe_id = :recipe_id 
                  AND r.moderation_status = 'Visible'
                $orderClause
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $ratings = $stmt->fetchAll();

            // Get total count
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM ratings 
                WHERE recipe_id = :recipe_id AND moderation_status = 'Visible'
            ");
            $countStmt->execute([':recipe_id' => $recipeId]);
            $total = $countStmt->fetchColumn();

            // Process ratings
            $processedRatings = array_map(function($rating) {
                $rating['user_full_name'] = $rating['first_name'] . ' ' . $rating['last_name'];
                return $rating;
            }, $ratings);

            return [
                'success' => true,
                'ratings' => $processedRatings,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => max(1, ceil($total / $limit)),
                    'total_ratings' => (int)$total,
                    'per_page' => $limit
                ]
            ];

        } catch (PDOException $e) {
            error_log("Get ratings error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load ratings.'];
        }
    }

    /**
     * Get rating summary for a recipe
     */
    public function getRecipeRatingSummary(int $recipeId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_ratings,
                COALESCE(AVG(overall_rating), 0) as avg_overall,
                COALESCE(AVG(difficulty_rating), 0) as avg_difficulty,
                COALESCE(AVG(taste_rating), 0) as avg_taste,
                COALESCE(AVG(aesthetics_rating), 0) as avg_aesthetics,
                COUNT(CASE WHEN overall_rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN overall_rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN overall_rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN overall_rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN overall_rating = 1 THEN 1 END) as one_star
            FROM ratings 
            WHERE recipe_id = :recipe_id AND moderation_status = 'Visible'
        ");
        $stmt->execute([':recipe_id' => $recipeId]);
        $summary = $stmt->fetch();

        return array_map(function($value) {
            return is_numeric($value) ? round((float)$value, 1) : $value;
        }, $summary);
    }

    /**
     * Get user's rating for a specific recipe
     */
    public function getUserRating(int $userId, int $recipeId): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                id, overall_rating, difficulty_rating, taste_rating, 
                aesthetics_rating, comment, is_edited,
                moderation_status, created_at, updated_at
            FROM ratings 
            WHERE user_id = :user_id AND recipe_id = :recipe_id
        ");
        $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all ratings by a user
     */
    public function getUserRatings(int $userId, int $page = 1, int $limit = 20): array {
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare("
            SELECT 
                r.id, r.overall_rating, r.difficulty_rating, r.taste_rating,
                r.aesthetics_rating, r.comment, r.is_edited,
                r.moderation_status, r.created_at, r.updated_at,
                rec.id as recipe_id, rec.title as recipe_title, rec.slug as recipe_slug,
                rec.image_url as recipe_image
            FROM ratings r
            INNER JOIN recipes rec ON r.recipe_id = rec.id
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Delete a rating
     */
    public function deleteRating(int $userId, int $ratingId): array {
        try {
            // Verify ownership
            $stmt = $this->db->prepare("
                SELECT r.*, rec.id as recipe_id 
                FROM ratings r
                INNER JOIN recipes rec ON r.recipe_id = rec.id
                WHERE r.id = :id AND r.user_id = :user_id
            ");
            $stmt->execute([':id' => $ratingId, ':user_id' => $userId]);
            $rating = $stmt->fetch();

            if (!$rating) {
                return ['success' => false, 'message' => 'Rating not found or access denied.'];
            }

            // Delete rating
            $stmt = $this->db->prepare("DELETE FROM ratings WHERE id = :id");
            $stmt->execute([':id' => $ratingId]);

            // Update recipe aggregate
            $this->updateRecipeAggregateRating($rating['recipe_id']);

            // Log audit
            $this->logAudit($userId, 'Rating', $ratingId, 'delete', $rating, null);

            return ['success' => true, 'message' => 'Rating deleted successfully.'];

        } catch (PDOException $e) {
            error_log("Delete rating error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete rating.'];
        }
    }

    /**
     * Moderate a rating (admin function)
     */
    public function moderateRating(int $ratingId, string $newStatus): array {
        if (!in_array($newStatus, ['Visible', 'Hidden', 'Flagged'])) {
            return ['success' => false, 'message' => 'Invalid moderation status.'];
        }

        $stmt = $this->db->prepare("
            UPDATE ratings SET moderation_status = :status, updated_at = NOW() 
            WHERE id = :id
        ");
        $stmt->execute([':status' => $newStatus, ':id' => $ratingId]);

        return ['success' => true, 'message' => 'Rating moderated successfully.'];
    }

    /**
     * Update recipe aggregate rating (denormalized for performance)
     */
    private function updateRecipeAggregateRating(int $recipeId): void {
        $summary = $this->getRecipeRatingSummary($recipeId);

        $stmt = $this->db->prepare("
            UPDATE recipes SET 
                average_rating = :avg,
                total_ratings = :total
            WHERE id = :id
        ");
        $stmt->execute([
            ':avg' => $summary['avg_overall'],
            ':total' => $summary['total_ratings'],
            ':id' => $recipeId
        ]);
    }

    /**
     * Validate optional rating value
     */
    private function validateOptionalRating($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }
        $intValue = (int)$value;
        return ($intValue >= 1 && $intValue <= 5) ? $intValue : null;
    }

    /**
     * Create notification
     */
    private function createNotification(int $userId, string $type, string $title, string $message): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, notification_type, title, message) 
                VALUES (:user_id, :type, :title, :message)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message
            ]);
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
        }
    }

    /**
     * Log audit entry
     */
    private function logAudit(?int $userId, string $entityName, int $entityId, string $actionType, $oldValue, $newValue): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, entity_name, entity_id, action_type, old_value, new_value, ip_address, user_agent) 
                VALUES (:user_id, :entity, :entity_id, :action, :old_val, :new_val, :ip, :agent)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':entity' => $entityName,
                ':entity_id' => $entityId,
                ':action' => $actionType,
                ':old_val' => $oldValue ? json_encode($oldValue) : null,
                ':new_val' => $newValue ? json_encode($newValue) : null,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
}