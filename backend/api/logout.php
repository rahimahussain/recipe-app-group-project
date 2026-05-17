<?php
/**
 * POST /api/logout.php
 * Logout user, destroy session and optionally revoke API token
 * Matches enterprise schema with api_tokens cleanup
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

try {
    $userId = null;

    // Get user ID from session if available
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    // Optionally revoke the current API token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];

        if ($userId) {
            revokeApiToken($userId, $token);
        }
    }

    // Log logout in audit log
    if ($userId) {
        logAuditEntry($userId, 'User', $userId, 'logout', null, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // Destroy session
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully.'
    ]);

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());

    // Still destroy session even if errors occur
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Logged out.'
    ]);
}