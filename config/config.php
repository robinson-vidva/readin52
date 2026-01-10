<?php
/**
 * ReadIn52 Configuration
 *
 * For Cloudways deployment - files deployed directly to public_html
 *
 * DATABASE SETUP:
 * 1. Copy db.example.php to db.php
 * 2. Update db.php with your Cloudways database credentials
 * 3. db.php is gitignored and won't be overwritten on deploy
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent Varnish/proxy caching of dynamic pages
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('X-Varnish-Bypass: 1');
header('Vary: Cookie');

// Detect HTTPS (Cloudways uses proxy, check X-Forwarded-Proto)
$isHttps = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
);

// Session configuration - must be set before session_start()
// Note: strict_mode disabled for Cloudways compatibility (causes session data loss)
ini_set('session.use_strict_mode', 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Also set via session_set_cookie_params for Cloudways compatibility
session_set_cookie_params([
    'lifetime' => 0,           // Session cookie (expires when browser closes)
    'path' => '/',
    'domain' => '',            // Current domain only
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session early to ensure consistent behavior
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'ReadIn52');
define('APP_TAGLINE', 'Journey Through Scripture in 52 Weeks');
define('APP_VERSION', '1.14.0');

// Paths - All relative to document root (public_html)
define('ROOT_PATH', __DIR__ . '/..');
define('CONFIG_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('SRC_PATH', ROOT_PATH . '/src');
define('TEMPLATE_PATH', ROOT_PATH . '/templates');
define('PUBLIC_PATH', ROOT_PATH);

// ============================================================
// LOAD DATABASE CREDENTIALS
// ============================================================
// If db.php exists, load it (contains your DB credentials)
// This file is gitignored so it won't be overwritten on deploy
// ============================================================
$dbConfigPath = __DIR__ . '/db.php';
if (file_exists($dbConfigPath)) {
    require_once $dbConfigPath;
}

// ============================================================
// DEFAULT DATABASE SETTINGS (if not set in db.php)
// ============================================================
// These are placeholders - create db.php with real values
// ============================================================
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'your_database_name');
if (!defined('DB_USER')) define('DB_USER', 'your_database_user');
if (!defined('DB_PASS')) define('DB_PASS', 'your_database_password');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ============================================================
// LOAD EMAIL CREDENTIALS (Brevo)
// ============================================================
// If email.php exists, load it (contains your Brevo API key)
// This file is gitignored so it won't be overwritten on deploy
// ============================================================
$emailConfigPath = __DIR__ . '/email.php';
if (file_exists($emailConfigPath)) {
    require_once $emailConfigPath;
}

// Bible API
define('BIBLE_API_BASE', 'https://bible.helloao.org/api');
if (!defined('DEFAULT_TRANSLATION')) define('DEFAULT_TRANSLATION', 'eng_kjv');

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
