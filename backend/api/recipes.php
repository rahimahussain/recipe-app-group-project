<?php
/**
 * GET /api/recipes.php - List/search recipes
 * POST /api/recipes.php - Create new recipe
 * Matches enterprise schema
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../models/Favourite.php';

try {
    $recipeModel = new Recipe();

    // GET - List/search recipes
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if featured or latest requested
        if (isset($_GET['featured'])) {
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 6)));
            $recipes = $recipeModel->getFeatured($limit);

            echo json_encode([
                'success' => true,
                'recipes' => $recipes,
                'count' => count($recipes)
            ]);
            exit;
        }

        if (isset($_GET['latest'])) {
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
            $recipes = $recipeModel->getLatest($limit);

            echo json_encode([
                'success' => true,
                'recipes' => $recipes,
                'count' => count($recipes)
            ]);
            exit;
        }

        if (isset($_GET['trending'])) {
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
            $recipes = $recipeModel->getTrending($limit);

            echo json_encode([
                'success' => true,
                'recipes' => $recipes,
                'count' => count($recipes)
            ]);
            exit;
        }

        // Regular search with filters
        $filters = [
            'q' => $_GET['q'] ?? '',
            'author_id' => $_GET['author_id'] ?? '',
            'category' => $_GET['category'] ?? '',
            'category_slug' => $_GET['category_slug'] ?? '',
            'tag' => $_GET['tag'] ?? '',
            'difficulty' => $_GET['difficulty'] ?? '',
            'cuisine_type' => $_GET['cuisine_type'] ?? '',
            'max_time' => $_GET['max_time'] ?? '',
            'max_calories' => $_GET['max_calories'] ?? '',
            'dietary' => $_GET['dietary'] ?? '',
            'min_rating' => $_GET['min_rating'] ?? '',
            'is_featured' => $_GET['is_featured'] ?? null,
            'visibility' => $_GET['visibility'] ?? null,
            'moderation_status' => $_GET['moderation_status'] ?? null,
            'sort' => $_GET['sort'] ?? 'newest',
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? 12
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        $result = $recipeModel->search($filters);

        // Check favourites if user is logged in
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id']) && !empty($result['recipes'])) {
            $favouriteModel = new Favourite();
            foreach ($result['recipes'] as &$recipe) {
                $recipe['is_favourite'] = $favouriteModel->isFavourite(
                    $_SESSION['user_id'],
                    $recipe['id']
                );
            }
        }

        echo json_encode($result);
    }

    // POST - Create new recipe
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/../middleware/auth.php';
        $user = requireAuth();

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $result = $recipeModel->create($user['user_id'], $input);

        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Recipes endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to process request.', 'code' => 'SERVER_ERROR']);
}