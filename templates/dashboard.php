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
                    <div class="category-header">
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
    <div class="modal-content reader-modal <?php echo !empty($user['secondary_translation']) ? 'dual-translation' : ''; ?>">
        <div class="reader-header">
            <h2 id="readerTitle">Loading...</h2>
            <button class="reader-close" onclick="closeReader()" aria-label="Close">&times;</button>
            <div class="reader-controls">
                <?php if (!empty($user['secondary_translation'])): ?>
                    <div class="translation-toggle" style="display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" class="trans-btn active" id="btnPrimary" onclick="showTranslation('primary')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: var(--primary, #5D4037); color: white; border-radius: 6px 0 0 6px; cursor: pointer; font-size: 0.875rem;">
                            <?php
                            $primaryTrans = array_filter(ReadingPlan::getTranslations(), fn($t) => $t['id'] === $user['preferred_translation']);
                            echo e(reset($primaryTrans)['name'] ?? 'Primary');
                            ?>
                        </button>
                        <button type="button" class="trans-btn" id="btnSecondary" onclick="showTranslation('secondary')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: transparent; color: var(--primary, #5D4037); border-radius: 0; cursor: pointer; font-size: 0.875rem; margin-left: -2px;">
                            <?php
                            $secondaryTrans = array_filter(ReadingPlan::getTranslations(), fn($t) => $t['id'] === $user['secondary_translation']);
                            echo e(reset($secondaryTrans)['name'] ?? 'Secondary');
                            ?>
                        </button>
                        <button type="button" class="trans-btn" id="btnBoth" onclick="showTranslation('both')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: transparent; color: var(--primary, #5D4037); border-radius: 0 6px 6px 0; cursor: pointer; font-size: 0.875rem; margin-left: -2px;">
                            Both
                        </button>
                    </div>
                <?php else: ?>
                    <?php $translationsByLang = ReadingPlan::getTranslationsGroupedByLanguage(); ?>
                    <div class="searchable-select" id="readerTransSelect" style="min-width: 200px;">
                        <button type="button" class="searchable-select-trigger" aria-haspopup="listbox" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                            <span class="selected-text">
                                <?php
                                foreach ($translationsByLang as $lang => $translations) {
                                    foreach ($translations as $t) {
                                        if ($t['id'] === $user['preferred_translation']) {
                                            echo e($lang . ' (' . $t['name'] . ')');
                                            break 2;
                                        }
                                    }
                                }
                                ?>
                            </span>
                            <span class="arrow">&#9662;</span>
                        </button>
                        <div class="searchable-select-dropdown">
                            <div class="searchable-select-search">
                                <input type="text" placeholder="Search translations..." autocomplete="off">
                            </div>
                            <div class="searchable-select-options">
                                <?php foreach ($translationsByLang as $language => $langTranslations): ?>
                                    <div class="searchable-select-group"><?php echo e($language); ?></div>
                                    <?php foreach ($langTranslations as $trans): ?>
                                        <div class="searchable-select-option <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>"
                                             data-value="<?php echo e($trans['id']); ?>"
                                             data-label="<?php echo e($language . ' (' . $trans['name'] . ')'); ?>"
                                             data-search="<?php echo e(strtolower($language . ' ' . $trans['name'])); ?>">
                                            <?php echo e($language); ?> (<?php echo e($trans['name']); ?>)
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
    const secondaryTranslation = '<?php echo e($user['secondary_translation'] ?? ''); ?>';
    const hasDualTranslation = <?php echo !empty($user['secondary_translation']) ? 'true' : 'false'; ?>;
    const weekChapters = <?php echo json_encode($weekChapters); ?>;
    let currentViewMode = 'primary'; // 'primary', 'secondary', 'both'
    let cachedPrimaryData = null;
    let cachedSecondaryData = null;

    function showTranslation(mode) {
        currentViewMode = mode;

        // Update button styles
        document.querySelectorAll('.trans-btn').forEach(btn => {
            btn.style.background = 'transparent';
            btn.style.color = 'var(--primary, #5D4037)';
            btn.classList.remove('active');
        });

        const activeBtn = document.getElementById('btn' + mode.charAt(0).toUpperCase() + mode.slice(1));
        if (activeBtn) {
            activeBtn.style.background = 'var(--primary, #5D4037)';
            activeBtn.style.color = 'white';
            activeBtn.classList.add('active');
        }

        // Re-render with cached data
        if (cachedPrimaryData && (mode === 'primary' || mode === 'both')) {
            renderContent(cachedPrimaryData, cachedSecondaryData, mode);
        }
    }

    function renderContent(primaryData, secondaryData, mode) {
        const content = document.getElementById('readerContent');

        if (mode === 'both' && secondaryData) {
            // Side-by-side view
            let html = '<div class="dual-scripture" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">';

            // Primary column
            html += '<div class="scripture-column" style="border-right: 1px solid var(--border-color, #e0e0e0); padding-right: 1rem;">';
            html += '<h4 style="margin: 0 0 1rem; color: var(--primary, #5D4037); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">' + (primaryData.translationName || 'Primary') + '</h4>';
            html += '<div class="scripture-text">';
            primaryData.verses.forEach(verse => {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div></div>';

            // Secondary column
            html += '<div class="scripture-column" style="padding-left: 1rem;">';
            html += '<h4 style="margin: 0 0 1rem; color: var(--primary, #5D4037); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">' + (secondaryData.translationName || 'Secondary') + '</h4>';
            html += '<div class="scripture-text">';
            secondaryData.verses.forEach(verse => {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div></div>';

            html += '</div>';
            content.innerHTML = html;
        } else {
            // Single translation view
            const data = mode === 'secondary' && secondaryData ? secondaryData : primaryData;
            let html = '<div class="scripture-text">';
            data.verses.forEach(verse => {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div>';
            content.innerHTML = html;
        }
    }

    // Searchable Select for Translation (when not in dual mode)
    function initReaderTranslationSelect() {
        const container = document.getElementById('readerTransSelect');
        if (!container) return;

        const trigger = container.querySelector('.searchable-select-trigger');
        const searchInput = container.querySelector('.searchable-select-search input');
        const options = container.querySelectorAll('.searchable-select-option');
        const selectedText = trigger.querySelector('.selected-text');

        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = container.classList.contains('open');
            document.querySelectorAll('.searchable-select.open').forEach(el => {
                if (el !== container) el.classList.remove('open');
            });
            container.classList.toggle('open');
            if (!isOpen) {
                searchInput.value = '';
                filterReaderOptions('');
                searchInput.focus();
            }
        });

        searchInput.addEventListener('input', function() {
            filterReaderOptions(this.value.toLowerCase());
        });

        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        function filterReaderOptions(query) {
            options.forEach(option => {
                const searchText = option.dataset.search || option.textContent.toLowerCase();
                option.classList.toggle('hidden', query !== '' && !searchText.includes(query));
            });
            const groups = container.querySelectorAll('.searchable-select-group');
            groups.forEach(group => {
                let nextSibling = group.nextElementSibling;
                let hasVisibleOption = false;
                while (nextSibling && !nextSibling.classList.contains('searchable-select-group')) {
                    if (nextSibling.classList.contains('searchable-select-option') &&
                        !nextSibling.classList.contains('hidden')) {
                        hasVisibleOption = true;
                        break;
                    }
                    nextSibling = nextSibling.nextElementSibling;
                }
                group.style.display = hasVisibleOption ? '' : 'none';
            });
        }

        options.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const value = this.dataset.value;
                const label = this.dataset.label;
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedText.textContent = label;
                container.classList.remove('open');
                // Change translation and reload chapter
                changeTranslation(value);
            });
        });

        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                container.classList.remove('open');
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                container.classList.remove('open');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const visibleOptions = Array.from(options).filter(o => !o.classList.contains('hidden'));
                if (visibleOptions.length > 0) visibleOptions[0].click();
            }
        });
    }

    function changeTranslation(translation) {
        // Update user translation preference and reload chapter
        if (typeof currentCategory !== 'undefined' && typeof currentBook !== 'undefined' && typeof currentChapter !== 'undefined') {
            loadChapter(currentCategory, currentBook, currentChapter, translation);
        }
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initReaderTranslationSelect();
    });
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require TEMPLATE_PATH . '/layout.php';
