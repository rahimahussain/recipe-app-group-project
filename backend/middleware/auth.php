<?php
/**
 * Authentication Middleware
 * Handles JWT token validation for protected API endpoints
 *
 * Usage: require_once __DIR__ . '/../middleware/auth.php';
 */

require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Authenticate user via session-based token
 * Returns user data if authenticated, sends 401 response otherwise
 */
function authenticateRequest(): array {
    // Check for session-based authentication
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'] ?? ''
        ];
    }

    // Also support API key/token authentication via header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $userData = validateApiToken($token);

        if ($userData) {
            return $userData;
        }
    }

    // Not authenticated
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required. Please log in.'
    ]);
    exit;
}

/**
 * Validate simple API token (for demonstration)
 * In production, use proper JWT tokens
 */
function validateApiToken(string $token): ?array {
    try {
        $db = Database::getInstance()->getConnection();

        // Simple token validation (decode and check)
        $decoded = json_decode(base64_decode($token), true);

        if (!$decoded || !isset($decoded['user_id']) || !isset($decoded['expires'])) {
            return null;
        }

        // Check expiration
        if ($decoded['expires'] < time()) {
            return null;
        }

        // Verify user exists
        $stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE id = :id");
        $stmt->execute([':id' => $decoded['user_id']]);
        $user = $stmt->fetch();

        return $user ? [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name']
        ] : null;

    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate an API token for a user
 */
function generateApiToken(int $userId): string {
    $payload = [
        'user_id' => $userId,
        'created' => time(),
        'expires' => time() + (24 * 60 * 60) // 24 hours
    ];

    return base64_encode(json_encode($payload));
}

/**
 * Require authentication - wrapper function
 */
function requireAuth(): array {
    return authenticateRequest();
}