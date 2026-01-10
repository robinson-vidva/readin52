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
            WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)
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

        $allowedFields = ['name', 'email', 'role', 'preferred_translation', 'secondary_translation', 'theme'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
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
     * Delete user and all associated data
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // Delete user's reading progress
            $stmt = $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?");
            $stmt->execute([$id]);

            // Delete user's chapter progress
            $stmt = $pdo->prepare("DELETE FROM chapter_progress WHERE user_id = ?");
            $stmt->execute([$id]);

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
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
     * Get all users with their progress and badge counts
     */
    public static function getAllWithProgress(int $limit = 100, int $offset = 0): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT
                u.id, u.name, u.email, u.role, u.preferred_translation,
                u.created_at, u.last_login,
                COALESCE(rp.completed_count, 0) as completed_readings,
                COALESCE(ub.badge_count, 0) as badge_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) as completed_count
                FROM reading_progress
                WHERE completed = 1
                GROUP BY user_id
            ) rp ON u.id = rp.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) as badge_count
                FROM user_badges
                GROUP BY user_id
            ) ub ON u.id = ub.user_id
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Search users with progress and badge counts
     */
    public static function searchWithProgress(string $query): array
    {
        $pdo = Database::getInstance();
        $searchTerm = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT
                u.id, u.name, u.email, u.role, u.preferred_translation,
                u.created_at, u.last_login,
                COALESCE(rp.completed_count, 0) as completed_readings,
                COALESCE(ub.badge_count, 0) as badge_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) as completed_count
                FROM reading_progress
                WHERE completed = 1
                GROUP BY user_id
            ) rp ON u.id = rp.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) as badge_count
                FROM user_badges
                GROUP BY user_id
            ) ub ON u.id = ub.user_id
            WHERE u.name LIKE ? OR u.email LIKE ?
            ORDER BY u.name ASC
            LIMIT 50
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
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

    // ==========================================
    // PASSWORD RESET METHODS
    // ==========================================

    /**
     * Create a password reset token
     */
    public static function createPasswordResetToken(string $email): ?array
    {
        $user = self::findByEmail($email);
        if (!$user) {
            return null;
        }

        $pdo = Database::getInstance();

        // Invalidate any existing tokens for this user
        $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
        $stmt->execute([$user['id']]);

        // Create new token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $token, $expiresAt]);

        return [
            'token' => $token,
            'user' => $user,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Validate a password reset token
     */
    public static function validatePasswordResetToken(string $token): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT pr.*, u.id as user_id, u.name, u.email
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Use a password reset token and update password
     */
    public static function resetPasswordWithToken(string $token, string $newPassword): bool
    {
        $resetData = self::validatePasswordResetToken($token);
        if (!$resetData) {
            return false;
        }

        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$passwordHash, $resetData['user_id']]);

            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);

            // Clear login attempts so user can login immediately
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
            $stmt->execute([$resetData['email']]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Clean up expired password reset tokens
     */
    public static function cleanupExpiredPasswordResets(): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ==========================================
    // EMAIL CHANGE METHODS
    // ==========================================

    /**
     * Create an email verification token for email change
     */
    public static function createEmailVerificationToken(int $userId, string $newEmail): ?array
    {
        // Check if new email is already in use
        $existingUser = self::findByEmail($newEmail);
        if ($existingUser) {
            return null;
        }

        $user = self::findById($userId);
        if (!$user) {
            return null;
        }

        $pdo = Database::getInstance();

        // Invalidate any existing tokens for this user
        $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE user_id = ? AND used = 0");
        $stmt->execute([$userId]);

        // Create new token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare("
            INSERT INTO email_verifications (user_id, new_email, token, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $newEmail, $token, $expiresAt]);

        return [
            'token' => $token,
            'user' => $user,
            'new_email' => $newEmail,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Validate an email verification token
     */
    public static function validateEmailVerificationToken(string $token): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT ev.*, u.id as user_id, u.name, u.email as old_email
            FROM email_verifications ev
            JOIN users u ON ev.user_id = u.id
            WHERE ev.token = ? AND ev.used = 0 AND ev.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Complete email change with verification token
     */
    public static function completeEmailChange(string $token): ?array
    {
        $verifyData = self::validateEmailVerificationToken($token);
        if (!$verifyData) {
            return null;
        }

        // Check again that new email isn't taken
        $existingUser = self::findByEmail($verifyData['new_email']);
        if ($existingUser) {
            return null;
        }

        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // Update email
            $stmt = $pdo->prepare("UPDATE users SET email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$verifyData['new_email'], $verifyData['user_id']]);

            // Mark token as used
            $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);

            $pdo->commit();

            return [
                'user_id' => $verifyData['user_id'],
                'old_email' => $verifyData['old_email'],
                'new_email' => $verifyData['new_email']
            ];
        } catch (PDOException $e) {
            $pdo->rollBack();
            return null;
        }
    }

    /**
     * Clean up expired email verification tokens
     */
    public static function cleanupExpiredEmailVerifications(): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE expires_at < NOW() OR used = 1");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
