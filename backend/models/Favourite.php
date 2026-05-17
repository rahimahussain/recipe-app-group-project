<?php
/**
 * Favourite Model
 * Manages user recipe favourites and bookmark collections
 * Matches the enterprise recipe application schema
 */

require_once __DIR__ . '/../config/database.php';

class Favourite {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Toggle favourite status (add/remove)
     */
    public function toggle(int $userId, int $recipeId, string $folderName = null): array {
        try {
            // Verify recipe exists and is public
            $stmt = $this->db->prepare("
                SELECT id, visibility FROM recipes WHERE id = :id
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe = $stmt->fetch();

            if (!$recipe) {
                return ['success' => false, 'message' => 'Recipe not found.'];
            }

            // Check current favourite status
            $stmt = $this->db->prepare("
                SELECT 1 FROM favourites WHERE user_id = :user_id AND recipe_id = :recipe_id
            ");
            $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);

            if ($stmt->fetch()) {
                // Remove favourite
                $stmt = $this->db->prepare("
                    DELETE FROM favourites WHERE user_id = :user_id AND recipe_id = :recipe_id
                ");
                $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);

                // Log audit
                $this->logAudit($userId, 'Favourite', $recipeId, 'remove', null, null);

                return [
                    'success' => true,
                    'action' => 'removed',
                    'message' => 'Recipe removed from favourites.'
                ];
            } else {
                // Add favourite
                $stmt = $this->db->prepare("
                    INSERT INTO favourites (user_id, recipe_id, folder_name) 
                    VALUES (:user_id, :recipe_id, :folder_name)
                ");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':recipe_id' => $recipeId,
                    ':folder_name' => $folderName
                ]);

                // Log audit
                $this->logAudit($userId, 'Favourite', $recipeId, 'add', null, [
                    'folder' => $folderName
                ]);

                // Create notification for recipe author if different from favouriting user
                if ($recipe['author_id'] !== $userId) {
                    $this->createNotification(
                        $recipe['author_id'],
                        'recipe_favourited',
                        'Recipe Favourited',
                        "Someone added your recipe to their favourites!"
                    );
                }

                return [
                    'success' => true,
                    'action' => 'added',
                    'message' => 'Recipe added to favourites.'
                ];
            }

        } catch (PDOException $e) {
            error_log("Toggle favourite error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Operation failed. Please try again.'];
        }
    }

    /**
     * Get user's favourite recipes with pagination
     */
    public function getUserFavourites(int $userId, int $page = 1, int $limit = 12, string $folderName = null): array {
        try {
            $offset = ($page - 1) * $limit;
            $folderCondition = '';
            $params = [':user_id' => $userId];

            if ($folderName !== null) {
                $folderCondition = "AND f.folder_name = :folder_name";
                $params[':folder_name'] = $folderName;
            }

            // Get recipes
            $stmt = $this->db->prepare("
                SELECT 
                    r.id, r.title, r.slug, r.image_url, r.description,
                    r.total_time_minutes, r.difficulty, r.servings,
                    r.average_rating, r.total_ratings, r.cuisine_type,
                    f.folder_name, f.saved_at,
                    u.username as author_username,
                    u.first_name as author_first_name,
                    u.last_name as author_last_name
                FROM favourites f
                INNER JOIN recipes r ON f.recipe_id = r.id
                INNER JOIN users u ON r.author_id = u.id
                WHERE f.user_id = :user_id
                $folderCondition
                ORDER BY f.saved_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $favourites = $stmt->fetchAll();

            // Get total count
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM favourites f WHERE f.user_id = :user_id $folderCondition
            ");
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetchColumn();

            // Process results
            $processedFavourites = array_map(function($fav) {
                $fav['author_full_name'] = $fav['author_first_name'] . ' ' . $fav['author_last_name'];
                return $fav;
            }, $favourites);

            return [
                'success' => true,
                'favourites' => $processedFavourites,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => max(1, ceil($total / $limit)),
                    'total_favourites' => (int)$total,
                    'per_page' => $limit
                ]
            ];

        } catch (PDOException $e) {
            error_log("Get favourites error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load favourites.'];
        }
    }

    /**
     * Check if recipe is favourited by user
     */
    public function isFavourite(int $userId, int $recipeId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM favourites WHERE user_id = :user_id AND recipe_id = :recipe_id
        ");
        $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Get favourite folders for a user
     */
    public function getUserFolders(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                folder_name,
                COUNT(*) as recipe_count,
                MAX(saved_at) as last_saved
            FROM favourites 
            WHERE user_id = :user_id
            GROUP BY folder_name
            ORDER BY folder_name
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Move favourite to different folder
     */
    public function moveToFolder(int $userId, int $recipeId, string $newFolder): array {
        $stmt = $this->db->prepare("
            UPDATE favourites SET folder_name = :folder_name 
            WHERE user_id = :user_id AND recipe_id = :recipe_id
        ");
        $stmt->execute([
            ':folder_name' => $newFolder,
            ':user_id' => $userId,
            ':recipe_id' => $recipeId
        ]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Recipe moved to folder.'];
        }

        return ['success' => false, 'message' => 'Favourite not found.'];
    }

    /**
     * Get count of favourites for a recipe
     */
    public function getRecipeFavouriteCount(int $recipeId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM favourites WHERE recipe_id = :recipe_id");
        $stmt->execute([':recipe_id' => $recipeId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a bookmark collection
     */
    public function createCollection(int $userId, string $name, string $description = null): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO bookmark_collections (user_id, collection_name, description) 
                VALUES (:user_id, :name, :description)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => $name,
                ':description' => $description
            ]);

            return [
                'success' => true,
                'collection_id' => $this->db->lastInsertId(),
                'message' => 'Collection created.'
            ];

        } catch (PDOException $e) {
            error_log("Create collection error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create collection.'];
        }
    }

    /**
     * Get user's bookmark collections
     */
    public function getUserCollections(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                bc.*,
                COUNT(cr.recipe_id) as recipe_count
            FROM bookmark_collections bc
            LEFT JOIN collection_recipes cr ON bc.id = cr.collection_id
            WHERE bc.user_id = :user_id
            GROUP BY bc.id
            ORDER BY bc.collection_name
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Add recipe to collection
     */
    public function addToCollection(int $collectionId, int $recipeId, int $userId): array {
        try {
            // Verify collection ownership
            $stmt = $this->db->prepare("SELECT id FROM bookmark_collections WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $collectionId, ':user_id' => $userId]);

            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Collection not found or access denied.'];
            }

            // Add to collection
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO collection_recipes (collection_id, recipe_id) 
                VALUES (:collection_id, :recipe_id)
            ");
            $stmt->execute([
                ':collection_id' => $collectionId,
                ':recipe_id' => $recipeId
            ]);

            return ['success' => true, 'message' => 'Recipe added to collection.'];

        } catch (PDOException $e) {
            error_log("Add to collection error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add recipe to collection.'];
        }
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