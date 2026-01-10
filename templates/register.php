<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create an account on <?php echo e(ReadingPlan::getAppName()); ?>">
    <meta name="theme-color" content="#5D4037">

    <title>Create Account - <?php echo e(ReadingPlan::getAppName()); ?></title>

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
                <h1>Create Account</h1>
                <p>Start your 52-week Bible reading journey</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="/?route=register" class="auth-form">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo e($name ?? ''); ?>"
                        required
                        autocomplete="name"
                        autofocus
                        minlength="2"
                        maxlength="100"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo e($email ?? ''); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        minlength="6"
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
                    >
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="accept_terms" id="accept_terms" required>
                        <span>I agree to the <a href="#" id="showTermsLink">Terms & Conditions</a></span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="/?route=login">Sign in</a></p>
            </div>

            <div class="auth-home-link">
                <a href="/">&larr; Back to Home</a>
            </div>
        </div>
    </div>

    <style>
        .auth-home-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .auth-home-link a {
            color: #888;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .auth-home-link a:hover {
            color: #5D4037;
        }
    </style>

    <!-- Terms Modal -->
    <div id="termsModal" class="terms-modal" style="display: none;">
        <div class="terms-modal-backdrop"></div>
        <div class="terms-modal-content">
            <div class="terms-modal-header">
                <h3>Terms & Conditions</h3>
                <button type="button" class="terms-modal-close" id="closeTermsModal">&times;</button>
            </div>
            <div class="terms-modal-body" id="termsContent">
                <p style="text-align: center; color: #888;">Loading...</p>
            </div>
            <div class="terms-modal-footer">
                <button type="button" class="btn btn-primary" id="acceptTermsBtn">I Accept</button>
            </div>
        </div>
    </div>

    <style>
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            cursor: pointer;
            font-weight: normal;
        }
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
        }
        .checkbox-label span {
            font-size: 0.9rem;
            color: #555;
        }
        .checkbox-label a {
            color: #1565C0;
            text-decoration: underline;
        }

        .terms-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .terms-modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
        }
        .terms-modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: termsModalSlideIn 0.3s ease;
        }
        @keyframes termsModalSlideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .terms-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .terms-modal-header h3 {
            margin: 0;
            color: #5D4037;
            font-size: 1.1rem;
        }
        .terms-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: #f0f0f0;
            border-radius: 50%;
            font-size: 1.25rem;
            color: #666;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .terms-modal-close:hover {
            background: #e0e0e0;
        }
        .terms-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #333;
        }
        .terms-modal-body h1, .terms-modal-body h2, .terms-modal-body h3 {
            color: #5D4037;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .terms-modal-body h1:first-child,
        .terms-modal-body h2:first-child {
            margin-top: 0;
        }
        .terms-modal-body ul, .terms-modal-body ol {
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .terms-modal-body li {
            margin-bottom: 0.5rem;
        }
        .terms-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            text-align: center;
        }
    </style>

    <script>
        // Simple password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            const acceptTerms = document.getElementById('accept_terms').checked;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }

            if (!acceptTerms) {
                e.preventDefault();
                alert('Please accept the Terms & Conditions to continue');
            }
        });

        // Terms modal functionality
        (function() {
            const modal = document.getElementById('termsModal');
            const showLink = document.getElementById('showTermsLink');
            const closeBtn = document.getElementById('closeTermsModal');
            const acceptBtn = document.getElementById('acceptTermsBtn');
            const backdrop = modal.querySelector('.terms-modal-backdrop');
            const checkbox = document.getElementById('accept_terms');
            const termsContent = document.getElementById('termsContent');

            let termsLoaded = false;

            function showModal() {
                modal.style.display = 'flex';
                if (!termsLoaded) {
                    loadTerms();
                }
            }

            function hideModal() {
                modal.style.display = 'none';
            }

            function loadTerms() {
                fetch('/?route=terms')
                    .then(response => response.text())
                    .then(html => {
                        // Extract just the terms content from the page
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const content = doc.querySelector('.legal-content');
                        if (content) {
                            termsContent.innerHTML = content.innerHTML;
                        } else {
                            // Fallback: try to get body content minus header/footer
                            const body = doc.querySelector('.container');
                            if (body) {
                                termsContent.innerHTML = body.innerHTML;
                            } else {
                                termsContent.innerHTML = '<p>Please visit <a href="/?route=terms" target="_blank">Terms & Conditions</a> to read the full terms.</p>';
                            }
                        }
                        termsLoaded = true;
                    })
                    .catch(() => {
                        termsContent.innerHTML = '<p>Unable to load terms. Please visit <a href="/?route=terms" target="_blank">Terms & Conditions</a> to read the full terms.</p>';
                    });
            }

            showLink.addEventListener('click', function(e) {
                e.preventDefault();
                showModal();
            });

            closeBtn.addEventListener('click', hideModal);
            backdrop.addEventListener('click', hideModal);

            acceptBtn.addEventListener('click', function() {
                checkbox.checked = true;
                hideModal();
            });
        })();

        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
