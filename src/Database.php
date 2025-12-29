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

        // Seed additional Bible translations
        self::seedBibleTranslations();
    }

    /**
     * Seed comprehensive list of Bible translations from HelloAO API
     */
    public static function seedBibleTranslations(): void
    {
        $pdo = self::getInstance();

        // Check if we already have many translations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM bible_translations");
        $count = (int) $stmt->fetch()['count'];
        if ($count > 20) {
            return; // Already seeded
        }

        $translations = [
            // English Translations
            ['id' => 'BSB', 'name' => 'Berean Standard Bible', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'eng_kjv', 'name' => 'King James Version', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engkjvcpb', 'name' => 'King James Version (Cambridge)', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'eng_ukjv', 'name' => 'Updated King James Version', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engwebpb', 'name' => 'World English Bible', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engwebbe', 'name' => 'World English Bible British Edition', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engwebme', 'name' => 'World English Bible Messianic Edition', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engwmb', 'name' => 'World Messianic Bible', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engwmbb', 'name' => 'World Messianic Bible British Edition', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engoebcw', 'name' => 'Open English Bible (Commonwealth)', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engoebcus', 'name' => 'Open English Bible (US)', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engasv', 'name' => 'American Standard Version', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engDBY', 'name' => 'Darby Bible', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engYLT', 'name' => "Young's Literal Translation", 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engWEB', 'name' => 'Webster Bible', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engBBE', 'name' => 'Bible in Basic English', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engt4t', 'name' => 'Translation for Translators', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engULB', 'name' => 'Unlocked Literal Bible', 'language' => 'English', 'direction' => 'ltr'],
            ['id' => 'engUDB', 'name' => 'Unlocked Dynamic Bible', 'language' => 'English', 'direction' => 'ltr'],

            // Spanish Translations
            ['id' => 'sparvg', 'name' => 'Reina Valera Gómez', 'language' => 'Spanish', 'direction' => 'ltr'],
            ['id' => 'sparv1909', 'name' => 'Reina Valera 1909', 'language' => 'Spanish', 'direction' => 'ltr'],
            ['id' => 'spablh', 'name' => 'Biblia Libre para el Mundo', 'language' => 'Spanish', 'direction' => 'ltr'],
            ['id' => 'spasev', 'name' => 'Spanish Free Bible Version', 'language' => 'Spanish', 'direction' => 'ltr'],

            // Portuguese Translations
            ['id' => 'poracf', 'name' => 'Almeida Corrigida Fiel', 'language' => 'Portuguese', 'direction' => 'ltr'],
            ['id' => 'porara', 'name' => 'Almeida Revista e Atualizada', 'language' => 'Portuguese', 'direction' => 'ltr'],
            ['id' => 'porblivre', 'name' => 'Bíblia Livre', 'language' => 'Portuguese', 'direction' => 'ltr'],

            // French Translations
            ['id' => 'fraLSG', 'name' => 'Louis Segond 1910', 'language' => 'French', 'direction' => 'ltr'],
            ['id' => 'fraPDV', 'name' => 'Parole de Vie', 'language' => 'French', 'direction' => 'ltr'],
            ['id' => 'frafob', 'name' => 'French Ostervald', 'language' => 'French', 'direction' => 'ltr'],
            ['id' => 'fradby', 'name' => 'French Darby', 'language' => 'French', 'direction' => 'ltr'],

            // German Translations
            ['id' => 'deuelo', 'name' => 'Elberfelder 1905', 'language' => 'German', 'direction' => 'ltr'],
            ['id' => 'deulut1912', 'name' => 'Luther 1912', 'language' => 'German', 'direction' => 'ltr'],
            ['id' => 'deumeng', 'name' => 'Menge Bibel', 'language' => 'German', 'direction' => 'ltr'],
            ['id' => 'deusch2000', 'name' => 'Schlachter 2000', 'language' => 'German', 'direction' => 'ltr'],

            // Chinese Translations
            ['id' => 'cmncuvs', 'name' => 'Chinese Union Version Simplified', 'language' => 'Chinese', 'direction' => 'ltr'],
            ['id' => 'cmncuvt', 'name' => 'Chinese Union Version Traditional', 'language' => 'Chinese', 'direction' => 'ltr'],
            ['id' => 'cmnclv', 'name' => 'Chinese Literal Version', 'language' => 'Chinese', 'direction' => 'ltr'],

            // Korean Translations
            ['id' => 'korKRV', 'name' => 'Korean Revised Version', 'language' => 'Korean', 'direction' => 'ltr'],
            ['id' => 'korHKJV', 'name' => 'Korean KJV', 'language' => 'Korean', 'direction' => 'ltr'],

            // Russian Translations
            ['id' => 'russynod', 'name' => 'Russian Synodal', 'language' => 'Russian', 'direction' => 'ltr'],
            ['id' => 'ruscar', 'name' => 'Russian Carpatho-Rusyn', 'language' => 'Russian', 'direction' => 'ltr'],

            // Arabic Translations
            ['id' => 'arbvdab', 'name' => 'Arabic Bible Van Dyck', 'language' => 'Arabic', 'direction' => 'rtl'],
            ['id' => 'arbnav', 'name' => 'Arabic New Arabic Version', 'language' => 'Arabic', 'direction' => 'rtl'],

            // Hebrew Translations
            ['id' => 'hebmod', 'name' => 'Hebrew Modern', 'language' => 'Hebrew', 'direction' => 'rtl'],
            ['id' => 'hebSPMT', 'name' => 'Hebrew Samaritan Pentateuch', 'language' => 'Hebrew', 'direction' => 'rtl'],

            // Greek Translations
            ['id' => 'grctr', 'name' => 'Greek Textus Receptus', 'language' => 'Greek', 'direction' => 'ltr'],
            ['id' => 'grcsblgnt', 'name' => 'SBL Greek New Testament', 'language' => 'Greek', 'direction' => 'ltr'],

            // Latin Translations
            ['id' => 'latvul', 'name' => 'Latin Vulgate', 'language' => 'Latin', 'direction' => 'ltr'],
            ['id' => 'latclem', 'name' => 'Clementine Vulgate', 'language' => 'Latin', 'direction' => 'ltr'],

            // Italian Translations
            ['id' => 'itariveduta', 'name' => 'Italian Riveduta', 'language' => 'Italian', 'direction' => 'ltr'],
            ['id' => 'itanuoriveduta', 'name' => 'Italian Nuova Riveduta', 'language' => 'Italian', 'direction' => 'ltr'],
            ['id' => 'itadiodati', 'name' => 'Italian Diodati', 'language' => 'Italian', 'direction' => 'ltr'],

            // Dutch Translations
            ['id' => 'nldHTB', 'name' => 'Dutch Het Boek', 'language' => 'Dutch', 'direction' => 'ltr'],
            ['id' => 'nldsv', 'name' => 'Dutch Staten Vertaling', 'language' => 'Dutch', 'direction' => 'ltr'],

            // Polish Translations
            ['id' => 'polGdanska', 'name' => 'Polish Gdańska', 'language' => 'Polish', 'direction' => 'ltr'],
            ['id' => 'polUBG', 'name' => 'Polish UBG', 'language' => 'Polish', 'direction' => 'ltr'],

            // Romanian Translations
            ['id' => 'roncor', 'name' => 'Romanian Cornilescu', 'language' => 'Romanian', 'direction' => 'ltr'],
            ['id' => 'ronvdc', 'name' => 'Romanian VDC', 'language' => 'Romanian', 'direction' => 'ltr'],

            // Japanese Translations
            ['id' => 'jpnkou', 'name' => 'Japanese Kougo-yaku', 'language' => 'Japanese', 'direction' => 'ltr'],

            // Vietnamese Translations
            ['id' => 'vievnt', 'name' => 'Vietnamese 1934', 'language' => 'Vietnamese', 'direction' => 'ltr'],

            // Filipino Translations
            ['id' => 'tagalog', 'name' => 'Tagalog Ang Biblia', 'language' => 'Filipino', 'direction' => 'ltr'],
            ['id' => 'tglMBB', 'name' => 'Tagalog MBB', 'language' => 'Filipino', 'direction' => 'ltr'],

            // Hindi Translations
            ['id' => 'hinirv', 'name' => 'Hindi IRV', 'language' => 'Hindi', 'direction' => 'ltr'],

            // Indonesian Translations
            ['id' => 'indtb', 'name' => 'Indonesian Terjemahan Baru', 'language' => 'Indonesian', 'direction' => 'ltr'],

            // Swahili Translations
            ['id' => 'swaulb', 'name' => 'Swahili Union Version', 'language' => 'Swahili', 'direction' => 'ltr'],

            // Tamil Translations
            ['id' => 'tamirv', 'name' => 'Tamil IRV', 'language' => 'Tamil', 'direction' => 'ltr'],

            // Telugu Translations
            ['id' => 'telirv', 'name' => 'Telugu IRV', 'language' => 'Telugu', 'direction' => 'ltr'],

            // Ukrainian Translations
            ['id' => 'ukr', 'name' => 'Ukrainian Bible', 'language' => 'Ukrainian', 'direction' => 'ltr'],

            // Afrikaans Translations
            ['id' => 'afrafr83', 'name' => 'Afrikaans 1983', 'language' => 'Afrikaans', 'direction' => 'ltr'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO bible_translations (id, name, language, direction) VALUES (?, ?, ?, ?)");
        foreach ($translations as $trans) {
            $stmt->execute([
                $trans['id'],
                $trans['name'],
                $trans['language'],
                $trans['direction']
            ]);
        }
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
