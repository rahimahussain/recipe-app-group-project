<?php
/**
 * User Model
 * Handles user-related database operations
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new user
     */
    public function create(array $data): array {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "{$field} is required."];
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }

            // Validate username format
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
                return ['success' => false, 'message' => 'Username must be 3-50 characters (letters, numbers, underscores).'];
            }

            // Validate password strength
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
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

            // Hash password and insert user
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, dietary_preference) 
                VALUES (:username, :email, :password, :full_name, :dietary)
            ");

            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $passwordHash,
                ':full_name' => $data['full_name'] ?? '',
                ':dietary' => $data['dietary_preference'] ?? 'None'
            ]);

            return [
                'success' => true,
                'message' => 'Registration successful.',
                'user_id' => $this->db->lastInsertId()
            ];

        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Authenticate user login
     */
    public function login(string $username, string $password): array {
        try {
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required.'];
            }

            // Find user by username or email
            $stmt = $this->db->prepare("
                SELECT id, username, email, password_hash, full_name, dietary_preference 
                FROM users 
                WHERE username = :username OR email = :email
            ");
            $stmt->execute([
                ':username' => $username,
                ':email' => $username
            ]);

            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Generate API token
            $token = $this->generateSessionToken($user['id']);

            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'dietary_preference' => $user['dietary_preference']
                ],
                'token' => $token
            ];

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    /**
     * Get user profile by ID
     */
    public function getById(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, username, email, full_name, bio, dietary_preference, created_at 
            FROM users 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Generate session-based authentication token
     */
    private function generateSessionToken(int $userId): string {
        $payload = [
            'user_id' => $userId,
            'created' => time(),
            'expires' => time() + (24 * 60 * 60)
        ];
        return base64_encode(json_encode($payload));
    }
}