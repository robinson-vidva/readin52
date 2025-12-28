<?php
/**
 * ReadIn52 Installation Script
 *
 * Run this script once to set up the database and create default users.
 * DELETE THIS FILE after installation for security.
 */

// Prevent direct access in production
if (php_sapi_name() !== 'cli' && !isset($_GET['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Install ReadIn52</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #5D4037 0%, #3E2723 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 100%;
            }
            h1 { color: #5D4037; margin-bottom: 10px; }
            .tagline { color: #666; margin-bottom: 30px; }
            .warning {
                background: #FFF3E0;
                border: 1px solid #FFB300;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                color: #E65100;
            }
            .info {
                background: #E3F2FD;
                border: 1px solid #1565C0;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                color: #0D47A1;
            }
            .btn {
                display: inline-block;
                background: #5D4037;
                color: white;
                padding: 12px 30px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: background 0.3s;
            }
            .btn:hover { background: #3E2723; }
            ul { margin: 15px 0; padding-left: 20px; }
            li { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>ReadIn52 Installation</h1>
            <p class="tagline">Journey Through Scripture in 52 Weeks</p>

            <div class="warning">
                <strong>Warning:</strong> This will create the database and default users.
                Delete this file after installation!
            </div>

            <div class="info">
                <strong>Default accounts will be created:</strong>
                <ul>
                    <li><strong>Admin:</strong> admin@readin52.app / Admin@123</li>
                    <li><strong>Test User:</strong> testuser@readin52.app / Test@123</li>
                </ul>
            </div>

            <p style="margin-bottom: 20px;">Click the button below to start installation:</p>

            <a href="?confirm=1" class="btn">Install ReadIn52</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Run installation
require_once __DIR__ . '/config/config.php';

$messages = [];
$errors = [];

try {
    // Step 1: Create data directory
    if (!is_dir(DATA_PATH)) {
        if (mkdir(DATA_PATH, 0755, true)) {
            $messages[] = 'Created data directory';
        } else {
            $errors[] = 'Failed to create data directory';
        }
    } else {
        $messages[] = 'Data directory already exists';
    }

    // Step 2: Check if database already exists
    if (file_exists(DB_PATH)) {
        $messages[] = 'Database already exists - checking tables...';
    }

    // Step 3: Initialize database schema
    Database::initialize();
    $messages[] = 'Database schema created/verified';

    // Step 4: Insert default settings
    Database::insertDefaultSettings();
    $messages[] = 'Default settings inserted';

    // Step 5: Create admin user
    $adminExists = User::findByEmail('admin@readin52.app');
    if (!$adminExists) {
        $adminId = User::create('Administrator', 'admin@readin52.app', 'Admin@123', 'admin');
        if ($adminId) {
            $messages[] = 'Admin user created (admin@readin52.app / Admin@123)';
        } else {
            $errors[] = 'Failed to create admin user';
        }
    } else {
        $messages[] = 'Admin user already exists';
    }

    // Step 6: Create test user
    $testUserExists = User::findByEmail('testuser@readin52.app');
    if (!$testUserExists) {
        $testUserId = User::create('Test User', 'testuser@readin52.app', 'Test@123', 'user');
        if ($testUserId) {
            $messages[] = 'Test user created (testuser@readin52.app / Test@123)';
        } else {
            $errors[] = 'Failed to create test user';
        }
    } else {
        $messages[] = 'Test user already exists';
    }

    // Step 7: Create .gitkeep in data folder
    $gitkeepPath = DATA_PATH . '/.gitkeep';
    if (!file_exists($gitkeepPath)) {
        file_put_contents($gitkeepPath, '');
        $messages[] = 'Created .gitkeep in data directory';
    }

    $success = empty($errors);

} catch (Exception $e) {
    $errors[] = 'Installation error: ' . $e->getMessage();
    $success = false;
}

// Display results
if (php_sapi_name() === 'cli') {
    echo "\n=== ReadIn52 Installation ===\n\n";

    foreach ($messages as $msg) {
        echo "[OK] $msg\n";
    }

    foreach ($errors as $err) {
        echo "[ERROR] $err\n";
    }

    echo "\n";
    echo $success ? "Installation completed successfully!\n" : "Installation completed with errors.\n";
    echo "\nIMPORTANT: Delete install.php for security!\n\n";

} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation <?php echo $success ? 'Complete' : 'Failed'; ?> - ReadIn52</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #5D4037 0%, #3E2723 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
            }
            h1 { color: <?php echo $success ? '#43A047' : '#D32F2F'; ?>; margin-bottom: 20px; }
            .message {
                padding: 10px 15px;
                margin: 5px 0;
                border-radius: 6px;
                display: flex;
                align-items: center;
            }
            .message.success { background: #E8F5E9; color: #2E7D32; }
            .message.error { background: #FFEBEE; color: #C62828; }
            .message::before { margin-right: 10px; font-weight: bold; }
            .message.success::before { content: '✓'; }
            .message.error::before { content: '✗'; }
            .warning {
                background: #FFF3E0;
                border: 1px solid #FFB300;
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
                color: #E65100;
            }
            .btn {
                display: inline-block;
                background: #5D4037;
                color: white;
                padding: 12px 30px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                margin-top: 20px;
                transition: background 0.3s;
            }
            .btn:hover { background: #3E2723; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?php echo $success ? 'Installation Complete!' : 'Installation Failed'; ?></h1>

            <?php foreach ($messages as $msg): ?>
                <div class="message success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>

            <?php foreach ($errors as $err): ?>
                <div class="message error"><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>

            <?php if ($success): ?>
                <div class="warning">
                    <strong>IMPORTANT:</strong> Delete this install.php file immediately for security!
                </div>

                <a href="/" class="btn">Go to ReadIn52</a>
            <?php else: ?>
                <div class="warning">
                    <strong>Note:</strong> Please fix the errors above and try again.
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
