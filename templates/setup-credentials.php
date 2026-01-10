<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Set up your account on <?php echo e(ReadingPlan::getAppName()); ?>">
    <meta name="theme-color" content="#5D4037">

    <title>Set Up Your Account - <?php echo e(ReadingPlan::getAppName()); ?></title>

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
                <div class="auth-logo">
                    <span class="logo-icon">&#x1F4D6;</span>
                    <span class="logo-text"><?php echo e(ReadingPlan::getAppName()); ?></span>
                </div>
                <h1>Welcome!</h1>
                <p>Please set up your account credentials for security.</p>
            </div>

            <div class="setup-info" style="background: #E3F2FD; border: 1px solid #1565C0; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #0D47A1; font-size: 0.9rem;">
                <strong>Security Notice:</strong> You are using temporary credentials. Please enter your own email and a secure password to continue.
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="/?route=setup-credentials" class="auth-form">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="email">Your Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo e($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="your.email@example.com"
                    >
                    <small class="form-hint">Enter your real email address</small>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        minlength="6"
                        placeholder="Enter a secure password"
                    >
                    <small class="form-hint">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Save & Continue</button>
            </form>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });

        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
