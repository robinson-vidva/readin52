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
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo APP_VERSION; ?>">

    <!-- Responsive overrides for inline styles -->
    <style>
        @media (max-width: 768px) {
            .stats-banner { grid-template-columns: repeat(2, 1fr) !important; }
            .profile-content { grid-template-columns: 1fr !important; }
            .settings-content { grid-template-columns: 1fr !important; }
            .danger-zone-cards { grid-template-columns: 1fr !important; }
            .profile-header { flex-direction: column !important; text-align: center !important; }
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
                <?php if (Auth::isAdmin()): ?>
                    <a href="/?route=admin" class="nav-link <?php echo strpos(currentRoute(), 'admin') === 0 ? 'active' : ''; ?>">Admin</a>
                <?php endif; ?>
                <div class="nav-user-menu" style="position: relative; display: inline-block;">
                    <a href="/?route=profile" class="nav-avatar-link <?php echo activeClass('profile') || activeClass('settings') ? 'active' : ''; ?>" style="display: flex; padding: 4px; border-radius: 50%; cursor: pointer;">
                        <span class="avatar-small" style="background-color: <?php echo e($navAvatarColor); ?>; width: 32px; height: 32px; min-width: 32px; min-height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem; text-transform: uppercase;"><?php echo e($navInitials); ?></span>
                    </a>
                    <div class="nav-user-dropdown" style="display: none; position: absolute; top: 100%; right: 0; min-width: 160px; background: var(--card-bg, #fff); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 0.5rem 0; z-index: 1000; margin-top: 4px;">
                        <a href="/?route=profile" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: var(--text-color, #333); text-decoration: none;">Profile</a>
                        <a href="/?route=settings" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: var(--text-color, #333); text-decoration: none;">Settings</a>
                        <div class="dropdown-divider" style="height: 1px; background: var(--border-color, #e0e0e0); margin: 0.5rem 0;"></div>
                        <a href="/?route=logout" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: var(--text-color, #333); text-decoration: none;">Logout</a>
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
            <p>&copy; <?php echo date('Y'); ?> <?php echo e(ReadingPlan::getAppName()); ?>. <span class="footer-tagline">Journey Through Scripture in 52 Weeks.</span></p>
        </div>
    </footer>
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
