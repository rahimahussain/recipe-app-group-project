<?php
/**
 * Recipe Model
 * Handles recipe-related database operations
 */

require_once __DIR__ . '/../config/database.php';

class Recipe {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Search recipes with flexible filtering and sorting
     */
    public function search(array $filters = []): array {
        try {
            $conditions = [];
            $params = [];

            // Text search
            if (!empty($filters['q'])) {
                $conditions[] = "MATCH(r.title, r.description) AGAINST(:search IN BOOLEAN MODE)";
                $params[':search'] = $filters['q'] . '*';
            }

            // Category filter
            if (!empty($filters['category'])) {
                $conditions[] = "c.slug = :category";
                $params[':category'] = $filters['category'];
            }

            // Difficulty filter
            if (!empty($filters['difficulty'])) {
                $conditions[] = "r.difficulty = :difficulty";
                $params[':difficulty'] = $filters['difficulty'];
            }

            // Max time filter
            if (!empty($filters['max_time']) && is_numeric($filters['max_time'])) {
                $conditions[] = "r.total_time <= :max_time";
                $params[':max_time'] = (int)$filters['max_time'];
            }

            // Dietary filter
            if (!empty($filters['dietary'])) {
                if ($filters['dietary'] === 'vegetarian') {
                    $conditions[] = "r.id NOT IN (
                        SELECT recipe_id FROM recipe_categories 
                        WHERE category_id IN (SELECT id FROM categories WHERE slug = 'meat')
                    )";
                } elseif ($filters['dietary'] === 'vegan') {
                    $conditions[] = "r.id NOT IN (
                        SELECT recipe_id FROM recipe_categories 
                        WHERE category_id IN (
                            SELECT id FROM categories WHERE slug IN ('meat', 'vegetarian')
                        )
                    )";
                }
            }

            $whereClause = '';
            if (!empty($conditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            }

            // Sorting
            $orderClause = $this->getOrderClause($filters['sort'] ?? 'title_asc');

            // Pagination
            $page = max(1, (int)($filters['page'] ?? 1));
            $limit = min(50, max(1, (int)($filters['limit'] ?? 12)));
            $offset = ($page - 1) * $limit;

            // Main query
            $query = "
                SELECT 
                    r.id,
                    r.title,
                    r.description,
                    r.image_url,
                    r.prep_time,
                    r.cook_time,
                    r.total_time,
                    r.servings,
                    r.difficulty,
                    r.chef_name,
                    COALESCE(AVG(rt.overall_rating), 0) as avg_rating,
                    COUNT(DISTINCT rt.id) as rating_count,
                    GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as categories
                FROM recipes r
                LEFT JOIN ratings rt ON r.id = rt.recipe_id
                LEFT JOIN recipe_categories rc ON r.id = rc.recipe_id
                LEFT JOIN categories c ON rc.category_id = c.id
                $whereClause
                GROUP BY r.id
                $orderClause
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $recipes = $stmt->fetchAll();

            // Get total count
            $countQuery = "
                SELECT COUNT(DISTINCT r.id) 
                FROM recipes r
                LEFT JOIN recipe_categories rc ON r.id = rc.recipe_id
                LEFT JOIN categories c ON rc.category_id = c.id
                $whereClause
            ";
            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetchColumn();

            return [
                'success' => true,
                'recipes' => $recipes,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_recipes' => (int)$total,
                    'per_page' => $limit
                ]
            ];

        } catch (PDOException $e) {
            error_log("Recipe search error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Search failed.'];
        }
    }

    /**
     * Get single recipe with full details
     */
    public function getById(int $recipeId): ?array {
        try {
            // Get main recipe data
            $stmt = $this->db->prepare("
                SELECT 
                    r.*,
                    COALESCE(AVG(rt.overall_rating), 0) as avg_overall,
                    COALESCE(AVG(rt.difficulty_rating), 0) as avg_difficulty,
                    COALESCE(AVG(rt.taste_rating), 0) as avg_taste,
                    COALESCE(AVG(rt.aesthetics_rating), 0) as avg_aesthetics,
                    COUNT(DISTINCT rt.id) as rating_count,
                    GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as category_names,
                    GROUP_CONCAT(DISTINCT c.slug ORDER BY c.name SEPARATOR ', ') as category_slugs
                FROM recipes r
                LEFT JOIN ratings rt ON r.id = rt.recipe_id
                LEFT JOIN recipe_categories rc ON r.id = rc.recipe_id
                LEFT JOIN categories c ON rc.category_id = c.id
                WHERE r.id = :id
                GROUP BY r.id
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe = $stmt->fetch();

            if (!$recipe) {
                return null;
            }

            // Get ingredients
            $stmt = $this->db->prepare("
                SELECT id, name, quantity, unit, order_index 
                FROM ingredients 
                WHERE recipe_id = :id 
                ORDER BY order_index
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe['ingredients'] = $stmt->fetchAll();

            // Get steps
            $stmt = $this->db->prepare("
                SELECT id, step_number, instruction, duration_minutes 
                FROM steps 
                WHERE recipe_id = :id 
                ORDER BY step_number
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe['steps'] = $stmt->fetchAll();

            return $recipe;

        } catch (PDOException $e) {
            error_log("Get recipe error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get featured/recent recipes
     */
    public function getFeatured(int $limit = 6): array {
        $stmt = $this->db->prepare("
            SELECT 
                r.id, r.title, r.description, r.image_url, 
                r.total_time, r.difficulty,
                COALESCE(AVG(rt.overall_rating), 0) as avg_rating,
                COUNT(DISTINCT rt.id) as rating_count
            FROM recipes r
            LEFT JOIN ratings rt ON r.id = rt.recipe_id
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Build ORDER BY clause from sort parameter
     */
    private function getOrderClause(string $sort): string {
        return match($sort) {
            'title_desc' => 'ORDER BY r.title DESC',
            'rating_desc' => 'ORDER BY avg_rating DESC, rating_count DESC',
            'rating_asc' => 'ORDER BY avg_rating ASC',
            'time_asc' => 'ORDER BY r.total_time ASC',
            'time_desc' => 'ORDER BY r.total_time DESC',
            'difficulty_asc' => "ORDER BY FIELD(r.difficulty, 'Easy', 'Medium', 'Hard') ASC",
            'difficulty_desc' => "ORDER BY FIELD(r.difficulty, 'Hard', 'Medium', 'Easy') ASC",
            'newest' => 'ORDER BY r.created_at DESC',
            default => 'ORDER BY r.title ASC'
        };
    }
}