<?php
/**
 * POST /api/login.php
 * Authenticate user and return token
 * Matches enterprise schema with login_history and api_tokens
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed', 'code' => 'METHOD_NOT_ALLOWED']);
    exit;
}

require_once __DIR__ . '/../models/User.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    // Get client info for logging
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $userModel = new User();
    $result = $userModel->login($username, $password, $ipAddress, $userAgent);

    if ($result['success']) {
        // Start session for server-side auth
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['first_name'] = $result['user']['first_name'];
        $_SESSION['last_name'] = $result['user']['last_name'];
        $_SESSION['role'] = $result['user']['role'];
        $_SESSION['logged_in_at'] = time();

        // Set secure session cookie parameters
        session_regenerate_id(true);

        http_response_code(200);

        // Remove sensitive data from response
        $response = $result;
        unset($response['user']['password_hash']);

        echo json_encode($response);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Login endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed due to server error.',
        'code' => 'SERVER_ERROR'
    ]);
}