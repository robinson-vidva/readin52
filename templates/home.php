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
        <a href="<?php echo e($parentSiteUrl); ?>" class="topbar-brand" title="<?php echo e($parentSiteName); ?>">
            <span class="topbar-arrow">&larr;</span>
            <span class="topbar-text"><?php echo e($parentSiteName); ?></span>
        </a>
    </nav>
    <style>
        .home-topbar {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding: 0.75rem;
        }
        .topbar-brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            transition: all 0.3s ease;
        }
        .topbar-brand:hover {
            background: rgba(255,255,255,0.25);
        }
        .topbar-arrow {
            font-size: 1.1rem;
        }
        .topbar-text {
            display: inline;
        }

        /* Mobile: Compact circular button */
        @media (max-width: 600px) {
            .home-topbar {
                padding: 0.5rem;
            }
            .topbar-brand {
                width: 40px;
                height: 40px;
                padding: 0;
                border-radius: 50%;
                overflow: hidden;
            }
            .topbar-text {
                display: none;
            }
            .topbar-arrow {
                font-size: 1.2rem;
            }
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
            <h3 style="margin: 0 0 0.5rem; color: #5D4037;">Install <?php echo e(ReadingPlan::getAppName()); ?></h3>
            <p id="deviceInfo" style="font-size: 0.75rem; color: #999; margin-bottom: 1rem;"></p>

            <!-- Dynamic Instructions Container -->
            <div id="installInstructions"></div>

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

            const ua = navigator.userAgent;

            // Detect OS
            const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
            const isAndroid = /Android/.test(ua);
            const isMac = /Macintosh|MacIntel|MacPPC|Mac68K/.test(ua);
            const isWindows = /Win32|Win64|Windows/.test(ua);
            const isLinux = /Linux/.test(ua) && !isAndroid;

            // Detect Browser
            const isSafari = /Safari/.test(ua) && !/Chrome|CriOS|FxiOS|EdgiOS/.test(ua);
            const isChrome = /Chrome/.test(ua) && !/Edg|OPR|SamsungBrowser/.test(ua);
            const isChromeIOS = /CriOS/.test(ua);
            const isFirefox = /Firefox|FxiOS/.test(ua);
            const isEdge = /Edg/.test(ua);
            const isOpera = /OPR|Opera/.test(ua);
            const isSamsungBrowser = /SamsungBrowser/.test(ua);

            // Get friendly names
            function getOSName() {
                if (isIOS) return 'iOS';
                if (isAndroid) return 'Android';
                if (isMac) return 'macOS';
                if (isWindows) return 'Windows';
                if (isLinux) return 'Linux';
                return 'Unknown OS';
            }

            function getBrowserName() {
                if (isSamsungBrowser) return 'Samsung Internet';
                if (isChromeIOS) return 'Chrome';
                if (isEdge) return 'Edge';
                if (isOpera) return 'Opera';
                if (isChrome) return 'Chrome';
                if (isFirefox) return 'Firefox';
                if (isSafari) return 'Safari';
                return 'Browser';
            }

            // Generate instructions based on device/browser
            function getInstructions() {
                const os = getOSName();
                const browser = getBrowserName();

                // iOS devices
                if (isIOS) {
                    if (isSafari) {
                        return {
                            intro: 'Add this app to your home screen:',
                            steps: [
                                'Tap the <strong>Share</strong> button <span class="icon-badge" style="background:#007AFF;">↑</span> at the bottom of the screen',
                                'Scroll down and tap <strong>"Add to Home Screen"</strong>',
                                'Tap <strong>"Add"</strong> in the top right corner'
                            ],
                            note: 'The app will appear on your home screen.'
                        };
                    } else {
                        return {
                            intro: 'To install this app on iOS:',
                            steps: [
                                'Open this page in <strong>Safari</strong> browser',
                                'Tap the <strong>Share</strong> button <span class="icon-badge" style="background:#007AFF;">↑</span>',
                                'Tap <strong>"Add to Home Screen"</strong>'
                            ],
                            note: 'Note: ' + browser + ' on iOS doesn\'t support app installation. Please use Safari.'
                        };
                    }
                }

                // Android devices
                if (isAndroid) {
                    if (isChrome) {
                        return {
                            intro: 'Add this app to your home screen:',
                            steps: [
                                'Tap the <strong>menu</strong> button <span class="icon-badge" style="background:#333;">⋮</span> in the top right',
                                'Tap <strong>"Install app"</strong> or <strong>"Add to Home screen"</strong>',
                                'Tap <strong>"Install"</strong> to confirm'
                            ],
                            note: 'The app will appear on your home screen.'
                        };
                    } else if (isSamsungBrowser) {
                        return {
                            intro: 'Add this app to your home screen:',
                            steps: [
                                'Tap the <strong>menu</strong> button <span class="icon-badge" style="background:#333;">☰</span> (3 lines)',
                                'Tap <strong>"Add page to"</strong>',
                                'Select <strong>"Home screen"</strong>'
                            ],
                            note: 'The app will appear on your home screen.'
                        };
                    } else if (isFirefox) {
                        return {
                            intro: 'Add this app to your home screen:',
                            steps: [
                                'Tap the <strong>menu</strong> button <span class="icon-badge" style="background:#333;">⋮</span>',
                                'Tap <strong>"Install"</strong>',
                                'Confirm by tapping <strong>"Add"</strong>'
                            ],
                            note: 'The app will appear on your home screen.'
                        };
                    } else if (isEdge) {
                        return {
                            intro: 'Add this app to your home screen:',
                            steps: [
                                'Tap the <strong>menu</strong> button <span class="icon-badge" style="background:#333;">⋯</span> at the bottom',
                                'Tap <strong>"Add to phone"</strong>',
                                'Select <strong>"Add to Home screen"</strong>'
                            ],
                            note: 'The app will appear on your home screen.'
                        };
                    } else if (isOpera) {
                        return {
                            intro: 'Add this app to your home screen:',
                            steps: [
                                'Tap the <strong>menu</strong> button <span class="icon-badge" style="background:#333;">⋮</span>',
                                'Tap <strong>"Home screen"</strong>',
                                'Tap <strong>"Add"</strong> to confirm'
                            ],
                            note: 'The app will appear on your home screen.'
                        };
                    } else {
                        return {
                            intro: 'For best experience, use Chrome:',
                            steps: [
                                'Open this page in <strong>Chrome</strong>',
                                'Tap the menu <span class="icon-badge" style="background:#333;">⋮</span> and select <strong>"Install app"</strong>',
                                'Tap <strong>"Install"</strong> to confirm'
                            ],
                            note: 'Or look for "Add to Home screen" option in your browser menu.'
                        };
                    }
                }

                // Desktop browsers
                if (isChrome || isEdge) {
                    return {
                        intro: 'Install this app on your computer:',
                        steps: [
                            'Look for the <strong>install icon</strong> <span class="icon-badge" style="background:#333;">+</span> in the address bar (right side)',
                            'Or click the <strong>menu</strong> <span class="icon-badge" style="background:#333;">⋮</span> and select <strong>"Install ' + '<?php echo e(ReadingPlan::getAppName()); ?>' + '"</strong>',
                            'Click <strong>"Install"</strong> to confirm'
                        ],
                        note: 'The app will open in its own window.'
                    };
                } else if (isFirefox) {
                    return {
                        intro: 'Firefox has limited PWA support:',
                        steps: [
                            'For the best experience, open this page in <strong>Chrome</strong> or <strong>Edge</strong>',
                            'Then click the install icon in the address bar',
                            'Or bookmark this page for quick access'
                        ],
                        note: 'You can still use the site normally in Firefox.'
                    };
                } else if (isSafari && isMac) {
                    return {
                        intro: 'Add to Dock (macOS Sonoma+):',
                        steps: [
                            'Click <strong>File</strong> in the menu bar',
                            'Select <strong>"Add to Dock"</strong>',
                            'The app will appear in your Dock'
                        ],
                        note: 'Requires macOS Sonoma (14.0) or later. For older versions, use Chrome.'
                    };
                } else {
                    return {
                        intro: 'Install this app:',
                        steps: [
                            'For the best experience, open this page in <strong>Chrome</strong> or <strong>Edge</strong>',
                            'Click the install icon in the address bar',
                            'Or click the menu and select "Install app"'
                        ],
                        note: 'This allows the app to work offline and feel like a native app.'
                    };
                }
            }

            const wrapper = document.getElementById('installAppWrapper');
            const btn = document.getElementById('installAppBtn');
            const modal = document.getElementById('installModal');
            const closeBtn = document.getElementById('closeInstallModal');
            const closeBtnBottom = document.getElementById('closeInstallModalBtn');
            const backdrop = modal.querySelector('.install-modal-backdrop');
            const deviceInfo = document.getElementById('deviceInfo');
            const instructionsDiv = document.getElementById('installInstructions');

            // Show the install button
            wrapper.style.display = 'block';

            // Show appropriate instructions
            function showModal() {
                const instructions = getInstructions();
                deviceInfo.textContent = getOSName() + ' • ' + getBrowserName();

                let html = '<p style="margin-bottom: 1rem; color: #666;">' + instructions.intro + '</p>';
                html += '<ol style="text-align: left; padding-left: 1.5rem; color: #333; line-height: 2;">';
                instructions.steps.forEach(step => {
                    html += '<li>' + step + '</li>';
                });
                html += '</ol>';
                html += '<p style="margin-top: 1rem; font-size: 0.85rem; color: #888;">' + instructions.note + '</p>';

                instructionsDiv.innerHTML = html;
                modal.style.display = 'flex';
            }

            function hideModal() {
                modal.style.display = 'none';
            }

            btn.addEventListener('click', showModal);
            closeBtn.addEventListener('click', hideModal);
            closeBtnBottom.addEventListener('click', hideModal);
            backdrop.addEventListener('click', hideModal);

            // Handle native install prompt (Chrome/Edge on Android/Desktop)
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
            });

            // Override click to use native prompt when available
            btn.addEventListener('click', async (evt) => {
                if (deferredPrompt) {
                    evt.stopImmediatePropagation();
                    modal.style.display = 'none';
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    if (outcome === 'accepted') {
                        wrapper.style.display = 'none';
                    }
                }
            });
        })();
    </script>

    <style>
        .icon-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            color: white;
            border-radius: 4px;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            vertical-align: middle;
        }
    </style>
</body>
</html>
