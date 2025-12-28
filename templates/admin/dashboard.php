<?php
$totalUsers = User::count();
$activeUsers = User::countActive();
$globalStats = Progress::getGlobalStats();
$recentUsers = User::getAll(5);

ob_start();
?>

<div class="admin-dashboard">
    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #1565C0;">&#x1F465;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $totalUsers; ?></span>
                <span class="stat-label">Total Users</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #43A047;">&#x2705;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $activeUsers; ?></span>
                <span class="stat-label">Active (30 days)</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #FFB300;">&#x1F4D6;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $globalStats['total_completed']; ?></span>
                <span class="stat-label">Readings Completed</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #8B0000;">&#x1F3C6;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $globalStats['completed_plan']; ?></span>
                <span class="stat-label">Completed Plan</span>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="content-row">
        <!-- Recent Users -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Recent Users</h2>
                <a href="/?route=admin/users" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo e($user['name']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo timeAgo($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="/?route=admin/users" class="action-btn">
                        <span class="action-icon">&#x1F465;</span>
                        <span class="action-text">Manage Users</span>
                    </a>
                    <a href="/?route=admin/reading-plan" class="action-btn">
                        <span class="action-icon">&#x1F4D6;</span>
                        <span class="action-text">Edit Reading Plan</span>
                    </a>
                    <a href="/?route=admin/settings" class="action-btn">
                        <span class="action-icon">&#x2699;</span>
                        <span class="action-text">App Settings</span>
                    </a>
                    <a href="/?route=dashboard" class="action-btn">
                        <span class="action-icon">&#x1F3E0;</span>
                        <span class="action-text">View App</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- App Info -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Application Info</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">App Name</span>
                    <span class="info-value"><?php echo e(ReadingPlan::getAppName()); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Version</span>
                    <span class="info-value"><?php echo APP_VERSION; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">MySQL Version</span>
                    <span class="info-value"><?php echo Database::getInstance()->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registration</span>
                    <span class="info-value">
                        <?php echo Auth::isRegistrationEnabled() ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Default Translation</span>
                    <span class="info-value"><?php echo e(Database::getSetting('default_translation', 'eng_kjv')); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require TEMPLATE_PATH . '/admin/layout.php';
