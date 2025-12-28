<?php
$user = Auth::getUser();
$stats = Progress::getStats($user['id']);
$initials = getUserInitials($user['name']);
$avatarColor = getAvatarColor($user['name']);

ob_start();
?>

<div class="profile-page">
    <div class="container">
        <!-- Profile Header with Avatar -->
        <div class="profile-header">
            <div class="avatar-large" style="background-color: <?php echo e($avatarColor); ?>">
                <?php echo e($initials); ?>
            </div>
            <div class="profile-header-info">
                <h1><?php echo e($user['name']); ?></h1>
                <p class="profile-email"><?php echo e($user['email']); ?></p>
                <div class="profile-meta">
                    <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    <span class="meta-item">Member since <?php echo formatDate($user['created_at'], 'M Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- Reading Stats Overview -->
        <div class="stats-banner">
            <div class="stat-item">
                <span class="stat-value"><?php echo $stats['total_completed']; ?></span>
                <span class="stat-label">Readings</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $stats['percentage']; ?>%</span>
                <span class="stat-label">Complete</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $stats['streak']; ?></span>
                <span class="stat-label">Week Streak</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $stats['current_week']; ?></span>
                <span class="stat-label">Current Week</span>
            </div>
        </div>

        <div class="profile-content">
            <!-- Left Column -->
            <div class="profile-main">
                <!-- Account Information -->
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

                        <form method="POST" action="/?route=profile" class="profile-form">
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

                        <form method="POST" action="/?route=profile" class="profile-form">
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
            </div>

            <!-- Right Column - Stats -->
            <div class="profile-sidebar">
                <!-- Category Progress -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>Progress by Category</h2>
                    </div>
                    <div class="card-body">
                        <div class="category-stats">
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

                <!-- Account Details -->
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
                                <span class="detail-value">
                                    <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
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
</div>

<script>
    // Password confirmation validation
    document.querySelector('form[action="/?route=profile"] input[name="confirm_password"]')
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
