<?php
/**
 * GET /api/recipe.php?id=123 - Get recipe details
 * GET /api/recipe.php?slug=recipe-slug - Get recipe by slug
 * PUT /api/recipe.php - Update recipe
 * DELETE /api/recipe.php - Delete recipe
 * Matches enterprise schema
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../models/Rating.php';
require_once __DIR__ . '/../models/Favourite.php';
require_once __DIR__ . '/../middleware/auth.php';

try {
    $recipeModel = new Recipe();

    // GET - Fetch recipe details
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $slug = $_GET['slug'] ?? '';
        $incrementView = isset($_GET['view']) && $_GET['view'] === 'true';

        $recipe = null;

        if ($recipeId) {
            $recipe = $recipeModel->getById($recipeId, $incrementView);
        } elseif ($slug) {
            $recipe = $recipeModel->getBySlug($slug, $incrementView);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Recipe ID or slug required.']);
            exit;
        }

        if (!$recipe) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Recipe not found.']);
            exit;
        }

        // Check if recipe is private and user is not the author
        if ($recipe['visibility'] === 'Private' || $recipe['visibility'] === 'Draft') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $isAuthor = isset($_SESSION['user_id']) && $_SESSION['user_id'] === $recipe['author_id'];
            $isAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Moderator']);

            if (!$isAuthor && !$isAdmin) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Recipe not found.']);
                exit;
            }
        }

        // Get ratings
        $ratingModel = new Rating();
        $recipe['rating_summary'] = $ratingModel->getRecipeRatingSummary($recipeId);

        // Get related recipes
        $recipe['related_recipes'] = $recipeModel->getRelated($recipeId, 4);

        // Check user's favourite status and rating
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            $favouriteModel = new Favourite();
            $recipe['is_favourite'] = $favouriteModel->isFavourite($_SESSION['user_id'], $recipeId);
            $recipe['user_rating'] = $ratingModel->getUserRating($_SESSION['user_id'], $recipeId);
        }

        echo json_encode([
            'success' => true,
            'recipe' => $recipe
        ]);
    }

    // PUT - Update recipe
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $user = requireAuth();

        $recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$recipeId) {
            parse_str(file_get_contents('php://input'), $putVars);
            $recipeId = $putVars['id'] ?? null;

            if (!$recipeId) {
                $jsonInput = json_decode(file_get_contents('php://input'), true);
                $recipeId = $jsonInput['id'] ?? null;
            }
        }

        if (!$recipeId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Recipe ID required.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $result = $recipeModel->update($recipeId, $user['user_id'], $input);

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    // DELETE - Delete recipe
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $user = requireAuth();

        $recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$recipeId) {
            parse_str(file_get_contents('php://input'), $deleteVars);
            $recipeId = $deleteVars['id'] ?? null;
        }

        if (!$recipeId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Recipe ID required.']);
            exit;
        }

        // Check if hard delete requested (admin only)
        $hardDelete = ($_GET['hard'] ?? '') === 'true';

        if ($hardDelete && in_array($user['role'], ['Admin', 'Moderator'])) {
            $result = $recipeModel->hardDelete($recipeId);
        } else {
            $result = $recipeModel->delete($recipeId, $user['user_id']);
        }

        if ($result['success']) {
            http_response_code(200);
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
    error_log("Recipe endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to process request.', 'code' => 'SERVER_ERROR']);
}