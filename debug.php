<?php
/**
 * ReadIn52 Debug Script
 *
 * Use this to diagnose session/database issues.
 * DELETE THIS FILE after debugging!
 */

// Load configuration
require_once __DIR__ . '/config/config.php';

echo "<h1>ReadIn52 Diagnostics</h1>\n";
echo "<pre>\n";

// 1. PHP Version
echo "== PHP Version ==\n";
echo "PHP " . phpversion() . "\n\n";

// 2. Session Info
echo "== Session Configuration ==\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.save_path: " . (ini_get('session.save_path') ?: '(default)') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "\n\n";

// 3. HTTPS Detection
echo "== HTTPS Detection ==\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "\n";
echo "REQUEST_SCHEME: " . ($_SERVER['REQUEST_SCHEME'] ?? 'not set') . "\n";

$isHttps = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
);
echo "Detected as HTTPS: " . ($isHttps ? 'YES' : 'NO') . "\n\n";

// 4. Session Test
echo "== Session Test ==\n";
Auth::startSession();
echo "Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . " (2 = active)\n";

// Check if session directory is writable
$savePath = session_save_path() ?: sys_get_temp_dir();
echo "Session save path writable: " . (is_writable($savePath) ? 'YES' : 'NO') . "\n";

// Test session persistence
if (!isset($_SESSION['debug_visit_count'])) {
    $_SESSION['debug_visit_count'] = 0;
}
$_SESSION['debug_visit_count']++;
echo "Visit count (should increase on refresh): " . $_SESSION['debug_visit_count'] . "\n";

// CSRF token test
$csrfToken = Auth::generateCsrfToken();
echo "CSRF Token generated: " . substr($csrfToken, 0, 20) . "...\n";
echo "CSRF Token in session: " . (isset($_SESSION[CSRF_TOKEN_NAME]) ? 'YES' : 'NO') . "\n\n";

// 5. Database Connection Test
echo "== Database Connection ==\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_PASS: " . (DB_PASS === 'your_database_password' ? '(NOT CONFIGURED!)' : '****') . "\n\n";

try {
    $pdo = Database::getInstance();
    echo "Database connection: SUCCESS\n";

    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "\n";

    // Check users
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "User count: $count\n";
    }

    echo "\nDatabase::isInstalled() = " . (Database::isInstalled() ? 'true' : 'false') . "\n";

} catch (Exception $e) {
    echo "Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n== Cookie Info ==\n";
echo "Cookies received: " . (count($_COOKIE) ? implode(', ', array_keys($_COOKIE)) : 'none') . "\n";

echo "\n== Summary ==\n";
$issues = [];

if (DB_PASS === 'your_database_password') {
    $issues[] = "Database credentials NOT configured! Create config/db.php with your credentials.";
}

if ($isHttps && ini_get('session.cookie_secure') != 1) {
    $issues[] = "HTTPS detected but session.cookie_secure is not set";
}

if ($_SESSION['debug_visit_count'] == 1 && isset($_COOKIE)) {
    // First visit after cookie, might be issue
    if (count($_COOKIE) === 0) {
        $issues[] = "No cookies received - session might not persist";
    }
}

if (empty($issues)) {
    echo "No obvious issues detected. Refresh this page - visit count should increase.\n";
    echo "If visit count stays at 1, sessions are not persisting.\n";
} else {
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
}

echo "</pre>\n";

// Simple CSRF form test
echo "<h2>CSRF Form Test</h2>\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';

    echo "<p>Submitted token: " . htmlspecialchars(substr($submittedToken, 0, 20)) . "...</p>\n";
    echo "<p>Session token: " . htmlspecialchars(substr($sessionToken, 0, 20)) . "...</p>\n";

    if (Auth::verifyCsrfToken($submittedToken)) {
        echo "<p style='color:green;font-weight:bold;'>CSRF validation: SUCCESS!</p>\n";
    } else {
        echo "<p style='color:red;font-weight:bold;'>CSRF validation: FAILED</p>\n";
        echo "<p>This is likely the cause of your login issues.</p>\n";
        if ($submittedToken !== $sessionToken) {
            echo "<p>Tokens don't match - session may not be persisting between requests.</p>\n";
        }
    }
}

echo "<form method='POST'>\n";
echo csrfField();
echo "<button type='submit'>Test CSRF Validation</button>\n";
echo "</form>\n";

echo "<p style='color:red;'><strong>DELETE THIS FILE (debug.php) after debugging!</strong></p>\n";
