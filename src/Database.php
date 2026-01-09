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

        // Password reset tokens table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_password_reset_token (token),
                INDEX idx_password_reset_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Email verification tokens table (for email changes)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                new_email VARCHAR(255) NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_email_verify_token (token),
                INDEX idx_email_verify_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Badges definition table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS badges (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(255) NOT NULL,
                icon VARCHAR(10) NOT NULL,
                category ENUM('book', 'engagement', 'streak', 'milestone') NOT NULL,
                criteria JSON NOT NULL,
                sort_order TINYINT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // User badges (earned badges)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_badges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                badge_id VARCHAR(50) NOT NULL,
                earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_badge (user_id, badge_id),
                INDEX idx_user_badges_user (user_id)
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

        // Add custom_logo column to users table if not exists
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('custom_logo', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN custom_logo VARCHAR(255) DEFAULT NULL AFTER theme");
        }

        // Fix category sort_order (poetry=0, history=1, prophecy=2, gospels=3)
        $pdo->exec("UPDATE reading_categories SET sort_order = 0 WHERE id = 'poetry'");
        $pdo->exec("UPDATE reading_categories SET sort_order = 1 WHERE id = 'history'");
        $pdo->exec("UPDATE reading_categories SET sort_order = 2 WHERE id = 'prophecy'");
        $pdo->exec("UPDATE reading_categories SET sort_order = 3 WHERE id = 'gospels'");

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

        // Create password_resets table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    expires_at TIMESTAMP NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_password_reset_token (token),
                    INDEX idx_password_reset_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Create email_verifications table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'email_verifications'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE email_verifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    new_email VARCHAR(255) NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    expires_at TIMESTAMP NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_email_verify_token (token),
                    INDEX idx_email_verify_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Create badges table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'badges'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE badges (
                    id VARCHAR(50) PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description VARCHAR(255) NOT NULL,
                    icon VARCHAR(10) NOT NULL,
                    category ENUM('book', 'engagement', 'streak', 'milestone') NOT NULL,
                    criteria JSON NOT NULL,
                    sort_order TINYINT NOT NULL DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Create user_badges table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_badges'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE user_badges (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    badge_id VARCHAR(50) NOT NULL,
                    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_badge (user_id, badge_id),
                    INDEX idx_user_badges_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Create notes table if not exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'notes'");
        if ($stmt->fetch() === false) {
            $pdo->exec("
                CREATE TABLE notes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    week_number TINYINT NULL,
                    category VARCHAR(20) NULL,
                    book VARCHAR(10) NULL,
                    chapter SMALLINT NULL,
                    color VARCHAR(20) DEFAULT 'default',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_notes_user (user_id),
                    INDEX idx_notes_reference (week_number, category),
                    INDEX idx_notes_book (book, chapter)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Import reading plan from JSON if tables are empty
        self::importReadingPlanFromJson();

        // Seed badges
        self::seedBadges();

        // Seed additional Bible translations
        self::seedBibleTranslations();
    }

    /**
     * Sync Bible translations from HelloAO API
     * Returns array with 'success', 'imported' count, or 'error' message
     */
    public static function syncTranslationsFromAPI(): array
    {
        $apiUrl = 'https://bible.helloao.org/api/available_translations.json';

        // Try to fetch from API with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'ReadIn52/1.0'
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);

        $json = @file_get_contents($apiUrl, false, $context);

        if ($json === false) {
            return ['success' => false, 'error' => 'Could not connect to Bible API. Please check your internet connection.'];
        }

        $data = json_decode($json, true);

        if (!$data || !isset($data['translations'])) {
            return ['success' => false, 'error' => 'Invalid response from Bible API'];
        }

        $pdo = self::getInstance();
        $imported = 0;

        // Clear existing translations and re-import
        $pdo->exec("DELETE FROM bible_translations");

        $stmt = $pdo->prepare("INSERT INTO bible_translations (id, name, language, direction) VALUES (?, ?, ?, ?)");

        foreach ($data['translations'] as $trans) {
            $id = $trans['id'] ?? '';
            $name = $trans['englishName'] ?? $trans['name'] ?? '';
            $language = $trans['languageEnglishName'] ?? $trans['language'] ?? 'Other';
            $direction = ($trans['textDirection'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';

            if ($id && $name) {
                try {
                    $stmt->execute([$id, $name, $language, $direction]);
                    $imported++;
                } catch (PDOException $e) {
                    // Skip duplicates
                }
            }
        }

        // Clear ReadingPlan cache
        if (class_exists('ReadingPlan')) {
            ReadingPlan::clearTranslationsCache();
        }

        return ['success' => true, 'imported' => $imported];
    }

    /**
     * Seed basic Bible translations (only runs if no translations exist)
     */
    public static function seedBibleTranslations(): void
    {
        // Only seed if no translations exist (will be populated from JSON or API sync)
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

    /**
     * Seed badges into the database
     */
    public static function seedBadges(): void
    {
        $pdo = self::getInstance();

        // Check if badges already seeded
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM badges");
        $count = (int) $stmt->fetch()['count'];
        if ($count > 0) {
            return;
        }

        $badges = [
            // Book Completion Badges
            ['genesis_journey', 'Genesis Journey', 'Complete all Genesis chapters', '📖', 'book', json_encode(['type' => 'book', 'book' => 'GEN']), 1],
            ['exodus_explorer', 'Exodus Explorer', 'Complete all Exodus chapters', '🏔️', 'book', json_encode(['type' => 'book', 'book' => 'EXO']), 2],
            ['psalms_singer', 'Psalms Singer', 'Complete all Psalms chapters', '🎵', 'book', json_encode(['type' => 'book', 'book' => 'PSA']), 3],
            ['proverbs_wise', 'Wisdom Seeker', 'Complete all Proverbs chapters', '🦉', 'book', json_encode(['type' => 'book', 'book' => 'PRO']), 4],
            ['isaiah_prophet', 'Prophet\'s Voice', 'Complete all Isaiah chapters', '📜', 'book', json_encode(['type' => 'book', 'book' => 'ISA']), 5],
            ['matthew_disciple', 'Matthew\'s Path', 'Complete all Matthew chapters', '✝️', 'book', json_encode(['type' => 'book', 'book' => 'MAT']), 6],
            ['john_beloved', 'Beloved Disciple', 'Complete all John chapters', '❤️', 'book', json_encode(['type' => 'book', 'book' => 'JHN']), 7],
            ['romans_theologian', 'Romans Scholar', 'Complete all Romans chapters', '⚖️', 'book', json_encode(['type' => 'book', 'book' => 'ROM']), 8],
            ['revelation_seer', 'Revelation Seer', 'Complete all Revelation chapters', '👁️', 'book', json_encode(['type' => 'book', 'book' => 'REV']), 9],

            // Category Completion Badges
            ['poetry_master', 'Poetry Master', 'Complete all Psalms & Wisdom readings', '📚', 'book', json_encode(['type' => 'category', 'category' => 'poetry']), 10],
            ['history_scholar', 'History Scholar', 'Complete all Law & History readings', '🏛️', 'book', json_encode(['type' => 'category', 'category' => 'history']), 11],
            ['prophecy_student', 'Prophecy Student', 'Complete all Prophetic readings', '🔮', 'book', json_encode(['type' => 'category', 'category' => 'prophecy']), 12],
            ['gospel_bearer', 'Gospel Bearer', 'Complete all Gospel & Letters readings', '✨', 'book', json_encode(['type' => 'category', 'category' => 'gospels']), 13],

            // Engagement Badges
            ['first_steps', 'First Steps', 'Complete your first reading', '👣', 'engagement', json_encode(['type' => 'readings', 'count' => 1]), 20],
            ['getting_started', 'Getting Started', 'Complete 10 readings', '🌱', 'engagement', json_encode(['type' => 'readings', 'count' => 10]), 21],
            ['dedicated_reader', 'Dedicated Reader', 'Complete 50 readings', '📖', 'engagement', json_encode(['type' => 'readings', 'count' => 50]), 22],
            ['faithful_student', 'Faithful Student', 'Complete 100 readings', '🎓', 'engagement', json_encode(['type' => 'readings', 'count' => 100]), 23],
            ['bible_scholar', 'Bible Scholar', 'Complete all 208 readings', '🏆', 'engagement', json_encode(['type' => 'readings', 'count' => 208]), 24],

            // Weekly Badges
            ['week_warrior', 'Week Warrior', 'Complete all 4 readings in one week', '⚔️', 'engagement', json_encode(['type' => 'week_complete', 'count' => 1]), 30],
            ['month_of_faith', 'Month of Faith', 'Complete 4 consecutive weeks', '📅', 'engagement', json_encode(['type' => 'consecutive_weeks', 'count' => 4]), 31],
            ['quarter_champion', 'Quarter Champion', 'Complete 13 consecutive weeks', '🏅', 'engagement', json_encode(['type' => 'consecutive_weeks', 'count' => 13]), 32],

            // Milestone Badges
            ['halfway_there', 'Halfway There', 'Reach 50% completion', '🎯', 'milestone', json_encode(['type' => 'percentage', 'value' => 50]), 40],
            ['almost_done', 'Almost Done', 'Reach 90% completion', '🚀', 'milestone', json_encode(['type' => 'percentage', 'value' => 90]), 41],
            ['finisher', 'Finisher', 'Complete the entire Bible in 52 weeks', '👑', 'milestone', json_encode(['type' => 'percentage', 'value' => 100]), 42],

            // Streak Badges
            ['on_fire', 'On Fire', '7-day reading streak', '🔥', 'streak', json_encode(['type' => 'streak_days', 'count' => 7]), 50],
            ['consistent', 'Consistent', '30-day reading streak', '💪', 'streak', json_encode(['type' => 'streak_days', 'count' => 30]), 51],
            ['devoted', 'Devoted', '100-day reading streak', '🌟', 'streak', json_encode(['type' => 'streak_days', 'count' => 100]), 52],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO badges (id, name, description, icon, category, criteria, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($badges as $badge) {
            $stmt->execute($badge);
        }
    }
}
