<?php
/**
 * GET /api/recipes.php
 * Search and list recipes with filtering
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../models/Favourite.php';

try {
    $recipeModel = new Recipe();

    // Get featured recipes if no search parameters
    if (empty($_GET)) {
        $recipes = $recipeModel->getFeatured(6);
        echo json_encode([
            'success' => true,
            'recipes' => $recipes
        ]);
        exit;
    }

    // Search with filters
    $filters = [
        'q' => $_GET['q'] ?? '',
        'category' => $_GET['category'] ?? '',
        'difficulty' => $_GET['difficulty'] ?? '',
        'dietary' => $_GET['dietary'] ?? '',
        'max_time' => $_GET['max_time'] ?? '',
        'sort' => $_GET['sort'] ?? 'title_asc',
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 12
    ];

    $result = $recipeModel->search($filters);

    // Check favourites if user is logged in
    session_start();
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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch recipes.']);
}