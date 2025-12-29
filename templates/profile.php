<?php
$user = Auth::getUser();
$stats = Progress::getStats($user['id']);
$initials = getUserInitials($user['name']);
$avatarColor = getAvatarColor($user['name']);
$categories = ReadingPlan::getCategories();

ob_start();
?>

<div class="profile-page">
    <div class="container" style="max-width: 900px;">
        <!-- Profile Header -->
        <div class="profile-header" style="display: flex; align-items: center; gap: 1.5rem; padding: 2rem; background: var(--card-bg, #fff); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="avatar-large" style="background-color: <?php echo e($avatarColor); ?>;">
                <?php echo e($initials); ?>
            </div>
            <div class="profile-header-info" style="flex: 1;">
                <h1 style="margin: 0 0 0.25rem 0; font-size: 1.5rem;"><?php echo e($user['name']); ?></h1>
                <p style="margin: 0 0 0.5rem 0; color: var(--text-secondary, #666);"><?php echo e($user['email']); ?></p>
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    <span style="font-size: 0.85rem; color: var(--text-muted, #888);">Member since <?php echo formatDate($user['created_at'], 'M Y'); ?></span>
                </div>
            </div>
            <a href="/?route=settings" class="btn btn-outline" style="flex-shrink: 0;">Edit Settings</a>
        </div>

        <!-- Stats Banner -->
        <div class="stats-banner" style="display: grid; grid-template-columns: repeat(4, 1fr); background: linear-gradient(135deg, #5D4037 0%, #4E342E 100%); border-radius: 12px; margin-top: 1.5rem; overflow: hidden;">
            <div style="text-align: center; padding: 1.25rem;">
                <span style="display: block; font-size: 1.75rem; font-weight: 700; color: #fff;"><?php echo $stats['total_completed']; ?></span>
                <span style="font-size: 0.8rem; color: rgba(255,255,255,0.85);">Readings</span>
            </div>
            <div style="text-align: center; padding: 1.25rem; border-left: 1px solid rgba(255,255,255,0.15);">
                <span style="display: block; font-size: 1.75rem; font-weight: 700; color: #fff;"><?php echo $stats['percentage']; ?>%</span>
                <span style="font-size: 0.8rem; color: rgba(255,255,255,0.85);">Complete</span>
            </div>
            <div style="text-align: center; padding: 1.25rem; border-left: 1px solid rgba(255,255,255,0.15);">
                <span style="display: block; font-size: 1.75rem; font-weight: 700; color: #fff;"><?php echo $stats['streak']; ?></span>
                <span style="font-size: 0.8rem; color: rgba(255,255,255,0.85);">Week Streak</span>
            </div>
            <div style="text-align: center; padding: 1.25rem; border-left: 1px solid rgba(255,255,255,0.15);">
                <span style="display: block; font-size: 1.75rem; font-weight: 700; color: #fff;"><?php echo $stats['current_week']; ?></span>
                <span style="font-size: 0.8rem; color: rgba(255,255,255,0.85);">Current Week</span>
            </div>
        </div>

        <!-- Progress by Category -->
        <div class="profile-card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h2>Progress by Category</h2>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <?php foreach ($categories as $cat):
                        $catCount = $stats['by_category'][$cat['id']] ?? 0;
                        $catPercent = round(($catCount / 52) * 100);
                    ?>
                    <div style="background: var(--background, #f8f8f8); padding: 1rem; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo e($cat['color']); ?>;"></span>
                                <?php echo e($cat['name']); ?>
                            </span>
                            <span style="font-size: 0.85rem; color: var(--text-secondary, #666);"><?php echo $catCount; ?>/52</span>
                        </div>
                        <div style="background: var(--border-color, #e0e0e0); height: 6px; border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $catPercent; ?>%; height: 100%; background: <?php echo e($cat['color']); ?>; border-radius: 3px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Account Details -->
        <div class="profile-card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h2>Account Details</h2>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                    <div>
                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted, #888); margin-bottom: 0.25rem;">Member Since</span>
                        <span style="font-weight: 500;"><?php echo formatDate($user['created_at'], 'F j, Y'); ?></span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted, #888); margin-bottom: 0.25rem;">Account Type</span>
                        <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <?php if ($user['last_login']): ?>
                    <div>
                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted, #888); margin-bottom: 0.25rem;">Last Login</span>
                        <span style="font-weight: 500;"><?php echo timeAgo($user['last_login']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Profile';
require TEMPLATE_PATH . '/layout.php';
