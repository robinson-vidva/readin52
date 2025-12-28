<?php
/**
 * ReadIn52 - Main Router
 *
 * All requests are routed through this file.
 */

// Load configuration
require_once __DIR__ . '/config/config.php';

// Check if app is installed
if (!Database::isInstalled()) {
    // Redirect to install script if exists
    if (file_exists(__DIR__ . '/install.php')) {
        header('Location: /install.php');
        exit;
    }
    die('Application not installed. Please run install.php');
}

// Run database migrations (for updates)
Database::migrate();

// Start session
Auth::startSession();

// Get route
$route = trim($_GET['route'] ?? '', '/');
$method = $_SERVER['REQUEST_METHOD'];

// Route handling
try {
    switch ($route) {
        // ============ Public Routes ============

        case '':
        case 'home':
            if (Auth::isLoggedIn()) {
                redirect('/?route=dashboard');
            }
            render('home');
            break;

        case 'login':
            if (Auth::isLoggedIn()) {
                redirect('/?route=dashboard');
            }

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    render('login', ['error' => 'Invalid request. Please try again.']);
                    break;
                }

                $email = trim(post('email', ''));
                $password = post('password', '');

                $result = Auth::login($email, $password);

                if ($result['success']) {
                    setFlash('success', 'Welcome back!');
                    redirect('/?route=dashboard');
                } else {
                    render('login', [
                        'error' => $result['error'],
                        'email' => $email
                    ]);
                }
            } else {
                render('login');
            }
            break;

        case 'register':
            if (Auth::isLoggedIn()) {
                redirect('/?route=dashboard');
            }

            if (!Auth::isRegistrationEnabled()) {
                setFlash('error', 'Registration is currently disabled.');
                redirect('/?route=login');
            }

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    render('register', ['error' => 'Invalid request. Please try again.']);
                    break;
                }

                $name = trim(post('name', ''));
                $email = trim(post('email', ''));
                $password = post('password', '');
                $passwordConfirm = post('password_confirm', '');

                if ($password !== $passwordConfirm) {
                    render('register', [
                        'error' => 'Passwords do not match.',
                        'name' => $name,
                        'email' => $email
                    ]);
                    break;
                }

                $result = Auth::register($name, $email, $password);

                if ($result['success']) {
                    setFlash('success', 'Account created! Please sign in.');
                    redirect('/?route=login');
                } else {
                    render('register', [
                        'error' => $result['error'],
                        'name' => $name,
                        'email' => $email
                    ]);
                }
            } else {
                render('register');
            }
            break;

        case 'logout':
            Auth::logout();
            setFlash('success', 'You have been logged out.');
            redirect('/?route=login');
            break;

        // ============ Authenticated Routes ============

        case 'dashboard':
            Auth::requireAuth();
            render('dashboard');
            break;

        case 'profile':
            Auth::requireAuth();
            $data = [];

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    $data['error'] = 'Invalid request. Please try again.';
                } else {
                    $action = post('action', '');
                    $userId = Auth::getUserId();

                    if ($action === 'update_profile') {
                        $name = trim(post('name', ''));

                        if (User::update($userId, ['name' => $name])) {
                            $data['success'] = 'Profile updated successfully.';
                        } else {
                            $data['error'] = 'Failed to update profile.';
                        }
                    } elseif ($action === 'change_password') {
                        $currentPassword = post('current_password', '');
                        $newPassword = post('new_password', '');
                        $confirmPassword = post('confirm_password', '');

                        if (!User::verifyPassword($userId, $currentPassword)) {
                            $data['passwordError'] = 'Current password is incorrect.';
                        } elseif ($newPassword !== $confirmPassword) {
                            $data['passwordError'] = 'New passwords do not match.';
                        } elseif (strlen($newPassword) < 6) {
                            $data['passwordError'] = 'Password must be at least 6 characters.';
                        } elseif (User::updatePassword($userId, $newPassword)) {
                            $data['passwordSuccess'] = 'Password changed successfully.';
                        } else {
                            $data['passwordError'] = 'Failed to change password.';
                        }
                    }
                }
            }

            render('profile', $data);
            break;

        case 'settings':
            Auth::requireAuth();
            $data = [];

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    $data['error'] = 'Invalid request. Please try again.';
                } else {
                    $translation = post('preferred_translation', 'eng_kjv');
                    $theme = post('theme', 'auto');
                    // Validate theme value
                    if (!in_array($theme, ['light', 'dark', 'auto'])) {
                        $theme = 'auto';
                    }
                    $userId = Auth::getUserId();

                    if (User::update($userId, [
                        'preferred_translation' => $translation,
                        'theme' => $theme
                    ])) {
                        $data['success'] = 'Settings saved successfully.';
                    } else {
                        $data['error'] = 'Failed to save settings.';
                    }
                }
            }

            render('settings', $data);
            break;

        // ============ API Routes ============

        case 'api/progress':
            Auth::requireAuth();
            header('Content-Type: application/json');

            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $week = intval($input['week'] ?? 0);
                $category = $input['category'] ?? '';

                $result = Progress::toggleProgress(Auth::getUserId(), $week, $category);
                echo json_encode($result);
            } elseif ($method === 'GET') {
                $progress = Progress::getAllProgress(Auth::getUserId());
                echo json_encode(['success' => true, 'progress' => $progress]);
            }
            exit;

        case 'api/stats':
            Auth::requireAuth();
            header('Content-Type: application/json');
            $stats = Progress::getStats(Auth::getUserId());
            $chapterStats = Progress::getChapterStats(Auth::getUserId());
            echo json_encode(['success' => true, 'stats' => $stats, 'chapterStats' => $chapterStats]);
            exit;

        case 'api/chapter-progress':
            Auth::requireAuth();
            header('Content-Type: application/json');

            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $week = intval($input['week'] ?? 0);
                $category = $input['category'] ?? '';
                $book = $input['book'] ?? '';
                $chapter = intval($input['chapter'] ?? 0);

                $result = Progress::toggleChapter(Auth::getUserId(), $week, $category, $book, $chapter);

                // Also get updated week chapter counts
                if ($result['success']) {
                    $result['weekCounts'] = Progress::getWeekChapterCounts(Auth::getUserId(), $week);
                }

                echo json_encode($result);
            } elseif ($method === 'GET') {
                $week = intval($_GET['week'] ?? 0);
                if ($week > 0) {
                    $progress = Progress::getWeekChapterProgress(Auth::getUserId(), $week);
                    $counts = Progress::getWeekChapterCounts(Auth::getUserId(), $week);
                    echo json_encode(['success' => true, 'progress' => $progress, 'counts' => $counts]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Week required']);
                }
            }
            exit;

        // ============ Admin Routes ============

        case 'admin':
            Auth::requireAdmin();
            render('admin/dashboard');
            break;

        case 'admin/users':
            Auth::requireAdmin();
            $data = [];

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    $data['error'] = 'Invalid request.';
                } else {
                    $action = post('action', '');
                    $userId = intval(post('user_id', 0));

                    if ($action === 'update' && $userId) {
                        $updateData = [
                            'name' => trim(post('name', '')),
                            'email' => trim(post('email', '')),
                            'role' => post('role', 'user'),
                            'preferred_translation' => post('preferred_translation', 'eng_kjv')
                        ];

                        if (User::update($userId, $updateData)) {
                            $newPassword = post('new_password', '');
                            if ($newPassword && strlen($newPassword) >= 6) {
                                User::updatePassword($userId, $newPassword);
                            }
                            setFlash('success', 'User updated successfully.');
                        } else {
                            setFlash('error', 'Failed to update user.');
                        }
                        redirect('/?route=admin/users');
                    } elseif ($action === 'delete' && $userId) {
                        if ($userId === Auth::getUserId()) {
                            setFlash('error', 'You cannot delete your own account.');
                        } elseif (User::delete($userId)) {
                            setFlash('success', 'User deleted successfully.');
                        } else {
                            setFlash('error', 'Failed to delete user.');
                        }
                        redirect('/?route=admin/users');
                    }
                }
            }

            render('admin/users', $data);
            break;

        case 'admin/reading-plan':
            Auth::requireAdmin();
            $data = [];

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    $data['error'] = 'Invalid request.';
                } else {
                    $week = intval(post('week', 0));
                    $readings = post('readings', []);

                    if ($week >= 1 && $week <= 52) {
                        $plan = ReadingPlan::load();

                        foreach ($readings as $catId => $reading) {
                            $passages = json_decode($reading['passages'] ?? '[]', true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $plan['weeks'][$week - 1]['readings'][$catId] = [
                                    'reference' => $reading['reference'] ?? '',
                                    'passages' => $passages
                                ];
                            }
                        }

                        if (ReadingPlan::save($plan)) {
                            $data['success'] = "Week $week updated successfully.";
                        } else {
                            $data['error'] = 'Failed to save changes.';
                        }
                    }
                }
            }

            render('admin/reading-plan', $data);
            break;

        case 'admin/reading-plan/export':
            Auth::requireAdmin();
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="reading-plan-' . date('Y-m-d') . '.json"');
            echo ReadingPlan::export();
            exit;

        case 'admin/reading-plan/import':
            Auth::requireAdmin();

            if ($method === 'POST' && validateCsrf()) {
                if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
                    $json = file_get_contents($_FILES['json_file']['tmp_name']);
                    $result = ReadingPlan::import($json);

                    if ($result['success']) {
                        setFlash('success', 'Reading plan imported successfully.');
                    } else {
                        setFlash('error', $result['error']);
                    }
                } else {
                    setFlash('error', 'Please select a valid JSON file.');
                }
            }

            redirect('/?route=admin/reading-plan');
            break;

        case 'admin/settings':
            Auth::requireAdmin();
            $data = [];

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    $data['error'] = 'Invalid request.';
                } else {
                    $action = post('action', '');

                    if ($action === 'clear_progress') {
                        $pdo = Database::getInstance();
                        $pdo->exec('DELETE FROM reading_progress');
                        $data['success'] = 'All reading progress has been cleared.';
                    } elseif ($action === 'reset_settings') {
                        Database::insertDefaultSettings();
                        $data['success'] = 'Settings have been reset to defaults.';
                    } else {
                        // Update settings
                        Database::setSetting('app_name', trim(post('app_name', 'ReadIn52')));
                        Database::setSetting('default_translation', post('default_translation', 'eng_kjv'));
                        Database::setSetting('registration_enabled', post('registration_enabled', '0') ? '1' : '0');
                        $data['success'] = 'Settings saved successfully.';
                    }
                }
            }

            render('admin/settings', $data);
            break;

        // ============ Reader Routes ============

        default:
            // Check for reader route pattern: reader/{book}/{chapter}
            if (preg_match('#^reader/([A-Z0-9]+)/(\d+)$#', $route, $matches)) {
                Auth::requireAuth();
                render('reader', [
                    'book' => $matches[1],
                    'chapter' => intval($matches[2])
                ]);
                break;
            }

            // Check for API week route: api/week/{n}
            if (preg_match('#^api/week/(\d+)$#', $route, $matches)) {
                Auth::requireAuth();
                header('Content-Type: application/json');
                $weekNum = intval($matches[1]);
                $week = ReadingPlan::getWeekWithDetails($weekNum);
                $progress = Progress::getWeekProgress(Auth::getUserId(), $weekNum);
                echo json_encode([
                    'success' => true,
                    'week' => $week,
                    'progress' => $progress
                ]);
                exit;
            }

            // 404 Not Found
            http_response_code(404);
            echo '<!DOCTYPE html>
            <html>
            <head><title>404 - Not Found</title>
            <style>
                body { font-family: sans-serif; text-align: center; padding: 50px; }
                h1 { color: #5D4037; }
                a { color: #1565C0; }
            </style>
            </head>
            <body>
                <h1>404 - Page Not Found</h1>
                <p>The page you are looking for does not exist.</p>
                <p><a href="/">Return to Home</a></p>
            </body>
            </html>';
            break;
    }
} catch (Exception $e) {
    // Log error
    error_log('ReadIn52 Error: ' . $e->getMessage());

    // Show error page
    http_response_code(500);
    echo '<!DOCTYPE html>
    <html>
    <head><title>Error</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        h1 { color: #D32F2F; }
        a { color: #1565C0; }
    </style>
    </head>
    <body>
        <h1>Something went wrong</h1>
        <p>We encountered an error processing your request.</p>
        <p><a href="/">Return to Home</a></p>
    </body>
    </html>';
}
