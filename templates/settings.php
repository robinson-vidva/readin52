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

                            <?php
                            // Find current primary language
                            $primaryLang = 'English';
                            foreach ($translationsByLanguage as $lang => $translations) {
                                foreach ($translations as $t) {
                                    if ($t['id'] === $user['preferred_translation']) {
                                        $primaryLang = $lang;
                                        break 2;
                                    }
                                }
                            }

                            // Find current secondary language
                            $secondaryLang = '';
                            $secondaryTrans = $user['secondary_translation'] ?? '';
                            if ($secondaryTrans) {
                                foreach ($translationsByLanguage as $lang => $translations) {
                                    foreach ($translations as $t) {
                                        if ($t['id'] === $secondaryTrans) {
                                            $secondaryLang = $lang;
                                            break 2;
                                        }
                                    }
                                }
                            }
                            ?>

                            <div class="form-group">
                                <label>Primary Translation</label>
                                <input type="hidden" id="preferred_translation" name="preferred_translation" value="<?php echo e($user['preferred_translation']); ?>">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <select id="primaryLangSelect" class="form-control" style="flex: 1; min-width: 140px;">
                                        <?php foreach ($translationsByLanguage as $language => $langTranslations): ?>
                                            <option value="<?php echo e($language); ?>" <?php echo $language === $primaryLang ? 'selected' : ''; ?>>
                                                <?php echo e($language); ?> (<?php echo count($langTranslations); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="primaryTransSelect" class="form-control" style="flex: 2; min-width: 180px;">
                                        <?php foreach ($translationsByLanguage[$primaryLang] ?? [] as $trans): ?>
                                            <option value="<?php echo e($trans['id']); ?>" <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>>
                                                <?php echo e($trans['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small class="form-hint">Your main Bible translation for reading</small>
                            </div>

                            <div class="form-group">
                                <label>Secondary Translation (Optional)</label>
                                <input type="hidden" id="secondary_translation" name="secondary_translation" value="<?php echo e($user['secondary_translation'] ?? ''); ?>">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <select id="secondaryLangSelect" class="form-control" style="flex: 1; min-width: 140px;">
                                        <option value="">None</option>
                                        <?php foreach ($translationsByLanguage as $language => $langTranslations): ?>
                                            <option value="<?php echo e($language); ?>" <?php echo $language === $secondaryLang ? 'selected' : ''; ?>>
                                                <?php echo e($language); ?> (<?php echo count($langTranslations); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="secondaryTransSelect" class="form-control" style="flex: 2; min-width: 180px;" <?php echo empty($secondaryTrans) ? 'disabled' : ''; ?>>
                                        <?php if (empty($secondaryTrans)): ?>
                                            <option value="">Select a language first</option>
                                        <?php else: ?>
                                            <?php foreach ($translationsByLanguage[$secondaryLang] ?? [] as $trans): ?>
                                                <option value="<?php echo e($trans['id']); ?>" <?php echo $trans['id'] === $secondaryTrans ? 'selected' : ''; ?>>
                                                    <?php echo e($trans['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
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

// Translation data grouped by language
const translationsByLanguage = <?php echo json_encode($translationsByLanguage); ?>;

// Two-dropdown translation selector
document.addEventListener('DOMContentLoaded', function() {
    // Primary translation dropdowns
    const primaryLangSelect = document.getElementById('primaryLangSelect');
    const primaryTransSelect = document.getElementById('primaryTransSelect');
    const primaryInput = document.getElementById('preferred_translation');

    if (primaryLangSelect && primaryTransSelect && primaryInput) {
        primaryLangSelect.addEventListener('change', function() {
            const selectedLang = this.value;
            const translations = translationsByLanguage[selectedLang] || [];
            primaryTransSelect.innerHTML = '';
            translations.forEach(trans => {
                const option = document.createElement('option');
                option.value = trans.id;
                option.textContent = trans.name;
                primaryTransSelect.appendChild(option);
            });
            if (translations.length > 0) {
                primaryInput.value = translations[0].id;
            }
        });

        primaryTransSelect.addEventListener('change', function() {
            primaryInput.value = this.value;
        });
    }

    // Secondary translation dropdowns
    const secondaryLangSelect = document.getElementById('secondaryLangSelect');
    const secondaryTransSelect = document.getElementById('secondaryTransSelect');
    const secondaryInput = document.getElementById('secondary_translation');

    if (secondaryLangSelect && secondaryTransSelect && secondaryInput) {
        secondaryLangSelect.addEventListener('change', function() {
            const selectedLang = this.value;

            if (!selectedLang) {
                // None selected - disable translation dropdown
                secondaryTransSelect.disabled = true;
                secondaryTransSelect.innerHTML = '<option value="">Select a language first</option>';
                secondaryInput.value = '';
            } else {
                // Language selected - populate translation dropdown
                secondaryTransSelect.disabled = false;
                const translations = translationsByLanguage[selectedLang] || [];
                secondaryTransSelect.innerHTML = '';
                translations.forEach(trans => {
                    const option = document.createElement('option');
                    option.value = trans.id;
                    option.textContent = trans.name;
                    secondaryTransSelect.appendChild(option);
                });
                if (translations.length > 0) {
                    secondaryInput.value = translations[0].id;
                }
            }
        });

        secondaryTransSelect.addEventListener('change', function() {
            secondaryInput.value = this.value;
        });
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/layout.php';
