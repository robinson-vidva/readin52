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
                $acceptTerms = post('accept_terms', '');

                if (!$acceptTerms) {
                    render('register', [
                        'error' => 'You must accept the Terms & Conditions to create an account.',
                        'name' => $name,
                        'email' => $email
                    ]);
                    break;
                }

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
            redirect('/');
            break;

        case 'privacy':
            render('privacy');
            break;

        case 'terms':
            render('terms');
            break;

        case 'about':
            render('about');
            break;

        case 'forgot-password':
            if (Auth::isLoggedIn()) {
                redirect('/?route=dashboard');
            }

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    render('forgot-password', ['error' => 'Invalid request. Please try again.']);
                    break;
                }

                $email = trim(post('email', ''));

                // Always show success message to prevent email enumeration
                $successMessage = 'If an account exists with this email, you will receive a password reset link shortly.';

                if (!empty($email)) {
                    $resetData = User::createPasswordResetToken($email);
                    if ($resetData && Email::isConfigured()) {
                        Email::sendPasswordReset(
                            $resetData['user']['email'],
                            $resetData['user']['name'],
                            $resetData['token']
                        );
                    }
                }

                render('forgot-password', ['success' => $successMessage]);
            } else {
                render('forgot-password');
            }
            break;

        case 'reset-password':
            if (Auth::isLoggedIn()) {
                redirect('/?route=dashboard');
            }

            $token = $_GET['token'] ?? post('token', '');

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    render('reset-password', ['error' => 'Invalid request. Please try again.', 'validToken' => false]);
                    break;
                }

                $password = post('password', '');
                $passwordConfirm = post('password_confirm', '');

                if (strlen($password) < 6) {
                    render('reset-password', [
                        'error' => 'Password must be at least 6 characters.',
                        'validToken' => true,
                        'token' => $token
                    ]);
                } elseif ($password !== $passwordConfirm) {
                    render('reset-password', [
                        'error' => 'Passwords do not match.',
                        'validToken' => true,
                        'token' => $token
                    ]);
                } elseif (User::resetPasswordWithToken($token, $password)) {
                    render('reset-password', ['success' => 'Your password has been reset successfully. You can now sign in.']);
                } else {
                    render('reset-password', ['error' => 'This reset link is invalid or has expired.', 'validToken' => false]);
                }
            } else {
                $resetData = User::validatePasswordResetToken($token);
                render('reset-password', [
                    'validToken' => $resetData !== null,
                    'token' => $token
                ]);
            }
            break;

        case 'verify-email':
            $token = $_GET['token'] ?? '';
            $result = User::completeEmailChange($token);

            if ($result) {
                setFlash('success', 'Your email has been changed to ' . $result['new_email']);
                // If logged in, refresh session
                if (Auth::isLoggedIn() && Auth::getUserId() === $result['user_id']) {
                    // User session will pick up new email on next page load
                }
            } else {
                setFlash('error', 'This verification link is invalid or has expired.');
            }

            redirect(Auth::isLoggedIn() ? '/?route=settings' : '/?route=login');
            break;

        // ============ Authenticated Routes ============

        case 'dashboard':
            Auth::requireAuth();
            render('dashboard');
            break;

        case 'profile':
            Auth::requireAuth();
            render('profile');
            break;

        case 'books':
            Auth::requireAuth();
            render('books');
            break;

        case 'notes':
            Auth::requireAuth();
            render('notes');
            break;

        case 'notes/save':
            Auth::requireAuth();
            if ($method === 'POST') {
                // Check if AJAX request - look for ajax parameter or XHR header
                $isAjax = isAjax() || post('ajax') === '1' || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

                if (!validateCsrf()) {
                    if ($isAjax) {
                        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
                    }
                    setFlash('error', 'Invalid request.');
                    redirect('/?route=dashboard');
                }

                $noteId = post('note_id', '');
                $data = [
                    'title' => post('title', ''),
                    'content' => post('content', ''),
                    'color' => post('color', 'default'),
                    'week_number' => post('week_number', ''),
                    'category' => post('category', ''),
                    'book' => post('book', ''),
                    'chapter' => post('chapter', ''),
                ];

                try {
                    if ($noteId) {
                        Note::update((int) $noteId, Auth::getUserId(), $data);
                        $message = 'Note updated.';
                    } else {
                        Note::create(Auth::getUserId(), $data);
                        $message = 'Note created.';
                    }

                    if ($isAjax) {
                        jsonResponse(['success' => true, 'message' => $message]);
                    }
                    setFlash('success', $message);
                } catch (Exception $e) {
                    if ($isAjax) {
                        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
                    }
                    setFlash('error', 'Failed to save note.');
                }
            }
            // Redirect back - form submission reloads page in reader
            redirect('/?route=dashboard');
            break;

        case 'notes/delete':
            Auth::requireAuth();
            header('Content-Type: application/json');
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
                    exit;
                }
                // Validate CSRF token
                $csrfToken = $input['csrf_token'] ?? '';
                if (!Auth::verifyCsrfToken($csrfToken)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                    exit;
                }
                $noteId = (int) ($input['note_id'] ?? 0);
                if ($noteId && Note::delete($noteId, Auth::getUserId())) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid method']);
            }
            exit;

        case 'settings':
            Auth::requireAuth();
            $data = [];

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    $data['error'] = 'Invalid request. Please try again.';
                } else {
                    $action = post('action', 'save_preferences');
                    $userId = Auth::getUserId();

                    if ($action === 'update_name') {
                        $name = trim(post('name', ''));
                        if (empty($name)) {
                            $data['nameError'] = 'Name is required.';
                        } elseif (User::update($userId, ['name' => $name])) {
                            $data['nameSuccess'] = 'Name updated successfully.';
                        } else {
                            $data['nameError'] = 'Failed to update name.';
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
                    } elseif ($action === 'change_email') {
                        $newEmail = trim(post('new_email', ''));
                        $password = post('password', '');
                        $currentUser = Auth::getUser();

                        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                            $data['emailError'] = 'Please enter a valid email address.';
                        } elseif ($newEmail === $currentUser['email']) {
                            $data['emailError'] = 'New email is the same as your current email.';
                        } elseif (!User::verifyPassword($userId, $password)) {
                            $data['emailError'] = 'Incorrect password.';
                        } else {
                            $verifyData = User::createEmailVerificationToken($userId, $newEmail);
                            if (!$verifyData) {
                                $data['emailError'] = 'This email address is already in use.';
                            } elseif (!Email::isConfigured()) {
                                $data['emailError'] = 'Email service is not configured. Please contact support.';
                            } else {
                                $result = Email::sendEmailVerification($newEmail, $currentUser['name'], $verifyData['token']);
                                if ($result['success']) {
                                    $data['emailSuccess'] = 'Verification email sent to ' . e($newEmail) . '. Please check your inbox.';
                                } else {
                                    $data['emailError'] = 'Failed to send verification email. Please try again.';
                                }
                            }
                        }
                    } else {
                        // Default: save preferences (theme, translations)
                        $translation = post('preferred_translation', 'eng_kjv');
                        $secondaryTranslation = post('secondary_translation', '');
                        $theme = post('theme', 'auto');

                        // Validate theme value
                        if (!in_array($theme, ['light', 'dark', 'auto'])) {
                            $theme = 'auto';
                        }

                        // Set secondary to null if empty or same as primary
                        if ($secondaryTranslation === '' || $secondaryTranslation === $translation) {
                            $secondaryTranslation = null;
                        }

                        if (User::update($userId, [
                            'preferred_translation' => $translation,
                            'secondary_translation' => $secondaryTranslation,
                            'theme' => $theme
                        ])) {
                            $data['prefsSuccess'] = 'Preferences saved successfully.';
                        } else {
                            $data['prefsError'] = 'Failed to save preferences.';
                        }
                    }
                }
            }

            render('settings', $data);
            break;

        case 'settings/reset-progress':
            Auth::requireAuth();

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    setFlash('error', 'Invalid request. Please try again.');
                } else {
                    $password = post('password', '');
                    $userId = Auth::getUserId();

                    if (!User::verifyPassword($userId, $password)) {
                        setFlash('error', 'Incorrect password.');
                    } elseif (Progress::deleteAllProgress($userId)) {
                        setFlash('success', 'Your reading progress has been reset.');
                    } else {
                        setFlash('error', 'Failed to reset progress. Please try again.');
                    }
                }
            }

            redirect('/?route=settings');
            break;

        case 'settings/delete-account':
            Auth::requireAuth();

            if ($method === 'POST') {
                if (!validateCsrf()) {
                    setFlash('error', 'Invalid request. Please try again.');
                } else {
                    $password = post('password', '');
                    $userId = Auth::getUserId();

                    if (!User::verifyPassword($userId, $password)) {
                        setFlash('error', 'Incorrect password.');
                        redirect('/?route=settings');
                    } elseif (User::delete($userId)) {
                        Auth::logout();
                        setFlash('success', 'Your account has been deleted.');
                        redirect('/?route=login');
                    } else {
                        setFlash('error', 'Failed to delete account. Please try again.');
                        redirect('/?route=settings');
                    }
                }
            }

            redirect('/?route=settings');
            break;

        // ============ API Routes ============

        case 'api/progress':
            Auth::requireAuth();
            header('Content-Type: application/json');

            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
                    exit;
                }
                // Validate CSRF token
                $csrfToken = $input['csrf_token'] ?? '';
                if (!Auth::verifyCsrfToken($csrfToken)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                    exit;
                }
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
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
                    exit;
                }
                // Validate CSRF token
                $csrfToken = $input['csrf_token'] ?? '';
                if (!Auth::verifyCsrfToken($csrfToken)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                    exit;
                }
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

        // ============ Notes API ============
        // Handled in default section for dynamic route matching

        // ============ Admin Routes ============

        case 'admin':
            Auth::requireAdmin();
            render('admin/dashboard');
            break;

        case 'admin/user-progress':
            Auth::requireAdmin();
            render('admin/user-progress');
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
                        $allSuccess = true;
                        $errorMsg = '';

                        foreach ($readings as $catId => $reading) {
                            $passages = json_decode($reading['passages'] ?? '[]', true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $allSuccess = false;
                                $errorMsg = "Invalid JSON format for $catId passages.";
                                break;
                            }

                            if (!ReadingPlan::updateReading($week, $catId, $reading['reference'] ?? '', $passages)) {
                                $allSuccess = false;
                                $errorMsg = "Failed to update $catId reading.";
                                break;
                            }
                        }

                        if ($allSuccess) {
                            $data['success'] = "Week $week updated successfully.";
                        } else {
                            $data['error'] = $errorMsg ?: 'Failed to save changes.';
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

                    if ($action === 'upload_logo') {
                        // Handle logo upload
                        $uploadDir = ROOT_PATH . '/uploads/logos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['logo'];
                            $maxSize = 500 * 1024; // 500KB

                            if ($file['size'] > $maxSize) {
                                $data['logoError'] = 'File too large. Maximum 500KB allowed.';
                            } else {
                                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $allowed = ['png', 'jpg', 'jpeg', 'svg'];

                                if (!in_array($ext, $allowed)) {
                                    $data['logoError'] = 'Invalid file type. Use PNG, JPG, or SVG.';
                                } else {
                                    // Delete old logo if exists
                                    $oldLogo = Database::getSetting('app_logo', '');
                                    if (!empty($oldLogo) && file_exists($uploadDir . $oldLogo)) {
                                        unlink($uploadDir . $oldLogo);
                                    }

                                    // Generate unique filename
                                    $filename = 'app_logo_' . time() . '.' . $ext;
                                    $destPath = $uploadDir . $filename;

                                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                                        // Resize image if it's not SVG
                                        if ($ext !== 'svg' && function_exists('imagecreatefrompng')) {
                                            $maxWidth = 200;
                                            $maxHeight = 100;
                                            list($width, $height) = getimagesize($destPath);

                                            if ($width > $maxWidth || $height > $maxHeight) {
                                                $ratio = min($maxWidth / $width, $maxHeight / $height);
                                                $newWidth = (int)($width * $ratio);
                                                $newHeight = (int)($height * $ratio);

                                                $thumb = imagecreatetruecolor($newWidth, $newHeight);
                                                imagesavealpha($thumb, true);
                                                $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                                                imagefill($thumb, 0, 0, $transparent);

                                                if ($ext === 'png') {
                                                    $source = imagecreatefrompng($destPath);
                                                } else {
                                                    $source = imagecreatefromjpeg($destPath);
                                                }

                                                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                                                if ($ext === 'png') {
                                                    imagepng($thumb, $destPath);
                                                } else {
                                                    imagejpeg($thumb, $destPath, 90);
                                                }

                                                imagedestroy($thumb);
                                                imagedestroy($source);
                                            }
                                        }

                                        Database::setSetting('app_logo', $filename);
                                        $data['logoSuccess'] = 'Logo uploaded successfully.';
                                    } else {
                                        $data['logoError'] = 'Failed to upload file.';
                                    }
                                }
                            }
                        } else {
                            $data['logoError'] = 'Please select a valid file.';
                        }
                    } elseif ($action === 'remove_logo') {
                        // Remove logo
                        $uploadDir = ROOT_PATH . '/uploads/logos/';
                        $oldLogo = Database::getSetting('app_logo', '');
                        if (!empty($oldLogo) && file_exists($uploadDir . $oldLogo)) {
                            unlink($uploadDir . $oldLogo);
                        }
                        Database::setSetting('app_logo', '');
                        $data['logoSuccess'] = 'Logo removed.';
                    } elseif ($action === 'sync_translations') {
                        $result = Database::syncTranslationsFromAPI();
                        if ($result['success']) {
                            $data['success'] = 'Synced ' . $result['imported'] . ' translations from HelloAO API.';
                        } else {
                            $data['error'] = $result['error'];
                        }
                    } elseif ($action === 'clear_progress') {
                        // Require password confirmation for dangerous actions
                        $password = post('confirm_password', '');
                        if (!User::verifyPassword(Auth::getUserId(), $password)) {
                            $data['error'] = 'Incorrect password. Action cancelled for security.';
                        } else {
                            $pdo = Database::getInstance();
                            $pdo->exec('DELETE FROM reading_progress');
                            $pdo->exec('DELETE FROM chapter_progress');
                            $data['success'] = 'All reading progress has been cleared.';
                        }
                    } elseif ($action === 'reset_settings') {
                        Database::insertDefaultSettings();
                        $data['success'] = 'Settings have been reset to defaults.';
                    } else {
                        // Update settings
                        Database::setSetting('app_name', trim(post('app_name', 'ReadIn52')));
                        Database::setSetting('default_translation', post('default_translation', 'eng_kjv'));
                        Database::setSetting('registration_enabled', post('registration_enabled', '0') ? '1' : '0');
                        Database::setSetting('parent_site_name', trim(post('parent_site_name', '')));
                        Database::setSetting('parent_site_url', trim(post('parent_site_url', '')));
                        Database::setSetting('admin_email', trim(post('admin_email', '')));
                        Database::setSetting('github_repo_url', trim(post('github_repo_url', '')));
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

            // Check for API notes route: api/notes/{id}
            if (preg_match('#^api/notes/(\d+)$#', $route, $matches)) {
                Auth::requireAuth();
                header('Content-Type: application/json');
                $noteId = intval($matches[1]);
                $note = Note::get($noteId, Auth::getUserId());
                if ($note) {
                    echo json_encode(['success' => true, 'note' => $note]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Note not found']);
                }
                exit;
            }

            // Check for API notes/chapter route
            if ($route === 'api/notes/chapter') {
                Auth::requireAuth();
                header('Content-Type: application/json');
                $book = $_GET['book'] ?? '';
                $chapter = intval($_GET['chapter'] ?? 0);
                if ($book && $chapter) {
                    $notes = Note::getForChapter(Auth::getUserId(), $book, $chapter);
                    echo json_encode(['success' => true, 'notes' => $notes]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Book and chapter required']);
                }
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
