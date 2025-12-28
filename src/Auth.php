<?php
/**
 * Authentication class
 */
class Auth
{
    /**
     * Start session if not already started
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * Get current user ID
     */
    public static function getUserId(): ?int
    {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user data
     */
    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return User::findById(self::getUserId());
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        $user = self::getUser();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Attempt to log in user
     */
    public static function login(string $email, string $password): array
    {
        // Check rate limiting
        if (self::isRateLimited($email)) {
            return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordLoginAttempt($email);
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Regenerate session ID for security
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        // Update last login
        User::updateLastLogin($user['id']);

        // Clear login attempts
        self::clearLoginAttempts($email);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Log out current user
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Register new user
     */
    public static function register(string $name, string $email, string $password): array
    {
        // Check if registration is enabled
        if (!self::isRegistrationEnabled()) {
            return ['success' => false, 'error' => 'Registration is currently disabled.'];
        }

        // Validate input
        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['success' => false, 'error' => 'Name must be between 2 and 100 characters.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Please enter a valid email address.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters.'];
        }

        // Check if email already exists
        if (User::findByEmail($email)) {
            return ['success' => false, 'error' => 'An account with this email already exists.'];
        }

        // Create user
        $userId = User::create($name, $email, $password);

        if (!$userId) {
            return ['success' => false, 'error' => 'Failed to create account. Please try again.'];
        }

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Check if registration is enabled
     */
    public static function isRegistrationEnabled(): bool
    {
        return Database::getSetting('registration_enabled', '1') === '1';
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        self::startSession();
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Check if login attempts are rate limited
     */
    private static function isRateLimited(string $email): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$email, LOGIN_RATE_WINDOW]);
        $result = $stmt->fetch();

        return $result['attempts'] >= LOGIN_RATE_LIMIT;
    }

    /**
     * Record a login attempt
     */
    private static function recordLoginAttempt(string $email): void
    {
        $pdo = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
        $stmt->execute([$email, $ip]);
    }

    /**
     * Clear login attempts for email
     */
    private static function clearLoginAttempts(string $email): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }

    /**
     * Require authentication - redirect to login if not logged in
     */
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            redirect('/?route=login');
        }
    }

    /**
     * Require admin role - redirect if not admin
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            redirect('/?route=dashboard');
        }
    }
}
