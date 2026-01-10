<?php
// Get user ID from URL parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    redirect('/?route=admin/users');
}

$targetUser = User::findById($userId);
if (!$targetUser) {
    setFlash('error', 'User not found.');
    redirect('/?route=admin/users');
}

// Get user stats
$stats = Progress::getStats($userId);
$chapterStats = Progress::getChapterStats($userId);
$badges = Badge::getUserBadges($userId);
$allBadges = Badge::getAll();
$weeklyProgress = Progress::getWeeklyCompletionCounts($userId);
$categories = ReadingPlan::getCategories();

// Calculate weeks completed
$weeksCompleted = 0;
foreach ($weeklyProgress as $count) {
    if ($count === 4) $weeksCompleted++;
}

ob_start();
?>

<div class="user-progress-page">
    <!-- DEBUG: User ID = <?php echo $userId; ?>, Name = <?php echo e($targetUser['name']); ?> -->
    <!-- Header with back button -->
    <div class="page-actions">
        <a href="/?route=admin/users" class="btn btn-outline">
            <span>&larr;</span> Back to Users
        </a>
    </div>

    <!-- User Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($targetUser['name'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h1><?php echo e($targetUser['name']); ?></h1>
            <p class="profile-email"><?php echo e($targetUser['email']); ?></p>
            <div class="profile-meta">
                <span class="role-badge role-<?php echo $targetUser['role']; ?>"><?php echo ucfirst($targetUser['role']); ?></span>
                <span class="meta-sep">|</span>
                <span>Joined <?php echo formatDate($targetUser['created_at'], 'M j, Y'); ?></span>
                <span class="meta-sep">|</span>
                <span>Last active: <?php echo $targetUser['last_login'] ? timeAgo($targetUser['last_login']) : 'Never'; ?></span>
            </div>
        </div>
        <div class="profile-progress-ring">
            <svg viewBox="0 0 100 100">
                <circle class="ring-bg" cx="50" cy="50" r="45"></circle>
                <circle class="ring-fill" cx="50" cy="50" r="45"
                    stroke-dasharray="<?php echo (283 * $stats['percentage'] / 100); ?> 283"
                    style="stroke: <?php echo $stats['percentage'] >= 100 ? '#43A047' : '#5D4037'; ?>"></circle>
            </svg>
            <div class="ring-text">
                <span class="ring-value"><?php echo $stats['percentage']; ?>%</span>
                <span class="ring-label">Complete</span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="quick-stat">
            <span class="qs-value"><?php echo $stats['total_completed']; ?></span>
            <span class="qs-label">of <?php echo $stats['total_readings']; ?> Readings</span>
        </div>
        <div class="quick-stat">
            <span class="qs-value"><?php echo $weeksCompleted; ?></span>
            <span class="qs-label">of 52 Weeks Complete</span>
        </div>
        <div class="quick-stat">
            <span class="qs-value"><?php echo $stats['streak']; ?></span>
            <span class="qs-label">Week Streak</span>
        </div>
        <div class="quick-stat">
            <span class="qs-value"><?php echo count($badges); ?></span>
            <span class="qs-label">of <?php echo count($allBadges); ?> Badges</span>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="content-grid">
        <!-- Left Column -->
        <div class="content-main">
            <!-- Category Progress -->
            <div class="card">
                <div class="card-title">Progress by Category</div>
                <div class="category-list">
                    <?php foreach ($categories as $category):
                        $catCompleted = $stats['by_category'][$category['id']] ?? 0;
                        $catPercent = round(($catCompleted / 52) * 100, 1);
                    ?>
                    <div class="category-row">
                        <div class="cat-info">
                            <span class="cat-dot" style="background: <?php echo e($category['color']); ?>"></span>
                            <span class="cat-name"><?php echo e($category['name']); ?></span>
                        </div>
                        <div class="cat-progress">
                            <div class="cat-bar">
                                <div class="cat-fill" style="width: <?php echo $catPercent; ?>%; background: <?php echo e($category['color']); ?>"></div>
                            </div>
                            <span class="cat-count"><?php echo $catCompleted; ?>/52</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Weekly Progress Grid -->
            <div class="card">
                <div class="card-title">Weekly Progress</div>
                <div class="weeks-grid">
                    <?php for ($week = 1; $week <= 52; $week++):
                        $completed = $weeklyProgress[$week] ?? 0;
                        $statusClass = $completed === 4 ? 'done' : ($completed > 0 ? 'partial' : '');
                    ?>
                    <div class="week-cell <?php echo $statusClass; ?>" title="Week <?php echo $week; ?>: <?php echo $completed; ?>/4">
                        <span class="wk-num"><?php echo $week; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="weeks-legend">
                    <span><span class="leg-box done"></span> Complete</span>
                    <span><span class="leg-box partial"></span> In Progress</span>
                    <span><span class="leg-box"></span> Not Started</span>
                </div>
            </div>
        </div>

        <!-- Right Column - Badges -->
        <div class="content-side">
            <div class="card">
                <div class="card-title">Badges Earned (<?php echo count($badges); ?>/<?php echo count($allBadges); ?>)</div>
                <?php if (empty($badges)): ?>
                    <div class="empty-state">
                        <span class="empty-icon">&#x1F3C6;</span>
                        <p>No badges earned yet</p>
                    </div>
                <?php else: ?>
                    <div class="badge-list">
                        <?php foreach ($badges as $badge): ?>
                        <div class="badge-item">
                            <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                            <div class="badge-details">
                                <span class="badge-name"><?php echo e($badge['name']); ?></span>
                                <span class="badge-desc"><?php echo e($badge['description']); ?></span>
                                <span class="badge-date"><?php echo formatDate($badge['earned_at'], 'M j, Y'); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.user-progress-page {
    max-width: 1200px;
    margin: 0 auto;
}

.page-actions {
    margin-bottom: 1.5rem;
}

.btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-color, #ddd);
    background: white;
    color: var(--text-primary, #333);
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: var(--bg-secondary, #f5f5f5);
    border-color: var(--text-secondary, #666);
}

/* Profile Header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}

.profile-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: var(--primary, #5D4037);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 600;
    flex-shrink: 0;
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.profile-info h1 {
    margin: 0 0 0.25rem;
    font-size: 1.5rem;
    font-weight: 600;
}

.profile-email {
    margin: 0 0 0.5rem;
    color: var(--text-secondary, #666);
    font-size: 0.95rem;
}

.profile-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.85rem;
    color: var(--text-secondary, #666);
    flex-wrap: wrap;
}

.meta-sep {
    color: var(--border-color, #ddd);
}

.role-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.role-admin {
    background: #C62828;
    color: white;
}

.role-user {
    background: var(--bg-secondary, #e9ecef);
    color: var(--text-secondary, #666);
}

/* Progress Ring */
.profile-progress-ring {
    position: relative;
    width: 100px;
    height: 100px;
    flex-shrink: 0;
}

.profile-progress-ring svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.ring-bg {
    fill: none;
    stroke: var(--bg-secondary, #e9ecef);
    stroke-width: 8;
}

.ring-fill {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dasharray 0.5s ease;
}

.ring-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.ring-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary, #333);
}

.ring-label {
    display: block;
    font-size: 0.7rem;
    color: var(--text-secondary, #666);
    text-transform: uppercase;
}

/* Quick Stats */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.quick-stat {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.qs-value {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary, #5D4037);
}

.qs-label {
    display: block;
    font-size: 0.8rem;
    color: var(--text-secondary, #666);
    margin-top: 0.25rem;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary, #333);
}

/* Category Progress */
.category-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.category-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.cat-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 140px;
}

.cat-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.cat-name {
    font-size: 0.9rem;
    font-weight: 500;
}

.cat-progress {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.cat-bar {
    flex: 1;
    height: 6px;
    background: var(--bg-secondary, #e9ecef);
    border-radius: 3px;
    overflow: hidden;
}

.cat-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.cat-count {
    font-size: 0.8rem;
    color: var(--text-secondary, #666);
    min-width: 40px;
    text-align: right;
}

/* Weeks Grid */
.weeks-grid {
    display: grid;
    grid-template-columns: repeat(13, 1fr);
    gap: 4px;
}

.week-cell {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-secondary, #f0f0f0);
    border-radius: 4px;
    font-size: 0.7rem;
    color: var(--text-secondary, #888);
    transition: all 0.2s;
}

.week-cell:hover {
    transform: scale(1.1);
    z-index: 1;
}

.week-cell.done {
    background: #43A047;
    color: white;
}

.week-cell.partial {
    background: #FFB300;
    color: #333;
}

.wk-num {
    font-weight: 500;
}

.weeks-legend {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 1rem;
    font-size: 0.8rem;
    color: var(--text-secondary, #666);
}

.weeks-legend span {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.leg-box {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    background: var(--bg-secondary, #f0f0f0);
}

.leg-box.done {
    background: #43A047;
}

.leg-box.partial {
    background: #FFB300;
}

/* Badges */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--text-secondary, #888);
}

.empty-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 0.9rem;
}

.badge-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.badge-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, #fffbf0 0%, #fff 100%);
    border: 1px solid #f0e6d3;
    border-radius: 8px;
}

.badge-icon {
    font-size: 1.5rem;
    line-height: 1;
}

.badge-details {
    flex: 1;
    min-width: 0;
}

.badge-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.badge-desc {
    display: block;
    font-size: 0.8rem;
    color: var(--text-secondary, #666);
    margin-bottom: 0.2rem;
}

.badge-date {
    display: block;
    font-size: 0.7rem;
    color: var(--text-tertiary, #999);
}

/* Responsive */
@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .content-side {
        order: -1;
    }
}

@media (max-width: 700px) {
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .profile-header {
        flex-wrap: wrap;
    }

    .profile-progress-ring {
        order: -1;
        width: 100%;
        display: flex;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .profile-progress-ring svg {
        width: 80px;
        height: 80px;
    }

    .weeks-grid {
        grid-template-columns: repeat(7, 1fr);
    }
}

@media (max-width: 480px) {
    .profile-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .meta-sep {
        display: none;
    }
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'User Progress - ' . e($targetUser['name']);
require TEMPLATE_PATH . '/admin/layout.php';
