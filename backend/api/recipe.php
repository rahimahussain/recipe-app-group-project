<?php
/**
 * GET /api/recipe.php?id=123
 * Get single recipe details
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
require_once __DIR__ . '/../models/Rating.php';
require_once __DIR__ . '/../models/Favourite.php';

try {
    $recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$recipeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid recipe ID.']);
        exit;
    }

    $recipeModel = new Recipe();
    $recipe = $recipeModel->getById($recipeId);

    if (!$recipe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Recipe not found.']);
        exit;
    }

    // Get ratings
    $ratingModel = new Rating();
    $recipe['reviews'] = $ratingModel->getRecipeRatings($recipeId);

    // Check if user is logged in
    session_start();
    if (isset($_SESSION['user_id'])) {
        $favouriteModel = new Favourite();
        $recipe['is_favourite'] = $favouriteModel->isFavourite($_SESSION['user_id'], $recipeId);
        $recipe['user_rating'] = $ratingModel->getUserRating($_SESSION['user_id'], $recipeId);
    }

    echo json_encode([
        'success' => true,
        'recipe' => $recipe
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch recipe.']);
}