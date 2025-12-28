<?php
$user = Auth::getUser();
$stats = Progress::getStats($user['id']);
$translations = ReadingPlan::getTranslations();

ob_start();
?>

<div class="profile-page">
    <div class="container">
        <div class="page-header">
            <h1>My Profile</h1>
            <p>Manage your account settings and preferences</p>
        </div>

        <div class="profile-grid">
            <!-- Profile Info -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>Account Information</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo e($success); ?></div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/profile" class="profile-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name"
                                   value="<?php echo e($user['name']); ?>"
                                   required minlength="2" maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo e($user['email']); ?>" disabled>
                            <small class="form-hint">Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="preferred_translation">Preferred Translation</label>
                            <select id="preferred_translation" name="preferred_translation">
                                <?php foreach ($translations as $trans): ?>
                                    <option value="<?php echo e($trans['id']); ?>"
                                            <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>>
                                        <?php echo e($trans['name']); ?> (<?php echo e($trans['language']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($passwordSuccess)): ?>
                        <div class="alert alert-success"><?php echo e($passwordSuccess); ?></div>
                    <?php endif; ?>

                    <?php if (isset($passwordError)): ?>
                        <div class="alert alert-error"><?php echo e($passwordError); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/profile" class="profile-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password"
                                   required minlength="6">
                            <small class="form-hint">Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Reading Stats -->
            <div class="profile-card stats-card">
                <div class="card-header">
                    <h2>Your Reading Stats</h2>
                </div>
                <div class="card-body">
                    <div class="stats-overview">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $stats['total_completed']; ?></span>
                            <span class="stat-label">Readings Completed</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $stats['percentage']; ?>%</span>
                            <span class="stat-label">Progress</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $stats['streak']; ?></span>
                            <span class="stat-label">Week Streak</span>
                        </div>
                    </div>

                    <div class="category-stats">
                        <h3>By Category</h3>
                        <?php
                        $categories = ReadingPlan::getCategories();
                        foreach ($categories as $cat):
                            $catCount = $stats['by_category'][$cat['id']] ?? 0;
                            $catPercent = round(($catCount / 52) * 100);
                        ?>
                            <div class="category-progress">
                                <div class="category-label">
                                    <span class="cat-dot" style="background-color: <?php echo e($cat['color']); ?>"></span>
                                    <?php echo e($cat['name']); ?>
                                    <span class="cat-count"><?php echo $catCount; ?>/52</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $catPercent; ?>%; background-color: <?php echo e($cat['color']); ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>Account Details</h2>
                </div>
                <div class="card-body">
                    <div class="account-details">
                        <div class="detail-row">
                            <span class="detail-label">Member Since</span>
                            <span class="detail-value"><?php echo formatDate($user['created_at'], 'F j, Y'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Account Type</span>
                            <span class="detail-value badge-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        <?php if ($user['last_login']): ?>
                        <div class="detail-row">
                            <span class="detail-label">Last Login</span>
                            <span class="detail-value"><?php echo timeAgo($user['last_login']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Password confirmation validation
    document.querySelector('form[action="/profile"] input[name="confirm_password"]')
        ?.closest('form')
        ?.addEventListener('submit', function(e) {
            const newPass = this.querySelector('#new_password').value;
            const confirmPass = this.querySelector('#confirm_password').value;

            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New passwords do not match');
            }
        });
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Profile';
require TEMPLATE_PATH . '/layout.php';
