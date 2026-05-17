<?php
/**
 * GET /api/profile.php - Get user profile
 * PUT /api/profile.php - Update user profile
 * PUT /api/profile.php?action=password - Change password
 * Matches enterprise schema
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/User.php';

// Require authentication
$user = requireAuth();
$userId = $user['user_id'];

try {
    $userModel = new User();

    // GET - Fetch profile
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if stats requested
        if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
            $stats = $userModel->getUserStats($userId);

            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            exit;
        }

        $profile = $userModel->getById($userId);

        if (!$profile) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Remove sensitive data
        unset($profile['password_hash']);

        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
    }

    // PUT - Update profile
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        // Check if password change requested
        $action = $_GET['action'] ?? '';

        if ($action === 'password') {
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            $result = $userModel->changePassword($userId, $currentPassword, $newPassword);
            echo json_encode($result);
            exit;
        }

        // Regular profile update
        $result = $userModel->update($userId, $input);
        echo json_encode($result);
    }

    else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Profile endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Operation failed.', 'code' => 'SERVER_ERROR']);
}