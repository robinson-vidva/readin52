<?php
// Get user theme preference and avatar info
$userTheme = 'auto';
$navInitials = '';
$navAvatarColor = '#5D4037';
$appLogo = Database::getSetting('app_logo', '');
if (Auth::isLoggedIn()) {
    $currentUser = Auth::getUser();
    $userTheme = $currentUser['theme'] ?? 'auto';
    $navInitials = getUserInitials($currentUser['name']);
    $navAvatarColor = getAvatarColor($currentUser['name']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e(ReadingPlan::getAppTagline()); ?>">
    <meta name="theme-color" content="#5D4037">

    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?><?php echo e(ReadingPlan::getAppName()); ?></title>

    <!-- Theme initialization (runs early to prevent flash) -->
    <script>
    (function() {
        var theme = '<?php echo e($userTheme); ?>';
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (theme === 'dark' || (theme === 'auto' && prefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icon.svg">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo APP_VERSION; ?>">

    <!-- Critical inline styles (fallback if CSS fails to load) -->
    <style>
        /* Essential resets and layout */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #FAFAFA; color: #212121; line-height: 1.6; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }

        /* Navbar */
        .navbar { background: #5D4037; padding: 1rem 0; position: sticky; top: 0; z-index: 100; }
        .navbar .container { display: flex; align-items: center; justify-content: space-between; }
        .navbar-brand { color: white; font-size: 1.25rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .navbar-menu { display: flex; gap: 1rem; align-items: center; }
        .nav-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 0.5rem 1rem; }
        .nav-link:hover, .nav-link.active { color: white; }
        .navbar-toggle { display: none; }

        /* Modal - MUST BE HIDDEN BY DEFAULT */
        .modal { display: none !important; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex !important; }
        .modal-content { background: white; border-radius: 12px; max-width: 600px; width: 95%; max-height: 90vh; overflow: hidden; }

        /* Buttons */
        .btn { display: inline-flex; padding: 0.625rem 1.25rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; }
        .btn-primary { background: #5D4037; color: white; }
        .btn-secondary { background: #E0E0E0; color: #212121; }

        /* Dashboard basics */
        .dashboard { padding: 2rem 0; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }

        /* Avatar */
        .avatar-small { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem; }

        /* Footer */
        .footer { background: #5D4037; color: rgba(255,255,255,0.9); padding: 1.5rem 0; text-align: center; }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .stats-banner { grid-template-columns: repeat(2, 1fr) !important; }
            .profile-content { grid-template-columns: 1fr !important; }
            .settings-content { grid-template-columns: 1fr !important; }
            .danger-zone-cards { grid-template-columns: 1fr !important; }
            .profile-header { flex-direction: column !important; text-align: center !important; }
            .navbar-toggle { display: flex; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 0.5rem; }
            .navbar-toggle span { display: block; width: 24px; height: 2px; background: white; }
            .navbar-menu { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #3E2723; flex-direction: column; padding: 1rem; }
            .navbar-menu.show { display: flex; }
            /* Hide avatar in mobile menu */
            .nav-avatar-link { display: none !important; }
        }
        @media (max-width: 480px) {
            .stats-banner { grid-template-columns: repeat(2, 1fr) !important; padding: 1rem !important; }
            .stat-value { font-size: 1.5rem !important; }
        }
    </style>

    <?php if (isset($extraStyles)): ?>
        <?php echo $extraStyles; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? e($bodyClass) : ''; ?>">
    <?php if (Auth::isLoggedIn()): ?>
    <nav class="navbar">
        <div class="container">
            <a href="/?route=dashboard" class="navbar-brand">
                <?php if (!empty($appLogo) && file_exists(ROOT_PATH . '/uploads/logos/' . $appLogo)): ?>
                    <img src="/uploads/logos/<?php echo e($appLogo); ?>" alt="Logo" style="max-height: 32px; max-width: 120px; vertical-align: middle; margin-right: 0.5rem;">
                <?php else: ?>
                    <span class="brand-icon">&#x1F4D6;</span>
                <?php endif; ?>
                <?php echo e(ReadingPlan::getAppName()); ?>
            </a>

            <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="navbar-menu" id="navbarMenu">
                <a href="/?route=dashboard" class="nav-link <?php echo activeClass('dashboard'); ?>">Dashboard</a>
                <a href="/?route=books" class="nav-link <?php echo activeClass('books'); ?>">Books</a>
                <a href="/?route=notes" class="nav-link <?php echo activeClass('notes'); ?>">Notes</a>
                <?php if (Auth::isAdmin()): ?>
                    <a href="/?route=admin" class="nav-link <?php echo strpos(currentRoute(), 'admin') === 0 ? 'active' : ''; ?>">Admin</a>
                <?php endif; ?>
                <div class="nav-user-menu">
                    <a href="/?route=profile" class="nav-avatar-link <?php echo activeClass('profile') || activeClass('settings') ? 'active' : ''; ?>">
                        <span class="avatar-small" style="background-color: <?php echo e($navAvatarColor); ?>;"><?php echo e($navInitials); ?></span>
                    </a>
                    <div class="nav-user-dropdown">
                        <a href="/?route=profile" class="dropdown-item">Profile</a>
                        <a href="/?route=settings" class="dropdown-item">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="/?route=logout" class="dropdown-item">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <?php
    $successFlash = getFlash('success');
    $errorFlash = getFlash('error');
    if ($successFlash || $errorFlash):
    ?>
    <div class="flash-messages">
        <div class="container">
            <?php if ($successFlash): ?>
                <div class="alert alert-success"><?php echo e($successFlash); ?></div>
            <?php endif; ?>
            <?php if ($errorFlash): ?>
                <div class="alert alert-error"><?php echo e($errorFlash); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <main class="main-content">
        <?php echo $content ?? ''; ?>
    </main>

    <?php if (Auth::isLoggedIn()): ?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <span class="footer-logo">&#x1F4D6;</span>
                    <span class="footer-name"><?php echo e(ReadingPlan::getAppName()); ?></span>
                </div>
                <div class="footer-features">
                    <span class="footer-feature"><span class="footer-icon">&#x1F4C5;</span> 52 Weeks</span>
                    <span class="footer-feature"><span class="footer-icon">&#x1F4DA;</span> 66 Books</span>
                    <span class="footer-feature"><span class="footer-icon">&#x1F4D6;</span> 1,189 Chapters</span>
                </div>
                <div class="footer-links">
                    <a href="/?route=privacy" class="footer-link">Privacy Policy</a>
                    <span class="footer-divider">|</span>
                    <a href="/?route=terms" class="footer-link">Terms & Conditions</a>
                </div>
                <div class="footer-credit">
                    <p>Scripture provided by <a href="https://bible.helloao.org/" target="_blank" rel="noopener">HelloAO Bible API</a></p>
                    <p>&copy; <?php echo date('Y'); ?> <?php echo e(ReadingPlan::getAppName()); ?>. Journey Through Scripture.</p>
                </div>
            </div>
        </div>
    </footer>
    <style>
        .footer-content { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 0.5rem 0; }
        .footer-brand { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; }
        .footer-logo { font-size: 1.5rem; }
        .footer-features { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; }
        .footer-feature { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; opacity: 0.9; }
        .footer-icon { font-size: 1rem; }
        .footer-links { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; }
        .footer-link { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 0.8rem; transition: color 0.2s; }
        .footer-link:hover { color: white; text-decoration: underline; }
        .footer-divider { opacity: 0.5; font-size: 0.75rem; }
        .footer-credit { text-align: center; font-size: 0.75rem; opacity: 0.7; margin-top: 0.5rem; }
        .footer-credit p { margin: 0.25rem 0; }
        .footer-credit a { color: inherit; text-decoration: underline; }
        .footer-credit a:hover { opacity: 1; }
        @media (max-width: 480px) {
            .footer-features { gap: 1rem; }
            .footer-feature { font-size: 0.8rem; }
        }
    </style>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="/assets/js/bible-api.js?v=<?php echo APP_VERSION; ?>"></script>
    <script src="/assets/js/app.js?v=<?php echo APP_VERSION; ?>"></script>

    <?php if (isset($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.log('Service Worker registration failed:', err));
        }

        // User dropdown toggle
        (function() {
            var userMenu = document.querySelector('.nav-user-menu');
            var dropdown = document.querySelector('.nav-user-dropdown');
            if (userMenu && dropdown) {
                userMenu.addEventListener('mouseenter', function() {
                    dropdown.style.display = 'block';
                });
                userMenu.addEventListener('mouseleave', function() {
                    dropdown.style.display = 'none';
                });
            }
        })();
    </script>
</body>
</html>
