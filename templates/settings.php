<?php
$user = Auth::getUser();
$translationsByLanguage = ReadingPlan::getTranslationsGroupedByLanguage();
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

        <div class="settings-content" style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem;">
            <!-- Main Settings Column -->
            <div class="settings-main" style="display: flex; flex-direction: column; gap: 1.5rem;">
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
                                <label for="preferred_translation">Primary Translation</label>
                                <input type="hidden" id="preferred_translation" name="preferred_translation" value="<?php echo e($user['preferred_translation']); ?>">
                                <div class="searchable-select" id="primaryTransSelect">
                                    <button type="button" class="searchable-select-trigger" aria-haspopup="listbox">
                                        <span class="selected-text">
                                            <?php
                                            foreach ($translationsByLanguage as $lang => $translations) {
                                                foreach ($translations as $t) {
                                                    if ($t['id'] === $user['preferred_translation']) {
                                                        echo e($lang . ' (' . $t['name'] . ')');
                                                        break 2;
                                                    }
                                                }
                                            }
                                            ?>
                                        </span>
                                        <span class="arrow">&#9662;</span>
                                    </button>
                                    <div class="searchable-select-dropdown">
                                        <div class="searchable-select-search">
                                            <input type="text" placeholder="Search translations..." autocomplete="off">
                                        </div>
                                        <div class="searchable-select-options">
                                            <?php foreach ($translationsByLanguage as $language => $langTranslations): ?>
                                                <div class="searchable-select-group"><?php echo e($language); ?></div>
                                                <?php foreach ($langTranslations as $trans): ?>
                                                    <div class="searchable-select-option <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>"
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
                                <small class="form-hint">Your main Bible translation for reading</small>
                            </div>

                            <div class="form-group">
                                <label for="secondary_translation">Secondary Translation (Optional)</label>
                                <input type="hidden" id="secondary_translation" name="secondary_translation" value="<?php echo e($user['secondary_translation'] ?? ''); ?>">
                                <div class="searchable-select" id="secondaryTransSelect">
                                    <button type="button" class="searchable-select-trigger" aria-haspopup="listbox">
                                        <span class="selected-text">
                                            <?php
                                            $found = false;
                                            $secondaryTrans = $user['secondary_translation'] ?? '';
                                            if ($secondaryTrans) {
                                                foreach ($translationsByLanguage as $lang => $translations) {
                                                    foreach ($translations as $t) {
                                                        if ($t['id'] === $secondaryTrans) {
                                                            echo e($lang . ' (' . $t['name'] . ')');
                                                            $found = true;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                            if (!$found) echo 'None - Single translation only';
                                            ?>
                                        </span>
                                        <span class="arrow">&#9662;</span>
                                    </button>
                                    <div class="searchable-select-dropdown">
                                        <div class="searchable-select-search">
                                            <input type="text" placeholder="Search translations..." autocomplete="off">
                                        </div>
                                        <div class="searchable-select-options">
                                            <div class="searchable-select-option <?php echo empty($user['secondary_translation']) ? 'selected' : ''; ?>"
                                                 data-value=""
                                                 data-label="None - Single translation only"
                                                 data-search="none single">
                                                None - Single translation only
                                            </div>
                                            <?php foreach ($translationsByLanguage as $language => $langTranslations): ?>
                                                <div class="searchable-select-group"><?php echo e($language); ?></div>
                                                <?php foreach ($langTranslations as $trans): ?>
                                                    <div class="searchable-select-option <?php echo $trans['id'] === ($user['secondary_translation'] ?? '') ? 'selected' : ''; ?>"
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
                                <small class="form-hint">Compare with a second translation while reading</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with Account Info -->
            <div class="settings-sidebar">
                <div class="profile-card" style="background: var(--card-bg, #fff); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="settings-account-preview" style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 1rem;">
                            <div class="avatar-medium" style="background-color: <?php echo e($avatarColor); ?>; width: 64px; height: 64px; min-width: 64px; min-height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.5rem; text-transform: uppercase; margin-bottom: 1rem;">
                                <?php echo e($initials); ?>
                            </div>
                            <div class="account-preview-info" style="display: flex; flex-direction: column; gap: 0.25rem;">
                                <strong style="font-size: 1rem;"><?php echo e($user['name']); ?></strong>
                                <span style="color: var(--text-muted, #666); font-size: 0.875rem;"><?php echo e($user['email']); ?></span>
                            </div>
                        </div>
                        <a href="/?route=profile" class="btn btn-outline btn-block" style="display: block; width: 100%; text-align: center; padding: 0.75rem 1rem; border: 2px solid var(--primary, #5D4037); color: var(--primary, #5D4037); border-radius: 8px; text-decoration: none; font-weight: 600;">Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone Section -->
        <div class="danger-zone-section" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-color, #e0e0e0);">
            <h2 class="section-title danger-title" style="color: #C62828; font-size: 1.25rem; margin-bottom: 0.5rem;">Danger Zone</h2>
            <p class="section-description" style="color: var(--text-muted, #666); margin-bottom: 1.5rem;">These actions are permanent and cannot be undone.</p>

            <div class="danger-zone-cards" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
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

// Searchable Select Component
function initSearchableSelect(container, hiddenInput) {
    const trigger = container.querySelector('.searchable-select-trigger');
    const dropdown = container.querySelector('.searchable-select-dropdown');
    const searchInput = container.querySelector('.searchable-select-search input');
    const options = container.querySelectorAll('.searchable-select-option');
    const selectedText = trigger.querySelector('.selected-text');

    // Toggle dropdown
    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = container.classList.contains('open');

        // Close all other dropdowns
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

    // Search functionality
    searchInput.addEventListener('input', function() {
        filterOptions(this.value.toLowerCase());
    });

    searchInput.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    function filterOptions(query) {
        let hasVisible = false;
        let lastVisibleGroup = null;

        options.forEach(option => {
            const searchText = option.dataset.search || option.textContent.toLowerCase();
            if (query === '' || searchText.includes(query)) {
                option.classList.remove('hidden');
                hasVisible = true;
            } else {
                option.classList.add('hidden');
            }
        });

        // Hide/show group headers based on visible options
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

    // Select option
    options.forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();
            const value = this.dataset.value;
            const label = this.dataset.label;

            // Update selected state
            options.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');

            // Update display and hidden input
            selectedText.textContent = label;
            hiddenInput.value = value;

            // Close dropdown
            container.classList.remove('open');
        });
    });

    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            container.classList.remove('open');
        }
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            container.classList.remove('open');
            trigger.focus();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const visibleOptions = Array.from(options).filter(o => !o.classList.contains('hidden'));
            if (visibleOptions.length > 0) {
                visibleOptions[0].click();
            }
        }
    });
}

// Initialize searchable selects
document.addEventListener('DOMContentLoaded', function() {
    const primarySelect = document.getElementById('primaryTransSelect');
    const primaryInput = document.getElementById('preferred_translation');
    if (primarySelect && primaryInput) {
        initSearchableSelect(primarySelect, primaryInput);
    }

    const secondarySelect = document.getElementById('secondaryTransSelect');
    const secondaryInput = document.getElementById('secondary_translation');
    if (secondarySelect && secondaryInput) {
        initSearchableSelect(secondarySelect, secondaryInput);
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/layout.php';
