<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your password for <?php echo e(ReadingPlan::getAppName()); ?>">
    <meta name="theme-color" content="#5D4037">

    <title>Forgot Password - <?php echo e(ReadingPlan::getAppName()); ?></title>

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
                <h1>Forgot Password</h1>
                <p>Enter your email and we'll send you a reset link</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="/?route=login" class="btn btn-primary btn-block">Back to Sign In</a>
                </div>
            <?php else: ?>
                <form method="POST" action="/?route=forgot-password" class="auth-form">
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

                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>

                <div class="auth-footer">
                    <p>Remember your password? <a href="/?route=login">Sign In</a></p>
                </div>
            <?php endif; ?>
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
