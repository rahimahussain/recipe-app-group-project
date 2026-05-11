<?php
/**
 * POST /api/ratings.php - Submit a rating
 * GET /api/ratings.php?recipe_id=123 - Get ratings for recipe
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Rating.php';

try {
    $ratingModel = new Rating();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get ratings for a recipe
        $recipeId = filter_input(INPUT_GET, 'recipe_id', FILTER_VALIDATE_INT);

        if (!$recipeId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Recipe ID required.']);
            exit;
        }

        $ratings = $ratingModel->getRecipeRatings($recipeId);
        echo json_encode(['success' => true, 'ratings' => $ratings]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Submit rating (requires auth)
        $user = requireAuth();

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $result = $ratingModel->submitRating($user['user_id'], $input);

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed.']);
}