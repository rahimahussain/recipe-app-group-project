<?php
/**
 * Favourite Model
 * Manages user recipe favourites
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
    public function toggle(int $userId, int $recipeId): array {
        try {
            // Check current status
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

                return ['success' => true, 'action' => 'removed', 'message' => 'Recipe removed from favourites.'];
            } else {
                // Add favourite
                $stmt = $this->db->prepare("
                    INSERT INTO favourites (user_id, recipe_id) VALUES (:user_id, :recipe_id)
                ");
                $stmt->execute([':user_id' => $userId, ':recipe_id' => $recipeId]);

                return ['success' => true, 'action' => 'added', 'message' => 'Recipe added to favourites.'];
            }

        } catch (PDOException $e) {
            error_log("Toggle favourite error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Operation failed.'];
        }
    }

    /**
     * Get user's favourite recipes
     */
    public function getUserFavourites(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                r.id,
                r.title,
                r.description,
                r.image_url,
                r.total_time,
                r.difficulty,
                COALESCE(AVG(rt.overall_rating), 0) as avg_rating,
                f.saved_at
            FROM favourites f
            JOIN recipes r ON f.recipe_id = r.id
            LEFT JOIN ratings rt ON r.id = rt.recipe_id
            WHERE f.user_id = :user_id
            GROUP BY r.id, f.saved_at
            ORDER BY f.saved_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
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
}