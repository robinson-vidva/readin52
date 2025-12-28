<?php
$user = Auth::getUser();
$stats = Progress::getStats($user['id']);
$chapterStats = Progress::getChapterStats($user['id']);
$currentWeek = get('week', $stats['current_week']);
$currentWeek = max(1, min(52, intval($currentWeek)));
$weekData = ReadingPlan::getWeekWithDetails($currentWeek);
$chapterProgress = Progress::getWeekChapterProgress($user['id'], $currentWeek);
$weekChapterCounts = Progress::getWeekChapterCounts($user['id'], $currentWeek);

// Build flat list of all chapters for the week (for navigation)
$weekChapters = [];
foreach ($weekData['readings'] as $categoryId => $reading) {
    foreach ($reading['passages'] as $passage) {
        foreach ($passage['chapters'] as $ch) {
            $key = $passage['book'] . '_' . $ch;
            $isComplete = isset($chapterProgress[$categoryId][$key]) && $chapterProgress[$categoryId][$key]['completed'];
            $weekChapters[] = [
                'category' => $categoryId,
                'book' => $passage['book'],
                'chapter' => $ch,
                'completed' => $isComplete
            ];
        }
    }
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
                <div class="progress-circle" data-progress="<?php echo $chapterStats['percentage']; ?>">
                    <svg viewBox="0 0 36 36">
                        <path class="circle-bg"
                            d="M18 2.0845
                               a 15.9155 15.9155 0 0 1 0 31.831
                               a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path class="circle-progress"
                            stroke-dasharray="<?php echo $chapterStats['percentage']; ?>, 100"
                            d="M18 2.0845
                               a 15.9155 15.9155 0 0 1 0 31.831
                               a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                    </svg>
                    <div class="progress-text">
                        <span class="progress-value"><?php echo $chapterStats['percentage']; ?>%</span>
                    </div>
                </div>
                <div class="progress-details">
                    <span class="completed"><?php echo $chapterStats['completed_chapters']; ?>/<?php echo $chapterStats['total_chapters']; ?></span>
                    <span class="label">Chapters Complete</span>
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
                <div class="weekly-fill" id="weeklyFill" style="width: <?php echo $weekChapterCounts['total'] > 0 ? ($weekChapterCounts['completed'] / $weekChapterCounts['total']) * 100 : 0; ?>%"></div>
            </div>
            <span class="weekly-text" id="weeklyText"><?php echo $weekChapterCounts['completed']; ?>/<?php echo $weekChapterCounts['total']; ?> chapters this week</span>
        </div>

        <!-- Reading List -->
        <div class="reading-list">
            <?php foreach ($weekData['readings'] as $categoryId => $reading): ?>
                <?php
                $category = $reading['category'];
                $categoryChapterProgress = $chapterProgress[$categoryId] ?? [];

                // Count completed chapters in this category
                $catTotalChapters = 0;
                $catCompletedChapters = 0;
                foreach ($reading['passages'] as $passage) {
                    foreach ($passage['chapters'] as $ch) {
                        $catTotalChapters++;
                        $key = $passage['book'] . '_' . $ch;
                        if (isset($categoryChapterProgress[$key]) && $categoryChapterProgress[$key]['completed']) {
                            $catCompletedChapters++;
                        }
                    }
                }
                $isCategoryComplete = $catCompletedChapters === $catTotalChapters;
                ?>
                <div class="category-section <?php echo $isCategoryComplete ? 'completed' : ''; ?>" data-category="<?php echo e($categoryId); ?>">
                    <div class="category-header" style="border-left-color: <?php echo e($category['color']); ?>">
                        <span class="category-name"><?php echo e($category['name']); ?></span>
                        <span class="category-progress"><?php echo $catCompletedChapters; ?>/<?php echo $catTotalChapters; ?></span>
                    </div>

                    <?php foreach ($reading['passages'] as $passageIndex => $passage): ?>
                        <?php $bookName = ReadingPlan::getBookName($passage['book']); ?>
                        <div class="book-section">
                            <div class="book-name"><?php echo e($bookName); ?></div>
                            <div class="chapter-list">
                                <?php foreach ($passage['chapters'] as $chapterIndex => $ch): ?>
                                    <?php
                                    $key = $passage['book'] . '_' . $ch;
                                    $isChapterComplete = isset($categoryChapterProgress[$key]) && $categoryChapterProgress[$key]['completed'];
                                    ?>
                                    <button class="chapter-item <?php echo $isChapterComplete ? 'completed' : ''; ?>"
                                            onclick="openChapter('<?php echo e($categoryId); ?>', '<?php echo e($passage['book']); ?>', <?php echo $ch; ?>)"
                                            data-category="<?php echo e($categoryId); ?>"
                                            data-book="<?php echo e($passage['book']); ?>"
                                            data-chapter="<?php echo $ch; ?>">
                                        <span class="chapter-num">Ch <?php echo $ch; ?></span>
                                        <?php if ($isChapterComplete): ?>
                                            <span class="chapter-done">&#10003;</span>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                <span class="stat-value"><?php echo $chapterStats['completed_chapters']; ?></span>
                <span class="stat-label">Chapters Done</span>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#x1F3AF;</span>
                <span class="stat-value"><?php echo $chapterStats['total_chapters'] - $chapterStats['completed_chapters']; ?></span>
                <span class="stat-label">Remaining</span>
            </div>
        </div>
    </div>
</div>

<!-- Bible Reader Modal -->
<div id="readerModal" class="modal">
    <div class="modal-content reader-modal">
        <div class="reader-header">
            <h2 id="readerTitle">Loading...</h2>
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
        <div class="reader-meta" id="readerMeta">
            <span class="verse-count" id="verseCount"></span>
            <span class="reading-time" id="readingTime"></span>
        </div>
        <div class="reader-body" id="readerContent">
            <div class="loading-spinner"></div>
        </div>
        <div class="reader-footer">
            <div class="reader-actions">
                <button class="btn btn-secondary" id="btnSkip" onclick="skipChapter()">Skip</button>
                <button class="btn btn-primary" id="btnComplete" onclick="markCompleteAndNext()">Mark Complete & Next</button>
            </div>
            <div class="reader-progress">
                <span id="readerProgress"></span>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal confirm-modal">
    <div class="modal-content confirm-content">
        <h3>Mark as Complete?</h3>
        <p>Would you like to mark this chapter as complete before moving on?</p>
        <div class="confirm-actions">
            <button class="btn btn-secondary" onclick="confirmSkip()">Skip</button>
            <button class="btn btn-primary" onclick="confirmComplete()">Mark Complete</button>
        </div>
    </div>
</div>

<script>
    const currentWeek = <?php echo $currentWeek; ?>;
    const userTranslation = '<?php echo e($user['preferred_translation']); ?>';
    const weekChapters = <?php echo json_encode($weekChapters); ?>;
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require TEMPLATE_PATH . '/layout.php';
