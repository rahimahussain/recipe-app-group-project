<?php
/**
 * POST /api/register.php
 * Register a new user account
 * Matches enterprise schema with audit_logs
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

    // Sanitize input
    $data = [
        'username' => trim($input['username'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'password' => $input['password'] ?? '',
        'first_name' => trim($input['first_name'] ?? ''),
        'last_name' => trim($input['last_name'] ?? ''),
        'bio' => trim($input['bio'] ?? ''),
        'dietary_preference' => $input['dietary_preference'] ?? 'None',
        'phone_number' => $input['phone_number'] ?? null,
        'profile_image_url' => $input['profile_image_url'] ?? null,
        'role' => $input['role'] ?? 'User' // Default to User, elevated roles require admin
    ];

    // Prevent self-assignment of elevated roles
    $allowedSelfRoles = ['User', 'Chef'];
    if (!in_array($data['role'], $allowedSelfRoles)) {
        $data['role'] = 'User';
    }

    $userModel = new User();
    $result = $userModel->create($data);

    if ($result['success']) {
        http_response_code(201);

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful. Please log in.',
            'user_id' => $result['user_id']
        ]);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Register endpoint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed due to server error.',
        'code' => 'SERVER_ERROR'
    ]);
}