<?php
$userId = intval(get('id', 0));

if (!$userId) {
    redirect('/?route=admin/users');
}

$user = User::findById($userId);
if (!$user) {
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

ob_start();
?>

<div class="user-progress-page">
    <!-- Back button and user header -->
    <div class="page-header-bar">
        <a href="/?route=admin/users" class="btn btn-secondary">
            &#x2190; Back to Users
        </a>
    </div>

    <!-- User Info Card -->
    <div class="admin-card user-info-card">
        <div class="user-header">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <h2><?php echo e($user['name']); ?></h2>
                <p class="user-email"><?php echo e($user['email']); ?></p>
                <div class="user-meta">
                    <span class="badge badge-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                    <span class="meta-item">Joined <?php echo formatDate($user['created_at'], 'M j, Y'); ?></span>
                    <span class="meta-item">Last login: <?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Never'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #43A047;">&#x2705;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['total_completed']; ?>/<?php echo $stats['total_readings']; ?></span>
                <span class="stat-label">Readings Completed</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #1565C0;">&#x1F4CA;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['percentage']; ?>%</span>
                <span class="stat-label">Overall Progress</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #FF8F00;">&#x1F525;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['streak']; ?></span>
                <span class="stat-label">Week Streak</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #8B0000;">&#x1F3C6;</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count($badges); ?>/<?php echo count($allBadges); ?></span>
                <span class="stat-label">Badges Earned</span>
            </div>
        </div>
    </div>

    <!-- Category Progress -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Progress by Category</h2>
        </div>
        <div class="card-body">
            <div class="category-progress-grid">
                <?php foreach ($categories as $category):
                    $catCompleted = $stats['by_category'][$category['id']] ?? 0;
                    $catTotal = 52;
                    $catPercent = round(($catCompleted / $catTotal) * 100, 1);
                ?>
                    <div class="category-progress-item">
                        <div class="category-header">
                            <span class="category-color" style="background-color: <?php echo e($category['color']); ?>;"></span>
                            <span class="category-name"><?php echo e($category['name']); ?></span>
                            <span class="category-count"><?php echo $catCompleted; ?>/<?php echo $catTotal; ?></span>
                        </div>
                        <div class="progress-bar-full">
                            <div class="progress-fill" style="width: <?php echo $catPercent; ?>%; background-color: <?php echo e($category['color']); ?>;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Badges Section -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Badges (<?php echo count($badges); ?>/<?php echo count($allBadges); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($badges)): ?>
                <p class="no-data">No badges earned yet.</p>
            <?php else: ?>
                <div class="badges-grid">
                    <?php foreach ($badges as $badge): ?>
                        <div class="badge-card earned">
                            <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                            <div class="badge-info">
                                <span class="badge-name"><?php echo e($badge['name']); ?></span>
                                <span class="badge-desc"><?php echo e($badge['description']); ?></span>
                                <span class="badge-date">Earned <?php echo formatDate($badge['earned_at'], 'M j, Y'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Weekly Progress Grid -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Weekly Progress</h2>
        </div>
        <div class="card-body">
            <div class="weekly-grid">
                <?php for ($week = 1; $week <= 52; $week++):
                    $completed = $weeklyProgress[$week] ?? 0;
                    $isComplete = $completed === 4;
                    $statusClass = $isComplete ? 'complete' : ($completed > 0 ? 'partial' : 'empty');
                ?>
                    <div class="week-box <?php echo $statusClass; ?>" title="Week <?php echo $week; ?>: <?php echo $completed; ?>/4 readings">
                        <span class="week-num"><?php echo $week; ?></span>
                        <span class="week-count"><?php echo $completed; ?>/4</span>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="weekly-legend">
                <span class="legend-item"><span class="legend-box complete"></span> Complete (4/4)</span>
                <span class="legend-item"><span class="legend-box partial"></span> In Progress</span>
                <span class="legend-item"><span class="legend-box empty"></span> Not Started</span>
            </div>
        </div>
    </div>
</div>

<style>
    .page-header-bar {
        margin-bottom: 1.5rem;
    }

    .user-info-card {
        margin-bottom: 1.5rem;
    }

    .user-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--primary, #5D4037);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 600;
    }

    .user-details h2 {
        margin: 0 0 0.25rem 0;
        font-size: 1.5rem;
    }

    .user-email {
        color: var(--text-secondary, #666);
        margin: 0 0 0.75rem 0;
    }

    .user-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .meta-item {
        font-size: 0.85rem;
        color: var(--text-secondary, #666);
    }

    .category-progress-grid {
        display: grid;
        gap: 1rem;
    }

    .category-progress-item {
        padding: 1rem;
        background: var(--bg-secondary, #f8f9fa);
        border-radius: 8px;
    }

    .category-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .category-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .category-name {
        flex: 1;
        font-weight: 500;
    }

    .category-count {
        font-size: 0.85rem;
        color: var(--text-secondary, #666);
    }

    .progress-bar-full {
        height: 8px;
        background: var(--bg-tertiary, #e9ecef);
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-full .progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .badges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    .badge-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--bg-secondary, #f8f9fa);
        border-radius: 8px;
        border: 2px solid transparent;
    }

    .badge-card.earned {
        border-color: var(--warning, #FFB300);
        background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
    }

    .badge-icon {
        font-size: 2rem;
    }

    .badge-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .badge-name {
        font-weight: 600;
    }

    .badge-desc {
        font-size: 0.85rem;
        color: var(--text-secondary, #666);
    }

    .badge-date {
        font-size: 0.75rem;
        color: var(--text-tertiary, #999);
    }

    .weekly-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 0.5rem;
    }

    .week-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.5rem;
        border-radius: 6px;
        text-align: center;
        min-height: 50px;
    }

    .week-box.complete {
        background: var(--success, #43A047);
        color: white;
    }

    .week-box.partial {
        background: var(--warning, #FFB300);
        color: #333;
    }

    .week-box.empty {
        background: var(--bg-secondary, #f5f5f5);
        color: var(--text-secondary, #666);
    }

    .week-num {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .week-count {
        font-size: 0.7rem;
        opacity: 0.8;
    }

    .weekly-legend {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--text-secondary, #666);
    }

    .legend-box {
        width: 16px;
        height: 16px;
        border-radius: 4px;
    }

    .legend-box.complete {
        background: var(--success, #43A047);
    }

    .legend-box.partial {
        background: var(--warning, #FFB300);
    }

    .legend-box.empty {
        background: var(--bg-secondary, #f5f5f5);
        border: 1px solid var(--border-color, #ddd);
    }

    .no-data {
        text-align: center;
        color: var(--text-secondary, #666);
        padding: 2rem;
    }

    @media (max-width: 600px) {
        .user-header {
            flex-direction: column;
            text-align: center;
        }

        .user-meta {
            justify-content: center;
        }

        .weekly-grid {
            grid-template-columns: repeat(7, 1fr);
        }
    }
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'User Progress - ' . e($user['name']);
require TEMPLATE_PATH . '/admin/layout.php';
