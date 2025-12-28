<?php
$user = Auth::getUser();
$translations = ReadingPlan::getTranslations();

ob_start();
?>

<div class="settings-page">
    <div class="container">
        <div class="page-header">
            <h1>Settings</h1>
            <p>Customize your reading experience</p>
        </div>

        <div class="settings-grid">
            <!-- Reading Preferences -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>Reading Preferences</h2>
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

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require TEMPLATE_PATH . '/layout.php';
