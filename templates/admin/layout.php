<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>Admin - <?php echo e(ReadingPlan::getAppName()); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body class="admin-page">
    <?php $adminAppLogo = Database::getSetting('app_logo', ''); ?>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="/?route=admin" class="admin-logo">
                    <?php if (!empty($adminAppLogo) && file_exists(ROOT_PATH . '/uploads/logos/' . $adminAppLogo)): ?>
                        <img src="/uploads/logos/<?php echo e($adminAppLogo); ?>" alt="Logo" style="max-height: 28px; max-width: 100px; vertical-align: middle;">
                    <?php else: ?>
                        <span class="logo-icon">&#x1F4D6;</span>
                    <?php endif; ?>
                    <span class="logo-text"><?php echo e(ReadingPlan::getAppName()); ?></span>
                </a>
                <span class="admin-badge">Admin</span>
            </div>

            <nav class="sidebar-nav">
                <a href="/?route=admin" class="nav-item <?php echo currentRoute() === 'admin' ? 'active' : ''; ?>">
                    <span class="nav-icon">&#x1F4CA;</span>
                    Dashboard
                </a>
                <a href="/?route=admin/users" class="nav-item <?php echo currentRoute() === 'admin/users' ? 'active' : ''; ?>">
                    <span class="nav-icon">&#x1F465;</span>
                    Users
                </a>
                <a href="/?route=admin/reading-plan" class="nav-item <?php echo currentRoute() === 'admin/reading-plan' ? 'active' : ''; ?>">
                    <span class="nav-icon">&#x1F4D6;</span>
                    Reading Plan
                </a>
                <a href="/?route=admin/settings" class="nav-item <?php echo currentRoute() === 'admin/settings' ? 'active' : ''; ?>">
                    <span class="nav-icon">&#x2699;</span>
                    Settings
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="/?route=dashboard" class="nav-item">
                    <span class="nav-icon">&#x2190;</span>
                    Back to App
                </a>
                <a href="/?route=logout" class="nav-item">
                    <span class="nav-icon">&#x1F6AA;</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="page-title"><?php echo e($pageTitle ?? 'Admin'); ?></h1>
                <div class="header-user">
                    <?php $user = Auth::getUser(); ?>
                    <span class="user-name"><?php echo e($user['name']); ?></span>
                </div>
            </header>

            <?php
            $successFlash = getFlash('success');
            $errorFlash = getFlash('error');
            if ($successFlash || $errorFlash):
            ?>
            <div class="flash-messages">
                <?php if ($successFlash): ?>
                    <div class="alert alert-success"><?php echo e($successFlash); ?></div>
                <?php endif; ?>
                <?php if ($errorFlash): ?>
                    <div class="alert alert-error"><?php echo e($errorFlash); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="admin-content">
                <?php echo $content ?? ''; ?>
            </div>
        </main>
    </div>

    <script src="/assets/js/app.js?v=<?php echo APP_VERSION; ?>"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.admin-layout').classList.toggle('sidebar-open');
        });
    </script>

    <?php if (isset($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>
</body>
</html>
