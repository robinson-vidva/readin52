<?php
/**
 * Local Configuration File
 *
 * Copy this file to config.local.php and update with your actual values.
 * The config.local.php file is ignored by git, so your credentials won't
 * be overwritten on deploy.
 *
 * IMPORTANT: After copying, rename to config.local.php
 */

// ============================================================
// DATABASE CONFIGURATION (MySQL/MariaDB)
// ============================================================
// Get these from: Cloudways > Application > Access Details > Database
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');     // e.g., abcdefghij
define('DB_USER', 'your_database_user');     // e.g., abcdefghij
define('DB_PASS', 'your_database_password'); // From Cloudways panel
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// OPTIONAL: Override other settings if needed
// ============================================================
// define('APP_NAME', 'ReadIn52');
// define('DEFAULT_TRANSLATION', 'eng_kjv');
