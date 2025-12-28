<?php
$user = Auth::getUser();
$stats = Progress::getStats($user['id']);
$currentWeek = get('week', $stats['current_week']);
$currentWeek = max(1, min(52, intval($currentWeek)));
$weekData = ReadingPlan::getWeekWithDetails($currentWeek);
$weekProgress = Progress::getWeekProgress($user['id'], $currentWeek);
$categories = ReadingPlan::getCategories();
$weeklyCompletion = Progress::getWeeklyCompletionCounts($user['id']);

$completedThisWeek = 0;
foreach ($weekProgress as $p) {
    if ($p['completed']) $completedThisWeek++;
}

ob_start();
?>

<div class="dashboard">
    <div class="container">
        <!-- Stats Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome, <?php echo e($user['name']); ?>!</h1>
                <p class="tagline">Your Bible Reading Journey</p>
            </div>

            <div class="progress-overview">
                <div class="progress-circle" data-progress="<?php echo $stats['percentage']; ?>">
                    <svg viewBox="0 0 36 36">
                        <path class="circle-bg"
                            d="M18 2.0845
                               a 15.9155 15.9155 0 0 1 0 31.831
                               a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path class="circle-progress"
                            stroke-dasharray="<?php echo $stats['percentage']; ?>, 100"
                            d="M18 2.0845
                               a 15.9155 15.9155 0 0 1 0 31.831
                               a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                    </svg>
                    <div class="progress-text">
                        <span class="progress-value"><?php echo $stats['percentage']; ?>%</span>
                    </div>
                </div>
                <div class="progress-details">
                    <span class="completed"><?php echo $stats['total_completed']; ?>/<?php echo $stats['total_readings']; ?></span>
                    <span class="label">Readings Complete</span>
                </div>
            </div>
        </div>

        <!-- Week Selector -->
        <div class="week-selector">
            <button class="week-nav prev" onclick="changeWeek(-1)" <?php echo $currentWeek <= 1 ? 'disabled' : ''; ?>>
                &larr; Previous
            </button>

            <div class="week-dropdown">
                <select id="weekSelect" onchange="goToWeek(this.value)">
                    <?php for ($w = 1; $w <= 52; $w++): ?>
                        <option value="<?php echo $w; ?>" <?php echo $w == $currentWeek ? 'selected' : ''; ?>>
                            Week <?php echo $w; ?>
                            <?php if ($weeklyCompletion[$w] == 4): ?> &#10003;<?php endif; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <button class="week-nav next" onclick="changeWeek(1)" <?php echo $currentWeek >= 52 ? 'disabled' : ''; ?>>
                Next &rarr;
            </button>
        </div>

        <!-- Weekly Progress -->
        <div class="weekly-progress">
            <div class="weekly-bar">
                <div class="weekly-fill" style="width: <?php echo ($completedThisWeek / 4) * 100; ?>%"></div>
            </div>
            <span class="weekly-text"><?php echo $completedThisWeek; ?>/4 readings this week</span>
        </div>

        <!-- Reading Cards -->
        <div class="reading-cards">
            <?php foreach ($weekData['readings'] as $categoryId => $reading): ?>
                <?php
                $category = $reading['category'];
                $isCompleted = $weekProgress[$categoryId]['completed'];
                ?>
                <div class="reading-card <?php echo $isCompleted ? 'completed' : ''; ?>"
                     style="--category-color: <?php echo e($category['color']); ?>">

                    <div class="card-header">
                        <span class="category-badge" style="background-color: <?php echo e($category['color']); ?>">
                            <?php echo e($category['name']); ?>
                        </span>
                        <button class="check-btn <?php echo $isCompleted ? 'checked' : ''; ?>"
                                onclick="toggleProgress(<?php echo $currentWeek; ?>, '<?php echo e($categoryId); ?>', this)"
                                aria-label="<?php echo $isCompleted ? 'Mark incomplete' : 'Mark complete'; ?>">
                            <?php echo $isCompleted ? '&#10003;' : ''; ?>
                        </button>
                    </div>

                    <div class="card-body">
                        <h3 class="reading-reference"><?php echo e($reading['reference']); ?></h3>

                        <div class="passages-list">
                            <?php foreach ($reading['passages'] as $passage): ?>
                                <?php
                                $bookName = ReadingPlan::getBookName($passage['book']);
                                $firstChapter = $passage['chapters'][0];
                                $lastChapter = end($passage['chapters']);
                                ?>
                                <span class="passage-item">
                                    <?php echo e($bookName); ?>
                                    <?php echo $firstChapter; ?>
                                    <?php echo $firstChapter !== $lastChapter ? '-' . $lastChapter : ''; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button class="btn btn-read"
                                onclick="openReader('<?php echo e($reading['passages'][0]['book']); ?>', <?php echo $reading['passages'][0]['chapters'][0]; ?>, '<?php echo e(json_encode($reading['passages'])); ?>')"
                                style="background-color: <?php echo e($category['color']); ?>">
                            Read Now
                        </button>
                    </div>

                    <?php if ($isCompleted): ?>
                        <div class="completed-overlay">
                            <span class="completed-check">&#10003;</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">&#x1F525;</span>
                <span class="stat-value"><?php echo $stats['streak']; ?></span>
                <span class="stat-label">Week Streak</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#x1F4D6;</span>
                <span class="stat-value"><?php echo $stats['total_completed']; ?></span>
                <span class="stat-label">Readings Done</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#x1F3AF;</span>
                <span class="stat-value"><?php echo $stats['total_readings'] - $stats['total_completed']; ?></span>
                <span class="stat-label">Remaining</span>
            </div>
        </div>
    </div>
</div>

<!-- Bible Reader Modal -->
<div id="readerModal" class="modal">
    <div class="modal-content reader-modal">
        <div class="reader-header">
            <div class="reader-nav">
                <button class="reader-nav-btn prev" onclick="navigateChapter(-1)" title="Previous Chapter">
                    &larr;
                </button>
                <h2 id="readerTitle">Loading...</h2>
                <button class="reader-nav-btn next" onclick="navigateChapter(1)" title="Next Chapter">
                    &rarr;
                </button>
            </div>
            <div class="reader-controls">
                <select id="translationSelect" onchange="changeTranslation(this.value)">
                    <?php foreach (ReadingPlan::getTranslations() as $trans): ?>
                        <option value="<?php echo e($trans['id']); ?>"
                                <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>>
                            <?php echo e($trans['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="modal-close" onclick="closeReader()">&times;</button>
            </div>
        </div>
        <div class="reader-body" id="readerContent">
            <div class="loading-spinner"></div>
        </div>
        <div class="reader-footer">
            <div class="reader-progress">
                <span id="readerProgress"></span>
            </div>
        </div>
    </div>
</div>

<script>
    const currentWeek = <?php echo $currentWeek; ?>;
    const userTranslation = '<?php echo e($user['preferred_translation']); ?>';
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require TEMPLATE_PATH . '/layout.php';
