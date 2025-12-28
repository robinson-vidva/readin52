<?php
/**
 * Database class - SQLite PDO wrapper
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
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if database exists and is initialized
     */
    public static function isInstalled(): bool
    {
        if (!file_exists(DB_PATH)) {
            return false;
        }

        try {
            $pdo = self::getInstance();
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
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

        $schema = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT DEFAULT 'user' CHECK(role IN ('admin', 'user')),
                preferred_translation TEXT DEFAULT 'eng_kjv',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            );

            CREATE TABLE IF NOT EXISTS reading_progress (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                week_number INTEGER NOT NULL CHECK(week_number BETWEEN 1 AND 52),
                category TEXT NOT NULL CHECK(category IN ('poetry', 'history', 'prophecy', 'gospels')),
                completed INTEGER DEFAULT 0,
                completed_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, week_number, category)
            );

            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );

            CREATE TABLE IF NOT EXISTS login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_progress_user ON reading_progress(user_id);
            CREATE INDEX IF NOT EXISTS idx_progress_week ON reading_progress(week_number);
            CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
            CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email, attempted_at);
        ";

        $pdo->exec($schema);
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

        $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
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
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
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
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}
