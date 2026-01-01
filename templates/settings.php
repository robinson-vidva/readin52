<?php
$user = Auth::getUser();
$translationsByLanguage = ReadingPlan::getTranslationsGroupedByLanguage();
$currentTheme = $user['theme'] ?? 'auto';

ob_start();
?>

<div class="settings-page">
    <div class="container" style="max-width: 700px;">
        <div class="page-header" style="margin-bottom: 1.5rem;">
            <h1>Settings</h1>
            <p style="color: var(--text-secondary, #666);">Manage your account and preferences</p>
        </div>

        <!-- Account Section -->
        <div class="settings-section">
            <h2 class="section-title">Account</h2>
            <div class="settings-card">
                <?php if (isset($nameSuccess)): ?>
                    <div class="alert alert-success"><?php echo e($nameSuccess); ?></div>
                <?php endif; ?>
                <?php if (isset($nameError)): ?>
                    <div class="alert alert-error"><?php echo e($nameError); ?></div>
                <?php endif; ?>

                <form method="POST" action="/?route=settings">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_name">

                    <div class="form-group">
                        <label for="name">Display Name</label>
                        <input type="text" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Name</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Email Section -->
        <div class="settings-section">
            <h2 class="section-title">Email Address</h2>
            <div class="settings-card">
                <?php if (isset($emailSuccess)): ?>
                    <div class="alert alert-success"><?php echo e($emailSuccess); ?></div>
                <?php endif; ?>
                <?php if (isset($emailError)): ?>
                    <div class="alert alert-error"><?php echo e($emailError); ?></div>
                <?php endif; ?>

                <form method="POST" action="/?route=settings">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="change_email">

                    <div class="form-group">
                        <label>Current Email</label>
                        <input type="email" value="<?php echo e($user['email']); ?>" disabled style="background: var(--background, #f5f5f5); cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="new_email">New Email Address</label>
                        <input type="email" id="new_email" name="new_email" required placeholder="Enter new email address">
                        <small class="form-hint">A verification link will be sent to the new email</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="email_password">Confirm Password</label>
                        <input type="password" id="email_password" name="password" required placeholder="Enter your password to confirm">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Change Email</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Section -->
        <div class="settings-section">
            <h2 class="section-title">Security</h2>
            <div class="settings-card">
                <?php if (isset($passwordSuccess)): ?>
                    <div class="alert alert-success"><?php echo e($passwordSuccess); ?></div>
                <?php endif; ?>
                <?php if (isset($passwordError)): ?>
                    <div class="alert alert-error"><?php echo e($passwordError); ?></div>
                <?php endif; ?>

                <form method="POST" action="/?route=settings">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <small class="form-hint" style="display: block; margin-top: 0.25rem;">Minimum 6 characters</small>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preferences Section -->
        <div class="settings-section">
            <h2 class="section-title">Preferences</h2>
            <div class="settings-card">
                <?php if (isset($prefsSuccess)): ?>
                    <div class="alert alert-success"><?php echo e($prefsSuccess); ?></div>
                <?php endif; ?>
                <?php if (isset($prefsError)): ?>
                    <div class="alert alert-error"><?php echo e($prefsError); ?></div>
                <?php endif; ?>

                <form method="POST" action="/?route=settings">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_preferences">

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

                    <div class="form-group" style="margin-bottom: 0;">
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

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone Section -->
        <div class="settings-section danger-section">
            <h2 class="section-title" style="color: #C62828;">Danger Zone</h2>
            <p style="color: var(--text-muted, #666); margin-bottom: 1rem; font-size: 0.9rem;">These actions are permanent and cannot be undone.</p>

            <div class="settings-card danger-card">
                <div class="danger-item">
                    <div class="danger-info">
                        <h3>Reset Reading Progress</h3>
                        <p>Delete all your reading progress and start fresh from Week 1.</p>
                    </div>
                    <form method="POST" action="/?route=settings/reset-progress" id="resetProgressForm">
                        <?php echo csrfField(); ?>
                        <div class="danger-form-row">
                            <input type="password" name="password" placeholder="Enter password" required>
                            <button type="submit" class="btn btn-danger">Reset Progress</button>
                        </div>
                    </form>
                </div>

                <div class="danger-divider"></div>

                <div class="danger-item">
                    <div class="danger-info">
                        <h3>Delete Account</h3>
                        <p>Permanently delete your account and all associated data.</p>
                    </div>
                    <form method="POST" action="/?route=settings/delete-account" id="deleteAccountForm">
                        <?php echo csrfField(); ?>
                        <div class="danger-form-row">
                            <input type="password" name="password" placeholder="Enter password" required>
                            <button type="submit" class="btn btn-danger">Delete Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-page {
    padding: 2rem 1rem;
}

.settings-section {
    margin-bottom: 2rem;
}

.settings-section .section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary, #333);
}

.settings-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.settings-card .form-group {
    margin-bottom: 1.25rem;
}

.settings-card .form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.settings-card .form-actions {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #eee);
}

/* Danger Zone */
.danger-section .settings-card {
    border: 1px solid #ffcdd2;
    background: #fff;
}

.danger-item {
    padding: 1rem 0;
}

.danger-item:first-child {
    padding-top: 0;
}

.danger-item:last-child {
    padding-bottom: 0;
}

.danger-info h3 {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: var(--text-primary, #333);
}

.danger-info p {
    font-size: 0.85rem;
    color: var(--text-muted, #666);
    margin: 0 0 0.75rem 0;
}

.danger-form-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.danger-form-row input {
    flex: 1;
    max-width: 200px;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 6px;
    font-size: 0.9rem;
}

.danger-divider {
    height: 1px;
    background: #ffcdd2;
    margin: 0.5rem 0;
}

/* Mobile adjustments */
@media (max-width: 600px) {
    .settings-page {
        padding: 1rem;
    }

    .settings-card {
        padding: 1.25rem;
    }

    .form-row {
        grid-template-columns: 1fr !important;
    }

    .danger-form-row {
        flex-direction: column;
        align-items: stretch;
    }

    .danger-form-row input {
        max-width: none;
    }
}
</style>

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
