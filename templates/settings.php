<?php
$user = Auth::getUser();
$translations = ReadingPlan::getTranslations();
$currentTheme = $user['theme'] ?? 'auto';
$initials = getUserInitials($user['name']);
$avatarColor = getAvatarColor($user['name']);

ob_start();
?>

<div class="settings-page">
    <div class="container">
        <div class="page-header">
            <h1>Settings</h1>
            <p>Customize your reading experience</p>
        </div>

        <div class="settings-content">
            <!-- Main Settings Column -->
            <div class="settings-main">
                <!-- Appearance -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Appearance</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo e($success); ?></div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?php echo e($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="/?route=settings" class="profile-form">
                            <?php echo csrfField(); ?>

                            <div class="form-group">
                                <label>Theme</label>
                                <div class="theme-selector">
                                    <label class="theme-option <?php echo $currentTheme === 'light' ? 'active' : ''; ?>">
                                        <input type="radio" name="theme" value="light" <?php echo $currentTheme === 'light' ? 'checked' : ''; ?>>
                                        <span class="theme-icon">&#9728;</span>
                                        <span class="theme-label">Light</span>
                                    </label>
                                    <label class="theme-option <?php echo $currentTheme === 'dark' ? 'active' : ''; ?>">
                                        <input type="radio" name="theme" value="dark" <?php echo $currentTheme === 'dark' ? 'checked' : ''; ?>>
                                        <span class="theme-icon">&#9790;</span>
                                        <span class="theme-label">Dark</span>
                                    </label>
                                    <label class="theme-option <?php echo $currentTheme === 'auto' ? 'active' : ''; ?>">
                                        <input type="radio" name="theme" value="auto" <?php echo $currentTheme === 'auto' ? 'checked' : ''; ?>>
                                        <span class="theme-icon">&#9881;</span>
                                        <span class="theme-label">Auto</span>
                                    </label>
                                </div>
                                <small class="form-hint">Auto follows your device's system preference</small>
                            </div>

                            <div class="form-group">
                                <label for="preferred_translation">Bible Translation</label>
                                <select id="preferred_translation" name="preferred_translation">
                                    <?php foreach ($translations as $trans): ?>
                                        <option value="<?php echo e($trans['id']); ?>"
                                                <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>>
                                            <?php echo e($trans['name']); ?> (<?php echo e($trans['language']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-hint">Select your preferred Bible translation for reading</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with Account Info -->
            <div class="settings-sidebar">
                <div class="profile-card">
                    <div class="card-body">
                        <div class="settings-account-preview">
                            <div class="avatar-medium" style="background-color: <?php echo e($avatarColor); ?>">
                                <?php echo e($initials); ?>
                            </div>
                            <div class="account-preview-info">
                                <strong><?php echo e($user['name']); ?></strong>
                                <span><?php echo e($user['email']); ?></span>
                            </div>
                        </div>
                        <a href="/?route=profile" class="btn btn-outline btn-block">Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone Section -->
        <div class="danger-zone-section">
            <h2 class="section-title danger-title">Danger Zone</h2>
            <p class="section-description">These actions are permanent and cannot be undone.</p>

            <div class="danger-zone-cards">
                <!-- Reset Progress -->
                <div class="profile-card danger-zone">
                    <div class="card-header">
                        <h2>Reset Progress</h2>
                    </div>
                    <div class="card-body">
                        <p class="danger-text">This will permanently delete all your reading progress. You'll start fresh from Week 1.</p>

                        <form method="POST" action="/?route=settings/reset-progress" class="profile-form" id="resetProgressForm">
                            <?php echo csrfField(); ?>

                            <div class="form-group">
                                <label for="reset_password">Enter your password to confirm</label>
                                <input type="password" id="reset_password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-danger">Reset All Progress</button>
                        </form>
                    </div>
                </div>

                <!-- Delete Account -->
                <div class="profile-card danger-zone">
                    <div class="card-header">
                        <h2>Delete Account</h2>
                    </div>
                    <div class="card-body">
                        <p class="danger-text">This will permanently delete your account and all associated data. This action cannot be undone.</p>

                        <form method="POST" action="/?route=settings/delete-account" class="profile-form" id="deleteAccountForm">
                            <?php echo csrfField(); ?>

                            <div class="form-group">
                                <label for="delete_password">Enter your password to confirm</label>
                                <input type="password" id="delete_password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-danger">Delete My Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live theme preview
document.querySelectorAll('.theme-option input').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
        this.closest('.theme-option').classList.add('active');

        // Apply theme immediately for preview
        const theme = this.value;
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            // Auto - check system preference
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        }
    });
});

// Reset Progress confirmation
document.getElementById('resetProgressForm')?.addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to reset all your reading progress? This cannot be undone.')) {
        e.preventDefault();
    }
});

// Delete Account confirmation
document.getElementById('deleteAccountForm')?.addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to permanently delete your account? This action cannot be undone.')) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/layout.php';
