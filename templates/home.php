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
    <div class="home-hero">
        <div class="home-overlay"></div>
        <div class="container">
            <div class="home-content">
                <div class="home-logo">
                    <span class="logo-icon">&#x1F4D6;</span>
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

                <div class="home-categories">
                    <?php foreach (ReadingPlan::getCategories() as $category): ?>
                        <span class="category-badge" style="background-color: <?php echo e($category['color']); ?>">
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

                <div class="home-verse" style="margin-top: 2rem; padding: 1.25rem; background: rgba(255,255,255,0.1); border-radius: 12px; border-left: 3px solid rgba(255,255,255,0.4);">
                    <p style="font-family: 'Merriweather', serif; font-style: italic; font-size: 1rem; color: rgba(255,255,255,0.95); margin: 0; line-height: 1.6;">
                        "So faith comes from hearing, and hearing through the word of Christ."
                    </p>
                    <span style="display: block; margin-top: 0.5rem; font-size: 0.85rem; color: rgba(255,255,255,0.7);">— Romans 10:17 (ESV)</span>
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
        </div>
    </footer>

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
