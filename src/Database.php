<?php
/**
 * Database class - MySQL/MariaDB PDO wrapper
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get database instance (singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }
        return self::$instance;
    }

    /**
     * Create database connection
     */
    private static function connect(): PDO
    {
        try {
            // Build DSN - port is optional (defaults to 3306)
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            // Add port only if explicitly set and not default
            if (defined('DB_PORT') && DB_PORT && DB_PORT !== '3306') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;

        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if database tables exist (app is installed)
     */
    public static function isInstalled(): bool
    {
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Initialize database with schema
     */
    public static function initialize(): void
    {
        $pdo = self::getInstance();

        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                preferred_translation VARCHAR(20) DEFAULT 'eng_kjv',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                INDEX idx_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Reading progress table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reading_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                week_number TINYINT NOT NULL,
                category ENUM('poetry', 'history', 'prophecy', 'gospels') NOT NULL,
                completed TINYINT(1) DEFAULT 0,
                completed_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_progress (user_id, week_number, category),
                INDEX idx_progress_user (user_id),
                INDEX idx_progress_week (week_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Settings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(50) PRIMARY KEY,
                `value` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Login attempts table (for rate limiting)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_attempts (email, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Chapter-level progress table (granular tracking)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chapter_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                week_number TINYINT NOT NULL,
                category ENUM('poetry', 'history', 'prophecy', 'gospels') NOT NULL,
                book VARCHAR(5) NOT NULL,
                chapter SMALLINT NOT NULL,
                completed TINYINT(1) DEFAULT 0,
                completed_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_chapter_progress (user_id, week_number, category, book, chapter),
                INDEX idx_chapter_user (user_id),
                INDEX idx_chapter_week (week_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Reading categories table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reading_categories (
                id VARCHAR(20) PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                color VARCHAR(7) NOT NULL,
                sort_order TINYINT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Reading plan table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reading_plan (
                id INT AUTO_INCREMENT PRIMARY KEY,
                week_number TINYINT NOT NULL,
                category_id VARCHAR(20) NOT NULL,
                reference VARCHAR(100) NOT NULL,
                passages JSON NOT NULL,
                UNIQUE KEY unique_week_category (week_number, category_id),
                INDEX idx_week (week_number),
                INDEX idx_category (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Available translations table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bible_translations (
                id VARCHAR(20) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                language VARCHAR(50) NOT NULL,
                direction ENUM('ltr', 'rtl') DEFAULT 'ltr'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Run migrations for existing databases
     */
    public static function migrate(): void
    {
        $pdo = self::getInstance();

        // Check if chapter_progress table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'chapter_progress'");
        if ($stmt->fetch() === false) {
            // Create chapter_progress table
            $pdo->exec("
                CREATE TABLE chapter_progress (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    week_number TINYINT NOT NULL,
                    category ENUM('poetry', 'history', 'prophecy', 'gospels') NOT NULL,
                    book VARCHAR(5) NOT NULL,
                    chapter SMALLINT NOT NULL,
                    completed TINYINT(1) DEFAULT 0,
                    completed_at TIMESTAMP NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_chapter_progress (user_id, week_number, category, book, chapter),
                    INDEX idx_chapter_user (user_id),
                    INDEX idx_chapter_week (week_number)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Add theme column to users table if not exists
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('theme', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN theme ENUM('light', 'dark', 'auto') DEFAULT 'auto' AFTER preferred_translation");
        }

        // Add secondary_translation column to users table if not exists
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('secondary_translation', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN secondary_translation VARCHAR(20) DEFAULT NULL AFTER preferred_translation");
        }

        // Create reading_categories table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'reading_categories'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE reading_categories (
                    id VARCHAR(20) PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    color VARCHAR(7) NOT NULL,
                    sort_order TINYINT NOT NULL DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Create reading_plan table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'reading_plan'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE reading_plan (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    week_number TINYINT NOT NULL,
                    category_id VARCHAR(20) NOT NULL,
                    reference VARCHAR(100) NOT NULL,
                    passages JSON NOT NULL,
                    UNIQUE KEY unique_week_category (week_number, category_id),
                    INDEX idx_week (week_number),
                    INDEX idx_category (category_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Create bible_translations table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'bible_translations'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE bible_translations (
                    id VARCHAR(20) PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    language VARCHAR(50) NOT NULL,
                    direction ENUM('ltr', 'rtl') DEFAULT 'ltr'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Import reading plan from JSON if tables are empty
        self::importReadingPlanFromJson();
    }

    /**
     * Import reading plan from JSON file into database
     */
    public static function importReadingPlanFromJson(): void
    {
        $pdo = self::getInstance();

        // Check if reading_plan already has data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM reading_plan");
        $count = (int) $stmt->fetch()['count'];
        if ($count > 0) {
            return; // Already imported
        }

        // Load JSON file
        $jsonPath = CONFIG_PATH . '/reading-plan.json';
        if (!file_exists($jsonPath)) {
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data) {
            return;
        }

        // Import translations
        if (isset($data['availableTranslations'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO bible_translations (id, name, language, direction) VALUES (?, ?, ?, ?)");
            foreach ($data['availableTranslations'] as $trans) {
                $stmt->execute([
                    $trans['id'],
                    $trans['name'],
                    $trans['language'],
                    $trans['direction'] ?? 'ltr'
                ]);
            }
        }

        // Import categories
        if (isset($data['categories'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO reading_categories (id, name, color, sort_order) VALUES (?, ?, ?, ?)");
            $sortOrder = 0;
            foreach ($data['categories'] as $cat) {
                $stmt->execute([
                    $cat['id'],
                    $cat['name'],
                    $cat['color'],
                    $sortOrder++
                ]);
            }
        }

        // Import weeks/readings
        if (isset($data['weeks'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO reading_plan (week_number, category_id, reference, passages) VALUES (?, ?, ?, ?)");
            foreach ($data['weeks'] as $week) {
                $weekNum = $week['week'];
                foreach ($week['readings'] as $categoryId => $reading) {
                    $stmt->execute([
                        $weekNum,
                        $categoryId,
                        $reading['reference'],
                        json_encode($reading['passages'])
                    ]);
                }
            }
        }
    }

    /**
     * Insert default settings
     */
    public static function insertDefaultSettings(): void
    {
        $pdo = self::getInstance();

        $settings = [
            ['app_name', 'ReadIn52'],
            ['default_translation', 'eng_kjv'],
            ['available_translations', '["eng_kjv","tam_irv"]'],
            ['registration_enabled', '1']
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    /**
     * Get a setting value
     */
    public static function getSetting(string $key, $default = null)
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : $default;
    }

    /**
     * Set a setting value
     */
    public static function setSetting(string $key, string $value): void
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute([$key, $value]);
    }
}
