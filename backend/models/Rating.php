<?php
/**
 * Rating Model
 * Manages recipe ratings with multiple dimensions
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

            // Optional ratings
            $taste = $this->validateOptionalRating($data['taste_rating'] ?? null);
            $difficulty = $this->validateOptionalRating($data['difficulty_rating'] ?? null);
            $aesthetics = $this->validateOptionalRating($data['aesthetics_rating'] ?? null);
            $comment = trim($data['comment'] ?? '');

            // Upsert rating
            $stmt = $this->db->prepare("
                INSERT INTO ratings 
                    (user_id, recipe_id, overall_rating, difficulty_rating, taste_rating, aesthetics_rating, comment)
                VALUES 
                    (:user_id, :recipe_id, :overall, :difficulty, :taste, :aesthetics, :comment)
                ON DUPLICATE KEY UPDATE
                    overall_rating = VALUES(overall_rating),
                    difficulty_rating = VALUES(difficulty_rating),
                    taste_rating = VALUES(taste_rating),
                    aesthetics_rating = VALUES(aesthetics_rating),
                    comment = VALUES(comment),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':recipe_id' => $recipeId,
                ':overall' => $overall,
                ':difficulty' => $difficulty,
                ':taste' => $taste,
                ':aesthetics' => $aesthetics,
                ':comment' => $comment
            ]);

            return ['success' => true, 'message' => 'Rating submitted successfully.'];

        } catch (PDOException $e) {
            error_log("Rating submission error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit rating.'];
        }
    }

    /**
     * Get ratings for a recipe
     */
    public function getRecipeRatings(int $recipeId, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT 
                r.id,
                r.overall_rating,
                r.difficulty_rating,
                r.taste_rating,
                r.aesthetics_rating,
                r.comment,
                r.created_at,
                u.username,
                u.full_name
            FROM ratings r
            JOIN users u ON r.user_id = u.id
            WHERE r.recipe_id = :recipe_id AND r.comment IS NOT NULL AND r.comment != ''
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get user's rating for a specific recipe
     */
    public function getUserRating(int $userId, int $recipeId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM ratings WHERE user_id = :user_id AND recipe_id = :recipe_id
        ");
        $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all ratings by a user
     */
    public function getUserRatings(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                r.id,
                r.overall_rating,
                r.difficulty_rating,
                r.taste_rating,
                r.aesthetics_rating,
                r.comment,
                r.created_at,
                rec.title as recipe_title,
                rec.id as recipe_id
            FROM ratings r
            JOIN recipes rec ON r.recipe_id = rec.id
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
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
}
