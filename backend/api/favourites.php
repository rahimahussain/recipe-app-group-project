<?php
/**
 * GET /api/favourites.php - List user's favourites
 * POST /api/favourites.php - Toggle favourite
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
require_once __DIR__ . '/../models/Favourite.php';

// Require authentication
$user = requireAuth();

try {
    $favouriteModel = new Favourite();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // List favourites
        $favourites = $favouriteModel->getUserFavourites($user['user_id']);

        echo json_encode([
            'success' => true,
            'favourites' => $favourites
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Toggle favourite
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $recipeId = (int)($input['recipe_id'] ?? 0);

        if ($recipeId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid recipe ID.']);
            exit;
        }

        $result = $favouriteModel->toggle($user['user_id'], $recipeId);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed.']);
}