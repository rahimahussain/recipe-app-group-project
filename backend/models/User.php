<?php
/**
 * User Model
 * Handles all user-related database operations
 * Matches the enterprise recipe application schema
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    // Valid ENUM values matching schema
    private const VALID_DIETARY_PREFERENCES = ['Vegetarian', 'Vegan', 'Non-vegetarian', 'None'];
    private const VALID_ROLES = ['Admin', 'Moderator', 'Chef', 'User'];
    private const VALID_ACCOUNT_STATUSES = ['Active', 'Locked', 'Disabled', 'Pending'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new user
     */
    public function create(array $data): array {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "{$field} is required."];
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }

            // Validate email length (VARCHAR(100))
            if (strlen($data['email']) > 100) {
                return ['success' => false, 'message' => 'Email must not exceed 100 characters.'];
            }

            // Validate username format and length (VARCHAR(50))
            if (strlen($data['username']) > 50) {
                return ['success' => false, 'message' => 'Username must not exceed 50 characters.'];
            }
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
                return ['success' => false, 'message' => 'Username must be 3-50 characters (letters, numbers, underscores).'];
            }

            // Validate name lengths (VARCHAR(50))
            if (strlen($data['first_name']) > 50) {
                return ['success' => false, 'message' => 'First name must not exceed 50 characters.'];
            }
            if (strlen($data['last_name']) > 50) {
                return ['success' => false, 'message' => 'Last name must not exceed 50 characters.'];
            }

            // Validate password strength
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
            }
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $data['password'])) {
                return ['success' => false, 'message' => 'Password must contain at least one letter and one number.'];
            }

            // Validate dietary preference if provided
            $dietaryPreference = 'None';
            if (!empty($data['dietary_preference'])) {
                if (!in_array($data['dietary_preference'], self::VALID_DIETARY_PREFERENCES)) {
                    return [
                        'success' => false,
                        'message' => 'Invalid dietary preference. Valid options: ' . implode(', ', self::VALID_DIETARY_PREFERENCES)
                    ];
                }
                $dietaryPreference = $data['dietary_preference'];
            }

            // Validate role if provided (only allow 'User' on registration)
            $role = 'User';
            if (!empty($data['role'])) {
                if (!in_array($data['role'], self::VALID_ROLES)) {
                    return ['success' => false, 'message' => 'Invalid role specified.'];
                }
                // Prevent self-assignment of elevated roles
                if (!in_array($data['role'], ['User', 'Chef'])) {
                    $data['role'] = 'User';
                }
                $role = $data['role'];
            }

            // Validate phone number if provided
            $phoneNumber = null;
            if (!empty($data['phone_number'])) {
                if (strlen($data['phone_number']) > 30) {
                    return ['success' => false, 'message' => 'Phone number must not exceed 30 characters.'];
                }
                $phoneNumber = $data['phone_number'];
            }

            // Validate profile image URL if provided
            $profileImageUrl = null;
            if (!empty($data['profile_image_url'])) {
                if (strlen($data['profile_image_url']) > 500) {
                    return ['success' => false, 'message' => 'Profile image URL too long.'];
                }
                if (!filter_var($data['profile_image_url'], FILTER_VALIDATE_URL)) {
                    return ['success' => false, 'message' => 'Invalid profile image URL.'];
                }
                $profileImageUrl = $data['profile_image_url'];
            }

            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email']
            ]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }

            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]);

            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    username, email, password_hash, first_name, last_name, 
                    bio, dietary_preference, role, profile_image_url, phone_number,
                    account_status
                ) VALUES (
                    :username, :email, :password, :first_name, :last_name,
                    :bio, :dietary, :role, :profile_image, :phone,
                    'Active'
                )
            ");

            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $passwordHash,
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':bio' => $data['bio'] ?? null,
                ':dietary' => $dietaryPreference,
                ':role' => $role,
                ':profile_image' => $profileImageUrl,
                ':phone' => $phoneNumber
            ]);

            $userId = $this->db->lastInsertId();

            // Log the registration in audit log
            $this->logAudit($userId, 'User', $userId, 'register', null, [
                'username' => $data['username'],
                'email' => $data['email']
            ]);

            return [
                'success' => true,
                'message' => 'Registration successful.',
                'user_id' => $userId
            ];

        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Authenticate user login
     */
    public function login(string $username, string $password, string $ipAddress = null, string $userAgent = null): array {
        $loginSuccess = false;
        $userId = null;

        try {
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required.'];
            }

            // Find user by username or email
            $stmt = $this->db->prepare("
                SELECT id, username, email, password_hash, first_name, last_name, 
                       dietary_preference, bio, role, account_status, profile_image_url,
                       phone_number, created_at, updated_at
                FROM users 
                WHERE (username = :username OR email = :email)
            ");
            $stmt->execute([
                ':username' => $username,
                ':email' => $username
            ]);

            $user = $stmt->fetch();
            $userId = $user['id'] ?? null;

            if (!$user) {
                $this->logLogin($userId, $ipAddress, $userAgent, 'Failed');
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Check account status
            if ($user['account_status'] !== 'Active') {
                $statusMessage = match($user['account_status']) {
                    'Locked' => 'Account is locked. Please contact support.',
                    'Disabled' => 'Account has been disabled.',
                    'Pending' => 'Account is pending verification.',
                    default => 'Account is not active.'
                };

                $this->logLogin($userId, $ipAddress, $userAgent, 'Failed');
                return ['success' => false, 'message' => $statusMessage];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->logLogin($userId, $ipAddress, $userAgent, 'Failed');
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Check if password needs rehash
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT, ['cost' => 12])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
                $updateStmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                $updateStmt->execute([':hash' => $newHash, ':id' => $user['id']]);
            }

            // Update last login timestamp
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);

            // Generate API token
            $token = $this->generateApiToken($user['id']);

            // Log successful login
            $this->logLogin($user['id'], $ipAddress, $userAgent, 'Success');
            $loginSuccess = true;

            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'full_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'dietary_preference' => $user['dietary_preference'],
                    'bio' => $user['bio'],
                    'role' => $user['role'],
                    'profile_image_url' => $user['profile_image_url'],
                    'phone_number' => $user['phone_number'],
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at']
                ],
                'token' => $token
            ];

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            if (!$loginSuccess) {
                $this->logLogin($userId, $ipAddress, $userAgent, 'Failed');
            }
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    /**
     * Get user profile by ID
     */
    public function getById(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, email, first_name, last_name, bio, 
                   dietary_preference, role, account_status, profile_image_url,
                   phone_number, last_login, created_at, updated_at
            FROM users 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);

        $user = $stmt->fetch();

        if ($user) {
            $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        }

        return $user ?: null;
    }

    /**
     * Get user by email
     */
    public function getByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, email, first_name, last_name, dietary_preference, role, account_status
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get user by username
     */
    public function getByUsername(string $username): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, email, first_name, last_name, dietary_preference, role, account_status
            FROM users 
            WHERE username = :username
        ");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update user profile
     */
    public function update(int $userId, array $data): array {
        try {
            $allowedFields = [
                'first_name', 'last_name', 'bio', 'dietary_preference',
                'profile_image_url', 'phone_number'
            ];

            $updates = [];
            $params = [':id' => $userId];
            $oldValues = [];

            // Get current user data for audit
            $currentUser = $this->getById($userId);
            if (!$currentUser) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    // Validate dietary preference
                    if ($field === 'dietary_preference' && !empty($data[$field])) {
                        if (!in_array($data[$field], self::VALID_DIETARY_PREFERENCES)) {
                            return [
                                'success' => false,
                                'message' => 'Invalid dietary preference. Valid options: ' . implode(', ', self::VALID_DIETARY_PREFERENCES)
                            ];
                        }
                    }

                    // Validate string lengths
                    if (in_array($field, ['first_name', 'last_name']) && strlen($data[$field]) > 50) {
                        return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' must not exceed 50 characters.'];
                    }

                    if ($field === 'profile_image_url' && strlen($data[$field]) > 500) {
                        return ['success' => false, 'message' => 'Profile image URL too long.'];
                    }

                    if ($field === 'phone_number' && strlen($data[$field]) > 30) {
                        return ['success' => false, 'message' => 'Phone number must not exceed 30 characters.'];
                    }

                    $oldValues[$field] = $currentUser[$field] ?? null;
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update.'];
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Log audit
            $this->logAudit($userId, 'User', $userId, 'update', $oldValues, $data);

            return ['success' => true, 'message' => 'Profile updated successfully.'];

        } catch (PDOException $e) {
            error_log("User update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed. Please try again.'];
        }
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        try {
            // Validate new password
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
            }
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $newPassword)) {
                return ['success' => false, 'message' => 'New password must contain at least one letter and one number.'];
            }

            // Get current password hash
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }

            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => 12]);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $newHash, ':id' => $userId]);

            // Log audit
            $this->logAudit($userId, 'User', $userId, 'password_change', null, null);

            return ['success' => true, 'message' => 'Password changed successfully.'];

        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed.'];
        }
    }

    /**
     * Update account status (admin function)
     */
    public function updateAccountStatus(int $userId, string $newStatus): array {
        if (!in_array($newStatus, self::VALID_ACCOUNT_STATUSES)) {
            return ['success' => false, 'message' => 'Invalid account status.'];
        }

        $stmt = $this->db->prepare("UPDATE users SET account_status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $userId]);

        return ['success' => true, 'message' => 'Account status updated.'];
    }

    /**
     * Get user statistics
     */
    public function getUserStats(int $userId): array {
        try {
            // Count recipes authored
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM recipes WHERE author_id = :id");
            $stmt->execute([':id' => $userId]);
            $recipeCount = (int)$stmt->fetchColumn();

            // Count favourites
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM favourites WHERE user_id = :id");
            $stmt->execute([':id' => $userId]);
            $favouriteCount = (int)$stmt->fetchColumn();

            // Count ratings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM ratings WHERE user_id = :id");
            $stmt->execute([':id' => $userId]);
            $ratingCount = (int)$stmt->fetchColumn();

            // Count comments
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE user_id = :id");
            $stmt->execute([':id' => $userId]);
            $commentCount = (int)$stmt->fetchColumn();

            return [
                'recipe_count' => $recipeCount,
                'favourite_count' => $favouriteCount,
                'rating_count' => $ratingCount,
                'comment_count' => $commentCount
            ];

        } catch (PDOException $e) {
            error_log("User stats error: " . $e->getMessage());
            return [
                'recipe_count' => 0,
                'favourite_count' => 0,
                'rating_count' => 0,
                'comment_count' => 0
            ];
        }
    }

    /**
     * Check if username is available
     */
    public function isUsernameAvailable(string $username): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return !$stmt->fetch();
    }

    /**
     * Check if email is available
     */
    public function isEmailAvailable(string $email): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return !$stmt->fetch();
    }

    /**
     * Generate API token and store hash
     */
    private function generateApiToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("
            INSERT INTO api_tokens (user_id, token_hash, expires_at) 
            VALUES (:user_id, :token_hash, :expires_at)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt
        ]);

        return base64_encode($userId . ':' . $token);
    }

    /**
     * Validate API token against stored hash
     */
    public function validateApiToken(string $token): ?int {
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$userId, $tokenPart] = $parts;
        $tokenHash = hash('sha256', $tokenPart);

        $stmt = $this->db->prepare("
            SELECT user_id FROM api_tokens 
            WHERE user_id = :user_id 
              AND token_hash = :token_hash 
              AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash
        ]);

        $result = $stmt->fetch();
        return $result ? (int)$result['user_id'] : null;
    }

    /**
     * Log login attempt
     */
    private function logLogin(?int $userId, ?string $ipAddress, ?string $userAgent, string $status): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_history (user_id, ip_address, user_agent, login_status) 
                VALUES (:user_id, :ip, :agent, :status)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':ip' => $ipAddress,
                ':agent' => $userAgent,
                ':status' => $status
            ]);
        } catch (PDOException $e) {
            error_log("Login log error: " . $e->getMessage());
        }
    }

    /**
     * Log audit entry
     */
    private function logAudit(?int $userId, string $entityName, int $entityId, string $actionType, $oldValue, $newValue): void {
        try {
            $stmt = $this->db->prepare("
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
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
}