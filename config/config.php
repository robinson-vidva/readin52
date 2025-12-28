<?php
/**
 * ReadIn52 Configuration
 *
 * For Cloudways deployment - files deployed directly to public_html
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);

// Application settings
define('APP_NAME', 'ReadIn52');
define('APP_TAGLINE', 'Journey Through Scripture in 52 Weeks');
define('APP_VERSION', '1.0.0');

// Paths - All relative to document root (public_html)
define('ROOT_PATH', __DIR__ . '/..');
define('CONFIG_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('SRC_PATH', ROOT_PATH . '/src');
define('TEMPLATE_PATH', ROOT_PATH . '/templates');
define('PUBLIC_PATH', ROOT_PATH);

// ============================================================
// DATABASE CONFIGURATION (MySQL/MariaDB)
// ============================================================
// Update these values with your Cloudways database credentials
// Found in: Cloudways > Application > Access Details > Database
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');     // e.g., abcdefghij
define('DB_USER', 'your_database_user');     // e.g., abcdefghij
define('DB_PASS', 'your_database_password'); // From Cloudways panel
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// Bible API
define('BIBLE_API_BASE', 'https://bible.helloao.org/api');
define('DEFAULT_TRANSLATION', 'eng_kjv');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('LOGIN_RATE_LIMIT', 5); // attempts
define('LOGIN_RATE_WINDOW', 900); // 15 minutes in seconds

// Timezone
date_default_timezone_set('UTC');

// Autoload classes
spl_autoload_register(function ($class) {
    $file = SRC_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load helpers
require_once SRC_PATH . '/helpers.php';
