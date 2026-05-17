<?php
/**
 * GET /api/favourites.php - List user's favourites
 * POST /api/favourites.php - Toggle favourite
 * PUT /api/favourites.php - Move to folder
 * DELETE /api/favourites.php - Remove favourite
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
require_once __DIR__ . '/../models/Favourite.php';

// Require authentication for all operations
$user = requireAuth();
$userId = $user['user_id'];

try {
    $favouriteModel = new Favourite();

    // GET - List favourites
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
        $folderName = $_GET['folder'] ?? null;

        // Check if folders list requested
        if (isset($_GET['folders']) && $_GET['folders'] === 'true') {
            $folders = $favouriteModel->getUserFolders($userId);

            echo json_encode([
                'success' => true,
                'folders' => $folders
            ]);
            exit;
        }

        // Check if collections requested
        if (isset($_GET['collections']) && $_GET['collections'] === 'true') {
            $collections = $favouriteModel->getUserCollections($userId);

            echo json_encode([
                'success' => true,
                'collections' => $collections
            ]);
            exit;
        }

        $result = $favouriteModel->getUserFavourites($userId, $page, $limit, $folderName);
        echo json_encode($result);
    }

    // POST - Toggle favourite
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $recipeId = (int)($input['recipe_id'] ?? 0);
        $folderName = $input['folder_name'] ?? null;

        if ($recipeId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid recipe ID.']);
            exit;
        }

        $result = $favouriteModel->toggle($userId, $recipeId, $folderName);

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    // PUT - Move favourite to different folder
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $recipeId = (int)($input['recipe_id'] ?? 0);
        $newFolder = $input['folder_name'] ?? '';

        if ($recipeId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid recipe ID.']);
            exit;
        }

        $result = $favouriteModel->moveToFolder($userId, $recipeId, $newFolder);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Favourites endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed.', 'code' => 'SERVER_ERROR']);
}