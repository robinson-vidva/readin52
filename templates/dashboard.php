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
                    <?php
                    $primaryTrans = array_filter(ReadingPlan::getTranslations(), fn($t) => $t['id'] === $user['preferred_translation']);
                    $secondaryTrans = array_filter(ReadingPlan::getTranslations(), fn($t) => $t['id'] === $user['secondary_translation']);
                    $primaryName = reset($primaryTrans)['name'] ?? 'Primary';
                    $secondaryName = reset($secondaryTrans)['name'] ?? 'Secondary';
                    ?>
                    <div class="translation-selector" style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                        <div class="segmented-control" style="display: inline-flex; background: #e8e8e8; border-radius: 6px; padding: 2px;">
                            <button type="button" id="btn1st" onclick="toggleTranslation(false)" style="padding: 4px 12px; font-size: 0.7rem; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; transition: all 0.2s; background: #fff; color: var(--primary, #5D4037); box-shadow: 0 1px 2px rgba(0,0,0,0.1);">1st</button>
                            <button type="button" id="btn2nd" onclick="toggleTranslation(true)" style="padding: 4px 12px; font-size: 0.7rem; font-weight: 500; border: none; border-radius: 4px; cursor: pointer; transition: all 0.2s; background: transparent; color: #666;">2nd</button>
                        </div>
                        <span id="currentTransName" style="font-size: 0.65rem; color: var(--text-secondary, #888); text-align: center; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo e($primaryName); ?></span>
                    </div>
                    <script>
                        const primaryTransName = '<?php echo e($primaryName); ?>';
                        const secondaryTransName = '<?php echo e($secondaryName); ?>';
                    </script>
                <?php else: ?>
                    <?php
                    $translationsByLang = ReadingPlan::getTranslationsGroupedByLanguage();
                    $readerLang = 'English';
                    foreach ($translationsByLang as $lang => $translations) {
                        foreach ($translations as $t) {
                            if ($t['id'] === $user['preferred_translation']) {
                                $readerLang = $lang;
                                break 2;
                            }
                        }
                    }
                    ?>
                    <div style="display: flex; gap: 0.25rem; align-items: center;">
                        <select id="readerLangSelect" style="padding: 0.4rem 0.5rem; font-size: 0.8rem; border: 1px solid #ddd; border-radius: 6px; min-width: 100px;">
                            <?php foreach ($translationsByLang as $language => $langTranslations): ?>
                                <option value="<?php echo e($language); ?>" <?php echo $language === $readerLang ? 'selected' : ''; ?>>
                                    <?php echo e($language); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="readerTransSelect" style="padding: 0.4rem 0.5rem; font-size: 0.8rem; border: 1px solid #ddd; border-radius: 6px; min-width: 140px;">
                            <?php foreach ($translationsByLang[$readerLang] ?? [] as $trans): ?>
                                <option value="<?php echo e($trans['id']); ?>" <?php echo $trans['id'] === $user['preferred_translation'] ? 'selected' : ''; ?>>
                                    <?php echo e($trans['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
    // Use var to make these global (accessible from app.js)
    var currentViewMode = 'primary'; // 'primary' or 'secondary'
    var cachedPrimaryData = null;
    var cachedSecondaryData = null;

    function toggleTranslation(isSecondary) {
        currentViewMode = isSecondary ? 'secondary' : 'primary';

        // Update segmented control button styles
        const btn1st = document.getElementById('btn1st');
        const btn2nd = document.getElementById('btn2nd');
        if (btn1st && btn2nd) {
            if (isSecondary) {
                btn1st.style.background = 'transparent';
                btn1st.style.color = '#666';
                btn1st.style.fontWeight = '500';
                btn1st.style.boxShadow = 'none';
                btn2nd.style.background = '#fff';
                btn2nd.style.color = 'var(--primary, #5D4037)';
                btn2nd.style.fontWeight = '600';
                btn2nd.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
            } else {
                btn1st.style.background = '#fff';
                btn1st.style.color = 'var(--primary, #5D4037)';
                btn1st.style.fontWeight = '600';
                btn1st.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
                btn2nd.style.background = 'transparent';
                btn2nd.style.color = '#666';
                btn2nd.style.fontWeight = '500';
                btn2nd.style.boxShadow = 'none';
            }
        }

        // Update full translation name display
        const nameEl = document.getElementById('currentTransName');
        if (nameEl && typeof primaryTransName !== 'undefined' && typeof secondaryTransName !== 'undefined') {
            nameEl.textContent = isSecondary ? secondaryTransName : primaryTransName;
        }

        // Re-render with cached data
        if (cachedPrimaryData) {
            renderContent(cachedPrimaryData, cachedSecondaryData, currentViewMode);
        }
    }

    // Legacy function for compatibility
    function showTranslation(mode) {
        currentViewMode = mode;
        if (cachedPrimaryData) {
            renderContent(cachedPrimaryData, cachedSecondaryData, mode);
        }
    }

    function renderContent(primaryData, secondaryData, mode) {
        const content = document.getElementById('readerContent');

        if (mode === 'secondary') {
            if (secondaryData && secondaryData.verses && secondaryData.verses.length > 0) {
                let html = '<div class="scripture-text">';
                secondaryData.verses.forEach(verse => {
                    html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
                });
                html += '</div>';
                content.innerHTML = html;
            } else {
                // Secondary not available - show warning and fall back to primary
                let html = '<div class="alert alert-warning" style="margin-bottom: 1rem; padding: 0.75rem; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; color: #856404;">Secondary translation not available for this chapter.</div>';
                html += '<div class="scripture-text">';
                primaryData.verses.forEach(verse => {
                    html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
                });
                html += '</div>';
                content.innerHTML = html;
            }
        } else {
            // Primary translation view
            let html = '<div class="scripture-text">';
            primaryData.verses.forEach(verse => {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div>';
            content.innerHTML = html;
        }
    }

    // Translation data for reader dropdown
    const readerTranslationsByLang = <?php echo json_encode($translationsByLang ?? ReadingPlan::getTranslationsGroupedByLanguage()); ?>;

    function changeTranslation(translation) {
        // Update user translation preference and reload chapter
        if (typeof currentCategory !== 'undefined' && typeof currentBook !== 'undefined' && typeof currentChapter !== 'undefined') {
            loadChapter(currentCategory, currentBook, currentChapter, translation);
        }
    }

    // Initialize reader translation dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const readerLangSelect = document.getElementById('readerLangSelect');
        const readerTransSelect = document.getElementById('readerTransSelect');

        if (readerLangSelect && readerTransSelect) {
            // When language changes, update translation options
            readerLangSelect.addEventListener('change', function() {
                const selectedLang = this.value;
                const translations = readerTranslationsByLang[selectedLang] || [];
                readerTransSelect.innerHTML = '';
                translations.forEach(trans => {
                    const option = document.createElement('option');
                    option.value = trans.id;
                    option.textContent = trans.name;
                    readerTransSelect.appendChild(option);
                });
                // Auto-select first translation and reload chapter
                if (translations.length > 0) {
                    changeTranslation(translations[0].id);
                }
            });

            // When translation changes, reload chapter
            readerTransSelect.addEventListener('change', function() {
                changeTranslation(this.value);
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require TEMPLATE_PATH . '/layout.php';
