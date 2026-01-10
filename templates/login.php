<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to <?php echo e(ReadingPlan::getAppName()); ?>">
    <meta name="theme-color" content="#5D4037">

    <title>Sign In - <?php echo e(ReadingPlan::getAppName()); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="auth-logo">
                    <span class="logo-icon">&#x1F4D6;</span>
                    <span class="logo-text"><?php echo e(ReadingPlan::getAppName()); ?></span>
                </a>
                <h1>Welcome Back</h1>
                <p>Sign in to continue your reading journey</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php
            $successFlash = getFlash('success');
            if ($successFlash):
            ?>
                <div class="alert alert-success"><?php echo e($successFlash); ?></div>
            <?php endif; ?>

            <form method="POST" action="/?route=login" class="auth-form">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo e($email ?? ''); ?>"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password">Password</label>
                        <a href="/?route=forgot-password" style="font-size: 0.85rem;">Forgot password?</a>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group form-check">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <?php if (Auth::isRegistrationEnabled()): ?>
                <div class="auth-footer">
                    <p>Don't have an account? <a href="/?route=register">Create one</a></p>
                </div>
            <?php endif; ?>

            <div class="auth-home-link">
                <a href="/">&larr; Back to Home</a>
            </div>
        </div>
    </div>

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
