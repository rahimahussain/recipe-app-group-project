<?php
/**
 * POST /api/login.php
 * Authenticate user and return token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../models/User.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    $userModel = new User();
    $result = $userModel->login($username, $password);

    if ($result['success']) {
        // Set session for server-side auth
        session_start();
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['full_name'] = $result['user']['full_name'];

        http_response_code(200);
    } else {
        http_response_code(401);
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login failed.']);
}