<?php
/**
 * Recipe Model
 * Handles all recipe-related database operations
 * Matches the enterprise recipe application schema
 */

require_once __DIR__ . '/../config/database.php';

class Recipe {
    private PDO $db;

    // Valid ENUM values matching schema
    private const VALID_DIFFICULTIES = ['Easy', 'Medium', 'Hard'];
    private const VALID_VISIBILITIES = ['Public', 'Private', 'Draft'];
    private const VALID_MODERATION_STATUSES = ['Pending', 'Approved', 'Rejected'];
    private const VALID_RATING_DIMENSIONS = ['overall_rating', 'taste_rating', 'difficulty_rating', 'aesthetics_rating'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new recipe
     */
    public function create(int $authorId, array $data): array {
        try {
            // Validate required fields
            $required = ['title', 'slug'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "{$field} is required."];
                }
            }

            // Validate title length (VARCHAR(255))
            if (strlen($data['title']) > 255) {
                return ['success' => false, 'message' => 'Title must not exceed 255 characters.'];
            }

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            // Check slug uniqueness
            if (!$this->isSlugUnique($data['slug'])) {
                return ['success' => false, 'message' => 'A recipe with this slug already exists. Please modify the title.'];
            }

            // Validate difficulty
            $difficulty = $data['difficulty'] ?? 'Medium';
            if (!in_array($difficulty, self::VALID_DIFFICULTIES)) {
                return ['success' => false, 'message' => 'Invalid difficulty level.'];
            }

            // Validate visibility
            $visibility = $data['visibility'] ?? 'Public';
            if (!in_array($visibility, self::VALID_VISIBILITIES)) {
                return ['success' => false, 'message' => 'Invalid visibility setting.'];
            }

            // Validate numeric fields
            $prepTime = max(0, (int)($data['prep_time_minutes'] ?? 0));
            $cookTime = max(0, (int)($data['cook_time_minutes'] ?? 0));
            $servings = max(1, (int)($data['servings'] ?? 1));

            // Validate nutritional information if provided
            $nutritionFields = ['calories', 'protein_g', 'carbs_g', 'fats_g', 'fibre_g', 'sugar_g', 'sodium_mg'];
            foreach ($nutritionFields as $field) {
                if (isset($data[$field]) && $data[$field] !== '' && !is_numeric($data[$field])) {
                    return ['success' => false, 'message' => "Invalid value for {$field}."];
                }
            }

            // Insert recipe
            $stmt = $this->db->prepare("
                INSERT INTO recipes (
                    author_id, title, slug, description, image_url,
                    prep_time_minutes, cook_time_minutes, servings, difficulty,
                    cuisine_type, source_url, tips,
                    calories, protein_g, carbs_g, fats_g, fibre_g, sugar_g, sodium_mg,
                    visibility, moderation_status
                ) VALUES (
                    :author_id, :title, :slug, :description, :image_url,
                    :prep_time, :cook_time, :servings, :difficulty,
                    :cuisine_type, :source_url, :tips,
                    :calories, :protein, :carbs, :fats, :fibre, :sugar, :sodium,
                    :visibility, 'Approved'
                )
            ");

            $stmt->execute([
                ':author_id' => $authorId,
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':image_url' => $data['image_url'] ?? 'default-recipe.jpg',
                ':prep_time' => $prepTime,
                ':cook_time' => $cookTime,
                ':servings' => $servings,
                ':difficulty' => $difficulty,
                ':cuisine_type' => $data['cuisine_type'] ?? null,
                ':source_url' => $data['source_url'] ?? null,
                ':tips' => $data['tips'] ?? null,
                ':calories' => $data['calories'] ?? null,
                ':protein' => $data['protein_g'] ?? null,
                ':carbs' => $data['carbs_g'] ?? null,
                ':fats' => $data['fats_g'] ?? null,
                ':fibre' => $data['fibre_g'] ?? null,
                ':sugar' => $data['sugar_g'] ?? null,
                ':sodium' => $data['sodium_mg'] ?? null,
                ':visibility' => $visibility
            ]);

            $recipeId = $this->db->lastInsertId();

            // Insert categories if provided
            if (!empty($data['categories']) && is_array($data['categories'])) {
                $this->attachCategories($recipeId, $data['categories']);
            }

            // Insert ingredients if provided
            if (!empty($data['ingredients']) && is_array($data['ingredients'])) {
                $this->attachIngredients($recipeId, $data['ingredients']);
            }

            // Insert steps if provided
            if (!empty($data['steps']) && is_array($data['steps'])) {
                $this->attachSteps($recipeId, $data['steps']);
            }

            // Insert tags if provided
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $this->attachTags($recipeId, $data['tags']);
            }

            // Log audit
            $this->logAudit($authorId, 'Recipe', $recipeId, 'create', null, [
                'title' => $data['title'],
                'slug' => $data['slug']
            ]);

            return [
                'success' => true,
                'message' => 'Recipe created successfully.',
                'recipe_id' => $recipeId,
                'slug' => $data['slug']
            ];

        } catch (PDOException $e) {
            error_log("Recipe creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Recipe creation failed. Please try again.'];
        }
    }

    /**
     * Update an existing recipe
     */
    public function update(int $recipeId, int $userId, array $data): array {
        try {
            // Check if recipe exists and user has permission
            $existingRecipe = $this->getById($recipeId);
            if (!$existingRecipe) {
                return ['success' => false, 'message' => 'Recipe not found.'];
            }
            if ($existingRecipe['author_id'] !== $userId) {
                return ['success' => false, 'message' => 'You do not have permission to edit this recipe.'];
            }

            $allowedFields = [
                'title', 'description', 'image_url', 'prep_time_minutes', 'cook_time_minutes',
                'servings', 'difficulty', 'cuisine_type', 'source_url', 'tips',
                'calories', 'protein_g', 'carbs_g', 'fats_g', 'fibre_g', 'sugar_g', 'sodium_mg',
                'visibility', 'moderation_status'
            ];

            $updates = [];
            $params = [':id' => $recipeId];
            $oldValues = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    // Validate ENUM fields
                    if ($field === 'difficulty' && !in_array($data[$field], self::VALID_DIFFICULTIES)) {
                        return ['success' => false, 'message' => 'Invalid difficulty level.'];
                    }
                    if ($field === 'visibility' && !in_array($data[$field], self::VALID_VISIBILITIES)) {
                        return ['success' => false, 'message' => 'Invalid visibility setting.'];
                    }
                    if ($field === 'moderation_status' && !in_array($data[$field], self::VALID_MODERATION_STATUSES)) {
                        return ['success' => false, 'message' => 'Invalid moderation status.'];
                    }

                    // Validate numeric fields
                    if (in_array($field, ['prep_time_minutes', 'cook_time_minutes', 'servings']) && $data[$field] < 0) {
                        return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' cannot be negative.'];
                    }

                    // Update slug if title changes
                    if ($field === 'title' && $data['title'] !== $existingRecipe['title']) {
                        $newSlug = $this->generateSlug($data['title']);
                        if ($newSlug !== $existingRecipe['slug'] && !$this->isSlugUnique($newSlug, $recipeId)) {
                            $newSlug = $newSlug . '-' . $recipeId;
                        }
                        $updates[] = "slug = :slug";
                        $params[':slug'] = $newSlug;
                    }

                    $oldValues[$field] = $existingRecipe[$field] ?? null;
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update.'];
            }

            $sql = "UPDATE recipes SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Update categories if provided
            if (isset($data['categories']) && is_array($data['categories'])) {
                $this->detachAllCategories($recipeId);
                $this->attachCategories($recipeId, $data['categories']);
            }

            // Update ingredients if provided
            if (isset($data['ingredients']) && is_array($data['ingredients'])) {
                $this->detachAllIngredients($recipeId);
                $this->attachIngredients($recipeId, $data['ingredients']);
            }

            // Update steps if provided
            if (isset($data['steps']) && is_array($data['steps'])) {
                $this->detachAllSteps($recipeId);
                $this->attachSteps($recipeId, $data['steps']);
            }

            // Log audit
            $this->logAudit($userId, 'Recipe', $recipeId, 'update', $oldValues, $data);

            return ['success' => true, 'message' => 'Recipe updated successfully.'];

        } catch (PDOException $e) {
            error_log("Recipe update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Recipe update failed. Please try again.'];
        }
    }

    /**
     * Delete a recipe (soft delete by setting visibility to Private)
     */
    public function delete(int $recipeId, int $userId): array {
        try {
            // Check if recipe exists and user has permission
            $existingRecipe = $this->getById($recipeId);
            if (!$existingRecipe) {
                return ['success' => false, 'message' => 'Recipe not found.'];
            }
            if ($existingRecipe['author_id'] !== $userId) {
                return ['success' => false, 'message' => 'You do not have permission to delete this recipe.'];
            }

            // Soft delete by setting visibility to Private
            $stmt = $this->db->prepare("UPDATE recipes SET visibility = 'Private' WHERE id = :id");
            $stmt->execute([':id' => $recipeId]);

            // Log audit
            $this->logAudit($userId, 'Recipe', $recipeId, 'soft_delete', $existingRecipe, null);

            return ['success' => true, 'message' => 'Recipe deleted successfully.'];

        } catch (PDOException $e) {
            error_log("Recipe deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Recipe deletion failed.'];
        }
    }

    /**
     * Hard delete a recipe (admin function)
     */
    public function hardDelete(int $recipeId): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM recipes WHERE id = :id");
            $stmt->execute([':id' => $recipeId]);

            return ['success' => true, 'message' => 'Recipe permanently deleted.'];

        } catch (PDOException $e) {
            error_log("Recipe hard delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Recipe deletion failed.'];
        }
    }

    /**
     * Get single recipe with full details
     */
    public function getById(int $recipeId, bool $incrementView = false): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    r.*,
                    u.username as author_username,
                    u.first_name as author_first_name,
                    u.last_name as author_last_name,
                    u.profile_image_url as author_image,
                    GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as category_names,
                    GROUP_CONCAT(DISTINCT c.slug ORDER BY c.name SEPARATOR ', ') as category_slugs,
                    GROUP_CONCAT(DISTINCT c.id ORDER BY c.name SEPARATOR ',') as category_ids,
                    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as tag_names,
                    GROUP_CONCAT(DISTINCT t.slug ORDER BY t.name SEPARATOR ', ') as tag_slugs
                FROM recipes r
                LEFT JOIN users u ON r.author_id = u.id
                LEFT JOIN recipe_categories rc ON r.id = rc.recipe_id
                LEFT JOIN categories c ON rc.category_id = c.id
                LEFT JOIN recipe_tags rt ON r.id = rt.recipe_id
                LEFT JOIN tags t ON rt.tag_id = t.id
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
                SELECT ri.recipe_id, ri.quantity, ri.unit, ri.order_index, ri.notes,
                       i.id as ingredient_id, i.name as ingredient_name, 
                       i.default_unit, i.allergens
                FROM recipe_ingredients ri
                JOIN ingredients i ON ri.ingredient_id = i.id
                WHERE ri.recipe_id = :id
                ORDER BY ri.order_index ASC
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe['ingredients'] = $stmt->fetchAll();

            // Get steps
            $stmt = $this->db->prepare("
                SELECT id, step_number, instruction, duration_minutes, image_url, video_url
                FROM recipe_steps
                WHERE recipe_id = :id
                ORDER BY step_number ASC
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe['steps'] = $stmt->fetchAll();

            // Get media
            $stmt = $this->db->prepare("
                SELECT id, media_type, media_url, caption, is_primary, sort_order
                FROM recipe_media
                WHERE recipe_id = :id
                ORDER BY is_primary DESC, sort_order ASC
            ");
            $stmt->execute([':id' => $recipeId]);
            $recipe['media'] = $stmt->fetchAll();

            // Increment view count if requested
            if ($incrementView) {
                $stmt = $this->db->prepare("UPDATE recipes SET view_count = view_count + 1 WHERE id = :id");
                $stmt->execute([':id' => $recipeId]);
                $recipe['view_count'] = (int)$recipe['view_count'] + 1;
            }

            // Add computed fields
            $recipe['author_full_name'] = $recipe['author_first_name'] . ' ' . $recipe['author_last_name'];

            return $recipe;

        } catch (PDOException $e) {
            error_log("Get recipe error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recipe by slug
     */
    public function getBySlug(string $slug, bool $incrementView = false): ?array {
        $stmt = $this->db->prepare("SELECT id FROM recipes WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        $recipe = $stmt->fetch();

        if (!$recipe) {
            return null;
        }

        return $this->getById($recipe['id'], $incrementView);
    }

    /**
     * Search recipes with advanced filtering
     */
    public function search(array $filters = []): array {
        try {
            $conditions = [];
            $params = [];
            $useFulltextSearch = false;

            // Text search using FULLTEXT index
            if (!empty($filters['q'])) {
                $searchTerm = trim($filters['q']);
                $conditions[] = "MATCH(r.title, r.description) AGAINST(:search IN BOOLEAN MODE)";
                $params[':search'] = $searchTerm . '*';
                $useFulltextSearch = true;
            }

            // Filter by author
            if (!empty($filters['author_id'])) {
                $conditions[] = "r.author_id = :author_id";
                $params[':author_id'] = (int)$filters['author_id'];
            }

            // Filter by category
            if (!empty($filters['category'])) {
                $conditions[] = "rc_inner.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            // Filter by category slug
            if (!empty($filters['category_slug'])) {
                $conditions[] = "c_inner.slug = :category_slug";
                $params[':category_slug'] = $filters['category_slug'];
            }

            // Filter by tag
            if (!empty($filters['tag'])) {
                $conditions[] = "rt_inner.tag_id = :tag_id";
                $params[':tag_id'] = (int)$filters['tag'];
            }

            // Filter by difficulty
            if (!empty($filters['difficulty']) && in_array($filters['difficulty'], self::VALID_DIFFICULTIES)) {
                $conditions[] = "r.difficulty = :difficulty";
                $params[':difficulty'] = $filters['difficulty'];
            }

            // Filter by cuisine type
            if (!empty($filters['cuisine_type'])) {
                $conditions[] = "r.cuisine_type = :cuisine_type";
                $params[':cuisine_type'] = $filters['cuisine_type'];
            }

            // Filter by visibility
            if (!empty($filters['visibility'])) {
                $conditions[] = "r.visibility = :visibility";
                $params[':visibility'] = $filters['visibility'];
            } else {
                // Default to public recipes only
                $conditions[] = "r.visibility = 'Public'";
            }

            // Filter by moderation status
            if (!empty($filters['moderation_status'])) {
                $conditions[] = "r.moderation_status = :moderation_status";
                $params[':moderation_status'] = $filters['moderation_status'];
            } else {
                // Default to approved recipes only
                $conditions[] = "r.moderation_status = 'Approved'";
            }

            // Filter by max total time
            if (!empty($filters['max_time']) && is_numeric($filters['max_time'])) {
                $conditions[] = "r.total_time_minutes <= :max_time";
                $params[':max_time'] = (int)$filters['max_time'];
            }

            // Filter by max calories
            if (!empty($filters['max_calories']) && is_numeric($filters['max_calories'])) {
                $conditions[] = "(r.calories IS NULL OR r.calories <= :max_calories)";
                $params[':max_calories'] = (int)$filters['max_calories'];
            }

            // Filter by dietary preference
            if (!empty($filters['dietary'])) {
                switch ($filters['dietary']) {
                    case 'vegetarian':
                        $conditions[] = "r.id NOT IN (
                            SELECT recipe_id FROM recipe_categories 
                            WHERE category_id IN (SELECT id FROM categories WHERE slug IN ('meat', 'beef', 'pork', 'chicken', 'fish', 'seafood'))
                        )";
                        break;
                    case 'vegan':
                        $conditions[] = "r.id IN (
                            SELECT recipe_id FROM recipe_categories 
                            WHERE category_id IN (SELECT id FROM categories WHERE slug IN ('vegan'))
                        )";
                        break;
                    case 'non-vegetarian':
                        $conditions[] = "r.id IN (
                            SELECT recipe_id FROM recipe_categories 
                            WHERE category_id IN (SELECT id FROM categories WHERE slug IN ('meat', 'beef', 'pork', 'chicken', 'fish', 'seafood'))
                        )";
                        break;
                }
            }

            // Filter by minimum rating
            if (!empty($filters['min_rating']) && is_numeric($filters['min_rating'])) {
                $conditions[] = "r.average_rating >= :min_rating";
                $params[':min_rating'] = (float)$filters['min_rating'];
            }

            // Filter by featured
            if (isset($filters['is_featured'])) {
                $conditions[] = "r.is_featured = :is_featured";
                $params[':is_featured'] = (bool)$filters['is_featured'] ? 1 : 0;
            }

            // Build WHERE clause with proper JOINs for category/tag filtering
            $whereClause = '';
            $extraJoins = '';

            if (!empty($filters['category']) || !empty($filters['category_slug'])) {
                $extraJoins .= " INNER JOIN recipe_categories rc_inner ON r.id = rc_inner.recipe_id";
                if (!empty($filters['category_slug'])) {
                    $extraJoins .= " INNER JOIN categories c_inner ON rc_inner.category_id = c_inner.id";
                }
            }

            if (!empty($filters['tag'])) {
                $extraJoins .= " INNER JOIN recipe_tags rt_inner ON r.id = rt_inner.recipe_id";
            }

            if (!empty($conditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            }

            // Sorting
            $sortOption = $filters['sort'] ?? 'newest';
            $orderClause = $this->getOrderClause($sortOption, $useFulltextSearch);

            // Pagination
            $page = max(1, (int)($filters['page'] ?? 1));
            $limit = min(50, max(1, (int)($filters['limit'] ?? 12)));
            $offset = ($page - 1) * $limit;

            // Build query
            $selectFields = "
                r.id, r.title, r.slug, r.description, r.image_url,
                r.prep_time_minutes, r.cook_time_minutes, r.total_time_minutes,
                r.servings, r.difficulty, r.cuisine_type,
                r.average_rating, r.total_ratings, r.view_count,
                r.is_featured, r.created_at,
                u.username as author_username,
                u.first_name as author_first_name,
                u.last_name as author_last_name,
                u.role as author_role
            ";

            if ($useFulltextSearch) {
                $selectFields .= ", MATCH(r.title, r.description) AGAINST(:search_score IN BOOLEAN MODE) as relevance";
                $params[':search_score'] = $filters['q'] . '*';
            }

            $query = "
                SELECT $selectFields
                FROM recipes r
                INNER JOIN users u ON r.author_id = u.id
                $extraJoins
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
                INNER JOIN users u ON r.author_id = u.id
                $extraJoins
                $whereClause
            ";
            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                if (!in_array($key, [':search_score', ':limit', ':offset'])) {
                    $countStmt->bindValue($key, $value);
                }
            }
            $countStmt->execute();
            $total = $countStmt->fetchColumn();

            // Process results
            $processedRecipes = array_map(function($recipe) {
                $recipe['average_rating'] = round((float)$recipe['average_rating'], 1);
                $recipe['total_ratings'] = (int)$recipe['total_ratings'];
                $recipe['author_full_name'] = $recipe['author_first_name'] . ' ' . $recipe['author_last_name'];
                return $recipe;
            }, $recipes);

            return [
                'success' => true,
                'recipes' => $processedRecipes,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => max(1, ceil($total / $limit)),
                    'total_recipes' => (int)$total,
                    'per_page' => $limit,
                    'has_more' => ($page * $limit) < $total
                ],
                'filters_applied' => array_keys($filters)
            ];

        } catch (PDOException $e) {
            error_log("Recipe search error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Search failed. Please try again.'];
        }
    }

    /**
     * Get featured recipes
     */
    public function getFeatured(int $limit = 6): array {
        $limit = min(20, max(1, $limit));

        $stmt = $this->db->prepare("
            SELECT 
                r.id, r.title, r.slug, r.image_url, r.total_time_minutes,
                r.difficulty, r.servings, r.average_rating, r.total_ratings,
                r.cuisine_type, r.is_featured,
                u.username as author_username,
                u.first_name as author_first_name,
                u.last_name as author_last_name
            FROM recipes r
            INNER JOIN users u ON r.author_id = u.id
            WHERE r.visibility = 'Public' 
              AND r.moderation_status = 'Approved'
              AND r.is_featured = TRUE
            ORDER BY r.average_rating DESC, r.total_ratings DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $recipes = $stmt->fetchAll();

        return array_map(function($recipe) {
            $recipe['author_full_name'] = $recipe['author_first_name'] . ' ' . $recipe['author_last_name'];
            return $recipe;
        }, $recipes);
    }

    /**
     * Get latest recipes
     */
    public function getLatest(int $limit = 12): array {
        $limit = min(50, max(1, $limit));

        $stmt = $this->db->prepare("
            SELECT 
                r.id, r.title, r.slug, r.image_url, r.total_time_minutes,
                r.difficulty, r.servings, r.average_rating, r.total_ratings,
                r.created_at,
                u.username as author_username,
                u.first_name as author_first_name,
                u.last_name as author_last_name
            FROM recipes r
            INNER JOIN users u ON r.author_id = u.id
            WHERE r.visibility = 'Public' 
              AND r.moderation_status = 'Approved'
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function($recipe) {
            $recipe['author_full_name'] = $recipe['author_first_name'] . ' ' . $recipe['author_last_name'];
            return $recipe;
        }, $stmt->fetchAll());
    }

    /**
     * Get recipes by author
     */
    public function getByAuthor(int $authorId, int $page = 1, int $limit = 12): array {
        return $this->search([
            'author_id' => $authorId,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * Get related recipes based on categories
     */
    public function getRelated(int $recipeId, int $limit = 4): array {
        $stmt = $this->db->prepare("
            SELECT DISTINCT 
                r.id, r.title, r.slug, r.image_url, r.total_time_minutes,
                r.difficulty, r.average_rating, r.total_ratings,
                COUNT(rc1.category_id) as category_match_count
            FROM recipes r
            INNER JOIN recipe_categories rc1 ON r.id = rc1.recipe_id
            INNER JOIN recipe_categories rc2 ON rc1.category_id = rc2.category_id
            WHERE rc2.recipe_id = :recipe_id 
              AND r.id != :recipe_id2
              AND r.visibility = 'Public'
              AND r.moderation_status = 'Approved'
            GROUP BY r.id
            ORDER BY category_match_count DESC, r.average_rating DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':recipe_id2', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get trending recipes (most viewed)
     */
    public function getTrending(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT 
                r.id, r.title, r.slug, r.image_url, r.view_count,
                r.average_rating, r.total_ratings
            FROM recipes r
            WHERE r.visibility = 'Public' 
              AND r.moderation_status = 'Approved'
              AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY r.view_count DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get all categories
     */
    public function getCategories(): array {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(rc.recipe_id) as recipe_count
            FROM categories c
            LEFT JOIN recipe_categories rc ON c.id = rc.category_id
            GROUP BY c.id
            ORDER BY c.name
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get category by slug
     */
    public function getCategoryBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Attach categories to recipe
     */
    private function attachCategories(int $recipeId, array $categories): void {
        $stmt = $this->db->prepare("
            INSERT INTO recipe_categories (recipe_id, category_id) 
            VALUES (:recipe_id, :category_id)
        ");

        foreach ($categories as $categoryId) {
            $stmt->execute([
                ':recipe_id' => $recipeId,
                ':category_id' => (int)$categoryId
            ]);
        }
    }

    /**
     * Detach all categories from recipe
     */
    private function detachAllCategories(int $recipeId): void {
        $stmt = $this->db->prepare("DELETE FROM recipe_categories WHERE recipe_id = :id");
        $stmt->execute([':id' => $recipeId]);
    }

    /**
     * Attach ingredients to recipe
     */
    private function attachIngredients(int $recipeId, array $ingredients): void {
        foreach ($ingredients as $index => $ingredient) {
            // Check if ingredient exists or create it
            $ingredientId = $this->getOrCreateIngredient(
                $ingredient['name'] ?? '',
                $ingredient['default_unit'] ?? null
            );

            if ($ingredientId) {
                $stmt = $this->db->prepare("
                    INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit, order_index, notes)
                    VALUES (:recipe_id, :ingredient_id, :quantity, :unit, :order_index, :notes)
                ");
                $stmt->execute([
                    ':recipe_id' => $recipeId,
                    ':ingredient_id' => $ingredientId,
                    ':quantity' => $ingredient['quantity'] ?? null,
                    ':unit' => $ingredient['unit'] ?? null,
                    ':order_index' => $index,
                    ':notes' => $ingredient['notes'] ?? null
                ]);
            }
        }
    }

    /**
     * Get or create ingredient
     */
    private function getOrCreateIngredient(string $name, ?string $defaultUnit): ?int {
        // Try to find existing ingredient
        $stmt = $this->db->prepare("SELECT id FROM ingredients WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $existing = $stmt->fetch();

        if ($existing) {
            return (int)$existing['id'];
        }

        // Create new ingredient
        $stmt = $this->db->prepare("
            INSERT INTO ingredients (name, default_unit) 
            VALUES (:name, :unit)
        ");
        $stmt->execute([
            ':name' => $name,
            ':unit' => $defaultUnit
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Detach all ingredients from recipe
     */
    private function detachAllIngredients(int $recipeId): void {
        $stmt = $this->db->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = :id");
        $stmt->execute([':id' => $recipeId]);
    }

    /**
     * Attach steps to recipe
     */
    private function attachSteps(int $recipeId, array $steps): void {
        $stmt = $this->db->prepare("
            INSERT INTO recipe_steps (recipe_id, step_number, instruction, duration_minutes, image_url, video_url)
            VALUES (:recipe_id, :step_number, :instruction, :duration, :image_url, :video_url)
        ");

        foreach ($steps as $step) {
            $stmt->execute([
                ':recipe_id' => $recipeId,
                ':step_number' => $step['step_number'] ?? ($step['order'] ?? 1),
                ':instruction' => $step['instruction'] ?? '',
                ':duration' => $step['duration_minutes'] ?? null,
                ':image_url' => $step['image_url'] ?? null,
                ':video_url' => $step['video_url'] ?? null
            ]);
        }
    }

    /**
     * Detach all steps from recipe
     */
    private function detachAllSteps(int $recipeId): void {
        $stmt = $this->db->prepare("DELETE FROM recipe_steps WHERE recipe_id = :id");
        $stmt->execute([':id' => $recipeId]);
    }

    /**
     * Attach tags to recipe
     */
    private function attachTags(int $recipeId, array $tags): void {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO recipe_tags (recipe_id, tag_id) 
            VALUES (:recipe_id, :tag_id)
        ");

        foreach ($tags as $tagId) {
            $stmt->execute([
                ':recipe_id' => $recipeId,
                ':tag_id' => (int)$tagId
            ]);
        }
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(string $title): string {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Check if slug is unique
     */
    private function isSlugUnique(string $slug, ?int $excludeRecipeId = null): bool {
        $sql = "SELECT COUNT(*) FROM recipes WHERE slug = :slug";
        $params = [':slug' => $slug];

        if ($excludeRecipeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeRecipeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() === 0;
    }

    /**
     * Build ORDER BY clause
     */
    private function getOrderClause(string $sort, bool $useRelevance = false): string {
        if ($useRelevance) {
            return 'ORDER BY relevance DESC, r.average_rating DESC';
        }

        return match($sort) {
            'title_asc' => 'ORDER BY r.title ASC',
            'title_desc' => 'ORDER BY r.title DESC',
            'rating_desc' => 'ORDER BY r.average_rating DESC, r.total_ratings DESC',
            'rating_asc' => 'ORDER BY r.average_rating ASC',
            'time_asc' => 'ORDER BY r.total_time_minutes ASC',
            'time_desc' => 'ORDER BY r.total_time_minutes DESC',
            'difficulty_asc' => "ORDER BY FIELD(r.difficulty, 'Easy', 'Medium', 'Hard') ASC",
            'difficulty_desc' => "ORDER BY FIELD(r.difficulty, 'Hard', 'Medium', 'Easy') ASC",
            'views' => 'ORDER BY r.view_count DESC',
            'newest' => 'ORDER BY r.created_at DESC',
            'oldest' => 'ORDER BY r.created_at ASC',
            default => 'ORDER BY r.created_at DESC'
        };
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