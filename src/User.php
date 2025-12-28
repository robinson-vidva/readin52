<?php
/**
 * User model class
 */
class User
{
    /**
     * Create a new user
     */
    public static function create(string $name, string $email, string $password, string $role = 'user'): ?int
    {
        $pdo = Database::getInstance();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $passwordHash, $role]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Find user by ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all users
     */
    public static function getAll(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, preferred_translation, created_at, last_login
            FROM users
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Count all users
     */
    public static function count(): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Count active users (logged in within last 30 days)
     */
    public static function countActive(): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT COUNT(*) as count FROM users
            WHERE last_login > datetime('now', '-30 days')
        ");
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Update user
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance();
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'email', 'role', 'preferred_translation'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Update user password
     */
    public static function updatePassword(int $id, string $password): bool
    {
        $pdo = Database::getInstance();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$passwordHash, $id]);
    }

    /**
     * Update last login timestamp
     */
    public static function updateLastLogin(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Delete user
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Search users by name or email
     */
    public static function search(string $query): array
    {
        $pdo = Database::getInstance();
        $searchTerm = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, preferred_translation, created_at, last_login
            FROM users
            WHERE name LIKE ? OR email LIKE ?
            ORDER BY name ASC
            LIMIT 50
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    /**
     * Check if user exists
     */
    public static function exists(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    /**
     * Verify current password
     */
    public static function verifyPassword(int $id, string $password): bool
    {
        $user = self::findById($id);
        if (!$user) {
            return false;
        }
        return password_verify($password, $user['password_hash']);
    }
}
