<?php
$appName = Database::getSetting('app_name', 'ReadIn52');
$defaultTranslation = Database::getSetting('default_translation', 'eng_kjv');
$registrationEnabled = Database::getSetting('registration_enabled', '1');
$translations = ReadingPlan::getTranslations();
$translationsByLanguage = ReadingPlan::getTranslationsGroupedByLanguage();

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
                        <input type="hidden" id="default_translation" name="default_translation" value="<?php echo e($defaultTranslation); ?>">
                        <div class="searchable-select" id="defaultTransSelect" style="position: relative; width: 100%;">
                            <button type="button" class="searchable-select-trigger" aria-haspopup="listbox" style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; background: #fff; cursor: pointer; text-align: left; font-size: 1rem;">
                                <span class="selected-text" style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php
                                    foreach ($translationsByLanguage as $lang => $langTranslations) {
                                        foreach ($langTranslations as $t) {
                                            if ($t['id'] === $defaultTranslation) {
                                                echo e($lang . ' (' . $t['name'] . ')');
                                                break 2;
                                            }
                                        }
                                    }
                                    ?>
                                </span>
                                <span class="arrow">&#9662;</span>
                            </button>
                            <div class="searchable-select-dropdown" style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1000; max-height: 320px; overflow: hidden;">
                                <div class="searchable-select-search" style="padding: 0.75rem; border-bottom: 1px solid #eee; background: #fff;">
                                    <input type="text" placeholder="Search translations..." autocomplete="off" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div class="searchable-select-options" style="max-height: 250px; overflow-y: auto;">
                                    <?php foreach ($translationsByLanguage as $language => $langTranslations): ?>
                                        <div class="searchable-select-group"><?php echo e($language); ?></div>
                                        <?php foreach ($langTranslations as $trans): ?>
                                            <div class="searchable-select-option <?php echo $trans['id'] === $defaultTranslation ? 'selected' : ''; ?>"
                                                 data-value="<?php echo e($trans['id']); ?>"
                                                 data-label="<?php echo e($language . ' (' . $trans['name'] . ')'); ?>"
                                                 data-search="<?php echo e(strtolower($language . ' ' . $trans['name'])); ?>">
                                                <?php echo e($language); ?> (<?php echo e($trans['name']); ?>)
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
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

// Searchable Select Component
function initSearchableSelect(container, hiddenInput) {
    const trigger = container.querySelector('.searchable-select-trigger');
    const searchInput = container.querySelector('.searchable-select-search input');
    const options = container.querySelectorAll('.searchable-select-option');
    const selectedText = trigger.querySelector('.selected-text');

    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = container.classList.contains('open');
        document.querySelectorAll('.searchable-select.open').forEach(el => {
            if (el !== container) el.classList.remove('open');
        });
        container.classList.toggle('open');
        if (!isOpen) {
            searchInput.value = '';
            filterOptions('');
            searchInput.focus();
        }
    });

    searchInput.addEventListener('input', function() {
        filterOptions(this.value.toLowerCase());
    });

    searchInput.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    function filterOptions(query) {
        options.forEach(option => {
            const searchText = option.dataset.search || option.textContent.toLowerCase();
            option.classList.toggle('hidden', query !== '' && !searchText.includes(query));
        });
        const groups = container.querySelectorAll('.searchable-select-group');
        groups.forEach(group => {
            let nextSibling = group.nextElementSibling;
            let hasVisibleOption = false;
            while (nextSibling && !nextSibling.classList.contains('searchable-select-group')) {
                if (nextSibling.classList.contains('searchable-select-option') &&
                    !nextSibling.classList.contains('hidden')) {
                    hasVisibleOption = true;
                    break;
                }
                nextSibling = nextSibling.nextElementSibling;
            }
            group.style.display = hasVisibleOption ? '' : 'none';
        });
    }

    options.forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();
            const value = this.dataset.value;
            const label = this.dataset.label;
            options.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            selectedText.textContent = label;
            hiddenInput.value = value;
            container.classList.remove('open');
        });
    });

    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            container.classList.remove('open');
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            container.classList.remove('open');
            trigger.focus();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const visibleOptions = Array.from(options).filter(o => !o.classList.contains('hidden'));
            if (visibleOptions.length > 0) visibleOptions[0].click();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const defaultSelect = document.getElementById('defaultTransSelect');
    const defaultInput = document.getElementById('default_translation');
    if (defaultSelect && defaultInput) {
        initSearchableSelect(defaultSelect, defaultInput);
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/admin/layout.php';
