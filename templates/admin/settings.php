<?php
$appName = Database::getSetting('app_name', 'ReadIn52');
$defaultTranslation = Database::getSetting('default_translation', 'eng_kjv');
$registrationEnabled = Database::getSetting('registration_enabled', '1');
$translations = ReadingPlan::getTranslations();

ob_start();
?>

<div class="admin-settings">
    <div class="admin-card">
        <div class="card-header">
            <h2>Application Settings</h2>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="/?route=admin/settings" class="settings-form">
                <?php echo csrfField(); ?>

                <div class="form-section">
                    <h3>General Settings</h3>

                    <div class="form-group">
                        <label for="app_name">Application Name</label>
                        <input type="text" id="app_name" name="app_name"
                               value="<?php echo e($appName); ?>" required>
                        <small class="form-hint">Displayed in the header and page titles</small>
                    </div>

                    <div class="form-group">
                        <label for="default_translation">Default Translation</label>
                        <select id="default_translation" name="default_translation">
                            <?php foreach ($translations as $trans): ?>
                                <option value="<?php echo e($trans['id']); ?>"
                                        <?php echo $trans['id'] === $defaultTranslation ? 'selected' : ''; ?>>
                                    <?php echo e($trans['name']); ?> (<?php echo e($trans['language']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Default translation for new users</small>
                    </div>
                </div>

                <div class="form-section">
                    <h3>User Registration</h3>

                    <div class="form-group">
                        <label class="toggle-label" style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                            <input type="checkbox" name="registration_enabled" value="1"
                                   <?php echo $registrationEnabled === '1' ? 'checked' : ''; ?>
                                   style="display: none;">
                            <span class="toggle-switch" style="position: relative; width: 48px; min-width: 48px; height: 24px; background-color: <?php echo $registrationEnabled === '1' ? '#43A047' : '#ccc'; ?>; border-radius: 12px; transition: background-color 0.2s;">
                                <span style="content: ''; position: absolute; top: 2px; left: <?php echo $registrationEnabled === '1' ? '26px' : '2px'; ?>; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: left 0.2s;"></span>
                            </span>
                            <span class="toggle-text">Enable User Registration</span>
                        </label>
                        <small class="form-hint">When disabled, only admins can create new accounts</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bible Translations -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Bible Translations</h2>
        </div>
        <div class="card-body">
            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">Available Translations</span>
                    <span class="info-value"><?php echo count($translations); ?></span>
                </div>
            </div>
            <p style="margin: 1rem 0; color: var(--text-secondary, #666);">
                Sync the list of available Bible translations from the HelloAO API to ensure users can access all supported translations.
            </p>
            <form method="POST" action="/?route=admin/settings" style="margin-top: 1rem;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="sync_translations">
                <button type="submit" class="btn btn-primary">Sync Translations from API</button>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="admin-card danger-zone">
        <div class="card-header">
            <h2>Danger Zone</h2>
        </div>
        <div class="card-body">
            <div class="danger-item">
                <div class="danger-info">
                    <h4>Clear All Reading Progress</h4>
                    <p>Delete all reading progress for all users. This cannot be undone.</p>
                </div>
                <form method="POST" action="/?route=admin/settings" onsubmit="return confirm('Are you sure? This will delete ALL reading progress for ALL users!');">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="clear_progress">
                    <button type="submit" class="btn btn-danger">Clear Progress</button>
                </form>
            </div>

            <div class="danger-item">
                <div class="danger-info">
                    <h4>Reset to Default Settings</h4>
                    <p>Reset all settings to their default values.</p>
                </div>
                <form method="POST" action="/?route=admin/settings" onsubmit="return confirm('Are you sure you want to reset all settings?');">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="reset_settings">
                    <button type="submit" class="btn btn-danger">Reset Settings</button>
                </form>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="admin-card">
        <div class="card-header">
            <h2>System Information</h2>
        </div>
        <div class="card-body">
            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">MySQL Version</span>
                    <span class="info-value"><?php echo Database::getInstance()->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Database Name</span>
                    <span class="info-value"><?php echo e(DB_NAME); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Database Host</span>
                    <span class="info-value"><?php echo e(DB_HOST); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">App Version</span>
                    <span class="info-value"><?php echo APP_VERSION; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Server Time</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i:s T'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle switch interaction
document.querySelector('.toggle-label')?.addEventListener('click', function(e) {
    var checkbox = this.querySelector('input[type="checkbox"]');
    var toggleSwitch = this.querySelector('.toggle-switch');
    var knob = toggleSwitch.querySelector('span');

    // Toggle state will be handled by the checkbox
    setTimeout(function() {
        if (checkbox.checked) {
            toggleSwitch.style.backgroundColor = '#43A047';
            knob.style.left = '26px';
        } else {
            toggleSwitch.style.backgroundColor = '#ccc';
            knob.style.left = '2px';
        }
    }, 10);
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/admin/layout.php';
