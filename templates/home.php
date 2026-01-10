<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e(ReadingPlan::getAppTagline()); ?>">
    <meta name="theme-color" content="#5D4037">

    <title><?php echo e(ReadingPlan::getAppName()); ?> - <?php echo e(ReadingPlan::getAppTagline()); ?></title>

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
<body class="home-page">
    <?php
    $appLogo = Database::getSetting('app_logo', '');
    $parentSiteUrl = Database::getSetting('parent_site_url', '');
    $parentSiteName = Database::getSetting('parent_site_name', '');
    ?>

    <?php if ($parentSiteUrl && $parentSiteName): ?>
    <nav class="home-topbar">
        <a href="<?php echo e($parentSiteUrl); ?>" class="topbar-brand">
            <span class="topbar-arrow">&larr;</span>
            <span class="topbar-text"><?php echo e($parentSiteName); ?></span>
        </a>
    </nav>
    <style>
        .home-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, transparent 100%);
        }
        .topbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .topbar-brand:hover {
            background: rgba(255,255,255,0.25);
            transform: translateX(-2px);
        }
        .topbar-arrow {
            font-size: 1.1rem;
        }
    </style>
    <?php endif; ?>

    <div class="home-hero">
        <div class="home-overlay"></div>
        <div class="container">
            <div class="home-content">
                <div class="home-logo">
                    <?php if (!empty($appLogo) && file_exists(ROOT_PATH . '/uploads/logos/' . $appLogo)): ?>
                        <img src="/uploads/logos/<?php echo e($appLogo); ?>" alt="Logo" style="max-height: 80px; max-width: 200px;">
                    <?php else: ?>
                        <span class="logo-icon">&#x1F4D6;</span>
                    <?php endif; ?>
                </div>
                <h1 class="home-title"><?php echo e(ReadingPlan::getAppName()); ?></h1>
                <p class="home-tagline"><?php echo e(ReadingPlan::getAppTagline()); ?></p>

                <div class="home-features">
                    <div class="feature">
                        <span class="feature-icon">&#x1F4C5;</span>
                        <span class="feature-text">52-Week Plan</span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon">&#x1F4DA;</span>
                        <span class="feature-text">4 Categories</span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon">&#x1F4F1;</span>
                        <span class="feature-text">Read Anywhere</span>
                    </div>
                </div>

                <div class="home-categories" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem;">
                    <?php foreach (ReadingPlan::getCategories() as $category): ?>
                        <span style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: rgba(255,255,255,0.9);">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?php echo e($category['color']); ?>;"></span>
                            <?php echo e($category['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="home-actions">
                    <a href="/?route=login" class="btn btn-primary btn-lg">Sign In</a>
                    <?php if (Auth::isRegistrationEnabled()): ?>
                        <a href="/?route=register" class="btn btn-outline btn-lg">Create Account</a>
                    <?php endif; ?>
                </div>

                <!-- Install App Button (hidden if already installed as PWA) -->
                <div id="installAppWrapper" style="margin-top: 1rem; display: none;">
                    <button type="button" id="installAppBtn" class="btn btn-outline" style="font-size: 0.9rem; padding: 0.5rem 1.25rem; border-color: rgba(255,255,255,0.5); color: white;">
                        <span style="margin-right: 0.4rem;">&#x1F4F2;</span> Install App
                    </button>
                </div>

                <div class="home-stats">
                    <div class="stat">
                        <span class="stat-value">1,189</span>
                        <span class="stat-label">Chapters</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">66</span>
                        <span class="stat-label">Books</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">52</span>
                        <span class="stat-label">Weeks</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">208</span>
                        <span class="stat-label">Readings</span>
                    </div>
                </div>

                <div class="home-verse" style="margin-top: 2rem; padding: 1rem 0; text-align: center;">
                    <p style="font-family: 'Merriweather', serif; font-style: italic; font-size: 0.95rem; color: rgba(255,255,255,0.85); margin: 0; line-height: 1.6;">
                        "So then faith cometh by hearing, and hearing by the word of God."
                    </p>
                    <span style="display: block; margin-top: 0.4rem; font-size: 0.8rem; color: rgba(255,255,255,0.6);">— Romans 10:17 (KJV)</span>
                </div>
            </div>
        </div>
    </div>

    <section class="home-about">
        <div class="container">
            <h2>Read Through the Bible in One Year</h2>
            <p>
                ReadIn52 is a structured Bible reading plan that guides you through the entire
                Scripture in 52 weeks. Each week includes four readings from different categories:
            </p>

            <div class="about-grid">
                <?php foreach (ReadingPlan::getCategories() as $category): ?>
                    <div class="about-card" style="border-top-color: <?php echo e($category['color']); ?>">
                        <h3><?php echo e($category['name']); ?></h3>
                        <p>
                            <?php
                            switch ($category['id']) {
                                case 'poetry':
                                    echo 'Psalms, Proverbs, Ecclesiastes, Song of Solomon, and Job';
                                    break;
                                case 'history':
                                    echo 'Genesis through Esther - the historical books of the Old Testament';
                                    break;
                                case 'prophecy':
                                    echo 'Isaiah through Malachi - the prophetic books and Chronicles';
                                    break;
                                case 'gospels':
                                    echo 'Matthew through Revelation - the New Testament writings';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="home-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo e(ReadingPlan::getAppName()); ?>. All rights reserved.</p>
            <p style="font-size: 0.75rem; margin-top: 0.5rem; opacity: 0.7;">Scripture provided by <a href="https://bible.helloao.org/" target="_blank" rel="noopener" style="color: inherit; text-decoration: underline;">HelloAO Bible API</a></p>
            <p style="font-size: 0.75rem; margin-top: 0.5rem; opacity: 0.7;"><a href="/?route=about" style="color: inherit; text-decoration: underline;">About</a> &middot; <a href="/?route=privacy" style="color: inherit; text-decoration: underline;">Privacy Policy</a> &middot; <a href="/?route=terms" style="color: inherit; text-decoration: underline;">Terms & Conditions</a></p>
        </div>
    </footer>

    <!-- Install App Modal -->
    <div id="installModal" class="install-modal" style="display: none;">
        <div class="install-modal-backdrop"></div>
        <div class="install-modal-content">
            <button type="button" class="install-modal-close" id="closeInstallModal">&times;</button>
            <h3 style="margin: 0 0 1rem; color: #5D4037;">Install <?php echo e(ReadingPlan::getAppName()); ?></h3>

            <!-- iOS Instructions -->
            <div id="iosInstructions" style="display: none;">
                <p style="margin-bottom: 1rem; color: #666;">Add this app to your home screen for quick access:</p>
                <ol style="text-align: left; padding-left: 1.5rem; color: #333; line-height: 1.8;">
                    <li>Tap the <strong>Share</strong> button <span style="display: inline-block; width: 20px; height: 20px; background: #007AFF; color: white; border-radius: 4px; text-align: center; line-height: 20px; font-size: 12px;">↑</span> at the bottom of Safari</li>
                    <li>Scroll down and tap <strong>"Add to Home Screen"</strong></li>
                    <li>Tap <strong>"Add"</strong> in the top right corner</li>
                </ol>
                <p style="margin-top: 1rem; font-size: 0.85rem; color: #888;">The app will appear on your home screen like a regular app.</p>
            </div>

            <!-- Android Instructions -->
            <div id="androidInstructions" style="display: none;">
                <p style="margin-bottom: 1rem; color: #666;">Add this app to your home screen for quick access:</p>
                <ol style="text-align: left; padding-left: 1.5rem; color: #333; line-height: 1.8;">
                    <li>Tap the <strong>menu</strong> button <span style="display: inline-block; padding: 2px 6px; background: #333; color: white; border-radius: 4px; font-size: 12px;">&#8942;</span> in Chrome</li>
                    <li>Tap <strong>"Add to Home screen"</strong> or <strong>"Install app"</strong></li>
                    <li>Tap <strong>"Add"</strong> or <strong>"Install"</strong> to confirm</li>
                </ol>
                <p style="margin-top: 1rem; font-size: 0.85rem; color: #888;">The app will appear on your home screen like a regular app.</p>
            </div>

            <!-- Desktop Instructions -->
            <div id="desktopInstructions" style="display: none;">
                <p style="margin-bottom: 1rem; color: #666;">Install this app for quick access:</p>
                <ol style="text-align: left; padding-left: 1.5rem; color: #333; line-height: 1.8;">
                    <li>Look for the <strong>install icon</strong> in your browser's address bar</li>
                    <li>Or click the <strong>menu</strong> and select <strong>"Install app"</strong></li>
                    <li>Click <strong>"Install"</strong> to confirm</li>
                </ol>
                <p style="margin-top: 1rem; font-size: 0.85rem; color: #888;">On mobile? Open this page on your phone or tablet for mobile-specific instructions.</p>
            </div>

            <button type="button" class="btn btn-primary" id="closeInstallModalBtn" style="margin-top: 1.5rem;">Got it!</button>
        </div>
    </div>

    <style>
        .install-modal {
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
        .install-modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
        }
        .install-modal-content {
            position: relative;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .install-modal-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
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
            transition: background 0.2s;
        }
        .install-modal-close:hover {
            background: #e0e0e0;
        }
    </style>

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }

        // Install App functionality
        (function() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;

            // Don't show install button if already installed
            if (isStandalone) return;

            const wrapper = document.getElementById('installAppWrapper');
            const btn = document.getElementById('installAppBtn');
            const modal = document.getElementById('installModal');
            const closeBtn = document.getElementById('closeInstallModal');
            const closeBtnBottom = document.getElementById('closeInstallModalBtn');
            const backdrop = modal.querySelector('.install-modal-backdrop');

            // Detect device type
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            const isMobile = isIOS || isAndroid;

            // Show the install button
            wrapper.style.display = 'block';

            // Show appropriate instructions
            function showModal() {
                modal.style.display = 'flex';
                document.getElementById('iosInstructions').style.display = isIOS ? 'block' : 'none';
                document.getElementById('androidInstructions').style.display = isAndroid ? 'block' : 'none';
                document.getElementById('desktopInstructions').style.display = (!isIOS && !isAndroid) ? 'block' : 'none';
            }

            function hideModal() {
                modal.style.display = 'none';
            }

            btn.addEventListener('click', showModal);
            closeBtn.addEventListener('click', hideModal);
            closeBtnBottom.addEventListener('click', hideModal);
            backdrop.addEventListener('click', hideModal);

            // Handle native install prompt (Chrome/Edge)
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;

                // Replace modal with native prompt for supported browsers
                btn.addEventListener('click', async (evt) => {
                    if (deferredPrompt) {
                        evt.stopImmediatePropagation();
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        deferredPrompt = null;
                        if (outcome === 'accepted') {
                            wrapper.style.display = 'none';
                        }
                    }
                }, { once: true });
            });
        })();
    </script>
</body>
</html>
