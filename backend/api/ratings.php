<?php
/**
 * GET /api/ratings.php?recipe_id=123 - Get ratings for recipe
 * POST /api/ratings.php - Submit a rating
 * PUT /api/ratings.php - Update a rating
 * DELETE /api/ratings.php - Delete a rating
 * Matches enterprise schema
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Rating.php';

try {
    $ratingModel = new Rating();

    // GET - Get ratings
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $recipeId = filter_input(INPUT_GET, 'recipe_id', FILTER_VALIDATE_INT);

        // Get user's own ratings
        if (isset($_GET['user_ratings']) && $_GET['user_ratings'] === 'true') {
            $user = requireAuth();
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));

            $ratings = $ratingModel->getUserRatings($user['user_id'], $page, $limit);

            echo json_encode([
                'success' => true,
                'ratings' => $ratings
            ]);
            exit;
        }

        // Get summary only
        if (isset($_GET['summary']) && $_GET['summary'] === 'true' && $recipeId) {
            $summary = $ratingModel->getRecipeRatingSummary($recipeId);

            echo json_encode([
                'success' => true,
                'summary' => $summary
            ]);
            exit;
        }

        // Get ratings for a recipe
        if ($recipeId) {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            $sortBy = $_GET['sort'] ?? 'newest';

            $result = $ratingModel->getRecipeRatings($recipeId, $page, $limit, $sortBy);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Recipe ID required.']);
        }
    }

    // POST - Submit rating
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = requireAuth();

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        // Validate required fields
        if (empty($input['recipe_id']) || empty($input['overall_rating'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Recipe ID and overall rating are required.']);
            exit;
        }

        $result = $ratingModel->submitRating($user['user_id'], $input);

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    // PUT - Update rating
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $user = requireAuth();

        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        // The submitRating method handles both create and update via upsert
        $result = $ratingModel->submitRating($user['user_id'], $input);

        if ($result['success']) {
            $result['message'] = 'Rating updated successfully.';
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    // DELETE - Delete rating
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $user = requireAuth();

        $ratingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$ratingId) {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $ratingId = $input['id'] ?? null;
        }

        if (!$ratingId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating ID required.']);
            exit;
        }

        $result = $ratingModel->deleteRating($user['user_id'], $ratingId);
        echo json_encode($result);
    }

    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Ratings endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed.', 'code' => 'SERVER_ERROR']);
}