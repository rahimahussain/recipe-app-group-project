<?php
/**
 * Authentication Middleware
 * Handles token-based and session-based authentication for protected API endpoints
 * Matches the enterprise recipe application schema with api_tokens table
 *
 * Usage: require_once __DIR__ . '/../middleware/auth.php';
 */

require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Authenticate user via session or API token
 * Returns user data if authenticated, sends 401 response otherwise
 */
function authenticateRequest(): array {
    // Check for session-based authentication
    if (isset($_SESSION['user_id'])) {
        $user = getUserFromDatabase($_SESSION['user_id']);

        if ($user && $user['account_status'] === 'Active') {
            // Update last login if needed
            updateLastActivity($_SESSION['user_id']);

            return [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'full_name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role'],
                'dietary_preference' => $user['dietary_preference'],
                'profile_image_url' => $user['profile_image_url']
            ];
        }

        // Session exists but user is not active
        session_destroy();
        sendUnauthorizedResponse('Account is not active. Please contact support.');
    }

    // Support API token authentication via Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $userData = validateApiToken($token);

        if ($userData) {
            // Set session for subsequent requests
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['username'] = $userData['username'];

            return $userData;
        }
    }

    // Not authenticated
    sendUnauthorizedResponse('Authentication required. Please log in.');
    exit; // This line won't execute due to exit in sendUnauthorizedResponse, but kept for clarity
}

/**
 * Validate API token against stored hashed tokens in api_tokens table
 */
function validateApiToken(string $token): ?array {
    try {
        $db = Database::getInstance()->getConnection();

        // Decode the base64 token
        $decoded = base64_decode($token, true);

        if ($decoded === false) {
            return null;
        }

        // Split into user_id and token part
        $parts = explode(':', $decoded, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$userId, $tokenPart] = $parts;

        if (!is_numeric($userId)) {
            return null;
        }

        $userId = (int)$userId;

        // Hash the token part for comparison
        $tokenHash = hash('sha256', $tokenPart);

        // Check token in database
        $stmt = $db->prepare("
            SELECT 
                t.id as token_id,
                t.user_id, 
                t.token_hash, 
                t.expires_at,
                u.id, u.username, u.email, u.first_name, u.last_name,
                u.dietary_preference, u.role, u.account_status,
                u.profile_image_url
            FROM api_tokens t
            INNER JOIN users u ON t.user_id = u.id
            WHERE t.user_id = :user_id 
              AND t.token_hash = :token_hash
              AND (t.expires_at IS NULL OR t.expires_at > NOW())
              AND u.account_status = 'Active'
            LIMIT 1
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash
        ]);

        $result = $stmt->fetch();

        if (!$result) {
            // Token not found, expired, or user not active
            return null;
        }

        // Log successful API authentication
        logApiAccess($userId, $result['token_id'], true);

        return [
            'user_id' => $result['id'],
            'username' => $result['username'],
            'email' => $result['email'],
            'first_name' => $result['first_name'],
            'last_name' => $result['last_name'],
            'full_name' => $result['first_name'] . ' ' . $result['last_name'],
            'role' => $result['role'],
            'dietary_preference' => $result['dietary_preference'],
            'profile_image_url' => $result['profile_image_url'],
            'account_status' => $result['account_status']
        ];

    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate an API token for a user and store hash in api_tokens table
 */
function generateApiToken(int $userId): string {
    try {
        $db = Database::getInstance()->getConnection();

        // Generate cryptographically secure random token
        $tokenPart = bin2hex(random_bytes(32));

        // Create the full token (user_id:random_token)
        $fullToken = base64_encode($userId . ':' . $tokenPart);

        // Hash the token part for storage
        $tokenHash = hash('sha256', $tokenPart);

        // Set expiration (30 days from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Clean up expired tokens for this user
        $cleanStmt = $db->prepare("
            DELETE FROM api_tokens 
            WHERE user_id = :user_id AND expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $cleanStmt->execute([':user_id' => $userId]);

        // Limit active tokens per user (max 5)
        $countStmt = $db->prepare("
            SELECT COUNT(*) FROM api_tokens 
            WHERE user_id = :user_id AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $countStmt->execute([':user_id' => $userId]);
        $activeTokenCount = (int)$countStmt->fetchColumn();

        if ($activeTokenCount >= 5) {
            // Remove oldest active token
            $deleteStmt = $db->prepare("
                DELETE FROM api_tokens 
                WHERE user_id = :user_id AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at ASC
                LIMIT 1
            ");
            $deleteStmt->execute([':user_id' => $userId]);
        }

        // Store token hash in database
        $stmt = $db->prepare("
            INSERT INTO api_tokens (user_id, token_hash, expires_at) 
            VALUES (:user_id, :token_hash, :expires_at)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt
        ]);

        // Log audit
        logAuditEntry($userId, 'ApiToken', (int)$db->lastInsertId(), 'generate', null, [
            'expires_at' => $expiresAt
        ]);

        return $fullToken;

    } catch (Exception $e) {
        error_log("Token generation error: " . $e->getMessage());
        // Fallback to simple token if database operations fail
        return generateFallbackToken($userId);
    }
}

/**
 * Revoke a specific API token
 */
function revokeApiToken(int $userId, string $token): bool {
    try {
        $db = Database::getInstance()->getConnection();

        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return false;
        }

        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $tokenHash = hash('sha256', $parts[1]);

        $stmt = $db->prepare("
            DELETE FROM api_tokens 
            WHERE user_id = :user_id AND token_hash = :token_hash
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash
        ]);

        return $stmt->rowCount() > 0;

    } catch (Exception $e) {
        error_log("Token revocation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Revoke all API tokens for a user
 */
function revokeAllApiTokens(int $userId): bool {
    try {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("DELETE FROM api_tokens WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);

        return true;

    } catch (Exception $e) {
        error_log("Revoke all tokens error: " . $e->getMessage());
        return false;
    }
}

/**
 * Require authentication - wrapper function
 */
function requireAuth(): array {
    return authenticateRequest();
}

/**
 * Require specific role for access
 */
function requireRole(string|array $allowedRoles): array {
    $user = authenticateRequest();

    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

    if (!in_array($user['role'], $roles)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient permissions. Required role: ' . implode(' or ', $roles)
        ]);
        exit;
    }

    return $user;
}

/**
 * Get user from database with full details
 */
function getUserFromDatabase(int $userId): ?array {
    try {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT id, username, email, first_name, last_name, 
                   dietary_preference, role, account_status, profile_image_url,
                   last_login, created_at, updated_at
            FROM users 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);

        return $stmt->fetch() ?: null;

    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update last activity timestamp
 */
function updateLastActivity(int $userId): void {
    try {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            UPDATE users SET last_login = NOW() 
            WHERE id = :id AND last_login < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([':id' => $userId]);

    } catch (Exception $e) {
        error_log("Update last activity error: " . $e->getMessage());
    }
}

/**
 * Log API access for auditing
 */
function logApiAccess(int $userId, int $tokenId, bool $success): void {
    try {
        // This could be extended to log to a separate API access log table
        // For now, we'll use the existing audit_logs
        logAuditEntry($userId, 'ApiAccess', $tokenId, $success ? 'authenticated' : 'failed', null, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Also log failed attempts to login_history
        if (!$success) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO login_history (user_id, ip_address, user_agent, login_status) 
                VALUES (:user_id, :ip, :agent, 'Failed')
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        }

    } catch (Exception $e) {
        error_log("API access log error: " . $e->getMessage());
    }
}

/**
 * Log audit entry to audit_logs table
 */
function logAuditEntry(?int $userId, string $entityName, int $entityId, string $actionType, $oldValue, $newValue): void {
    try {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, entity_name, entity_id, action_type, old_value, new_value, ip_address, user_agent) 
            VALUES (:user_id, :entity, :entity_id, :action, :old_val, :new_val, :ip, :agent)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':entity' => $entityName,
            ':entity_id' => $entityId,
            ':action' => $actionType,
            ':old_val' => $oldValue ? json_encode($oldValue) : null,
            ':new_val' => $newValue ? json_encode($newValue) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Send unauthorized response and exit
 */
function sendUnauthorizedResponse(string $message = 'Authentication required. Please log in.'): void {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'code' => 'UNAUTHORIZED'
    ]);
    exit;
}

/**
 * Generate a fallback token when database operations fail
 */
function generateFallbackToken(int $userId): string {
    $payload = [
        'user_id' => $userId,
        'created' => time(),
        'expires' => time() + (24 * 60 * 60), // 24 hours
        'nonce' => bin2hex(random_bytes(8))
    ];

    return base64_encode(json_encode($payload));
}

/**
 * Clean up expired tokens (can be called by a cron job)
 */
function cleanupExpiredTokens(): int {
    try {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            DELETE FROM api_tokens 
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $stmt->execute();

        return $stmt->rowCount();

    } catch (Exception $e) {
        error_log("Token cleanup error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get active tokens count for a user
 */
function getActiveTokenCount(int $userId): int {
    try {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM api_tokens 
            WHERE user_id = :user_id 
              AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([':user_id' => $userId]);

        return (int)$stmt->fetchColumn();

    } catch (Exception $e) {
        error_log("Token count error: " . $e->getMessage());
        return 0;
    }
}