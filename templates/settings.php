<?php
$user = Auth::getUser();
$translations = ReadingPlan::getTranslations();
$currentTheme = $user['theme'] ?? 'auto';

ob_start();
?>

<div class="settings-page">
    <div class="container">
        <div class="page-header">
            <h1>Settings</h1>
            <p>Customize your reading experience</p>
        </div>

        <div class="settings-grid">
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
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/layout.php';
