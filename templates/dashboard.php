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
                <div class="progress-circle" data-progress="<?php echo $chapterStats['percentage']; ?>" style="width: 80px; height: 80px; position: relative;">
                    <svg viewBox="0 0 36 36" width="80" height="80" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                        <path class="circle-bg"
                            style="fill: none; stroke: #E0E0E0; stroke-width: 3;"
                            d="M18 2.0845
                               a 15.9155 15.9155 0 0 1 0 31.831
                               a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path class="circle-progress"
                            style="fill: none; stroke: #43A047; stroke-width: 3; stroke-linecap: round;"
                            stroke-dasharray="<?php echo $chapterStats['percentage']; ?>, 100"
                            d="M18 2.0845
                               a 15.9155 15.9155 0 0 1 0 31.831
                               a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                    </svg>
                    <div class="progress-text" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                        <span class="progress-value" style="font-size: 1.25rem; font-weight: 700; color: #5D4037;"><?php echo $chapterStats['percentage']; ?>%</span>
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
<div id="readerModal" class="modal" style="display:none;">
    <div class="modal-content reader-modal <?php echo !empty($user['secondary_translation']) ? 'dual-translation' : ''; ?>">
        <div class="reader-header" style="display: flex; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color, #eee); gap: 0.75rem;">
            <h2 id="readerTitle" style="flex: 1; font-size: 1.1rem; margin: 0; font-weight: 600;">Loading...</h2>
            <div class="reader-controls" style="display: flex; align-items: center; gap: 0.5rem;">
                <?php if (!empty($user['secondary_translation'])): ?>
                    <?php
                    $primaryTrans = array_filter(ReadingPlan::getTranslations(), fn($t) => $t['id'] === $user['preferred_translation']);
                    $secondaryTrans = array_filter(ReadingPlan::getTranslations(), fn($t) => $t['id'] === $user['secondary_translation']);
                    $primaryName = reset($primaryTrans)['name'] ?? 'Primary';
                    $secondaryName = reset($secondaryTrans)['name'] ?? 'Secondary';
                    ?>
                    <div class="translation-selector" style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                        <div class="segmented-control" style="display: inline-flex; background: var(--background, #f0f0f0); border-radius: 8px; padding: 3px; border: 1px solid var(--border-color, #ddd);">
                            <button type="button" id="btn1st" onclick="toggleTranslation(false)" style="padding: 6px 14px; font-size: 0.75rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s; background: #fff; color: var(--primary, #5D4037); box-shadow: 0 1px 3px rgba(0,0,0,0.12);">1st</button>
                            <button type="button" id="btn2nd" onclick="toggleTranslation(true)" style="padding: 6px 14px; font-size: 0.75rem; font-weight: 500; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s; background: transparent; color: #666;">2nd</button>
                        </div>
                        <span id="currentTransName" style="font-size: 0.7rem; color: var(--text-secondary, #888); text-align: center; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo e($primaryName); ?></span>
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
            <button class="reader-close" onclick="closeReader()" aria-label="Close" style="width: 36px; height: 36px; border: none; background: var(--background, #f5f5f5); font-size: 1.25rem; cursor: pointer; color: var(--text-secondary, #666); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s;">&times;</button>
        </div>
        <div class="reader-meta" id="readerMeta">
            <span class="verse-count" id="verseCount"></span>
            <span class="reading-time" id="readingTime"></span>
        </div>
        <div style="display: flex; flex-direction: column; flex: 1; overflow: hidden; min-height: 0;">
            <!-- Scripture Content - 80% when notes open -->
            <div class="reader-body" id="readerContent" style="flex: 1; overflow-y: auto; transition: flex 0.3s ease;">
                <div class="loading-spinner"></div>
            </div>
            <!-- Notes Panel - Horizontal at bottom (20%) -->
            <div id="notesPanel" style="height: 0; min-height: 0; border-top: 2px solid var(--primary, #5D4037); background: var(--card-bg, #fff); display: none; flex-direction: column; overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 0.75rem 1rem; background: var(--background, #f5f5f5); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <span style="font-weight: 600; font-size: 1rem; color: var(--primary, #5D4037);">&#x1F4DD; Notes for this chapter</span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <button onclick="openNoteModal()" style="background: var(--primary, #5D4037); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500;">+ Add Note</button>
                        <button onclick="toggleNotesPanel()" style="background: none; border: 1px solid var(--border-color, #ddd); width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 1.1rem; color: var(--text-secondary, #666); display: flex; align-items: center; justify-content: center;" title="Close Notes">&times;</button>
                    </div>
                </div>
                <!-- Notes List - Grid layout -->
                <div id="notesList" style="flex: 1; overflow-y: auto; padding: 1rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem; align-content: start;">
                    <p id="noNotesMsg" style="grid-column: 1 / -1; text-align: center; color: var(--text-muted, #888); font-size: 0.9rem; padding: 1rem;">No notes for this chapter yet. Click "+ Add Note" to create one.</p>
                </div>
            </div>
        </div>
        <div class="reader-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-top: 1px solid var(--border-color, #eee); gap: 1rem;">
            <div class="reader-actions" style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary" id="btnSkip" onclick="skipChapter()">Skip</button>
                <button class="btn btn-primary" id="btnComplete" onclick="markCompleteAndNext()">Next Chapter</button>
            </div>
            <div class="reader-progress" style="flex: 1; text-align: center;">
                <span id="readerProgress"></span>
            </div>
            <button onclick="toggleNotesPanel()" id="notesToggleBtn" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; border: 1px solid var(--border-color, #ddd); border-radius: 6px; background: var(--background, #f5f5f5); cursor: pointer; display: flex; align-items: center; gap: 0.4rem; transition: all 0.2s;" title="Toggle Notes">
                <span>&#x1F4DD;</span>
                <span>Notes</span>
                <span id="notesCount" style="background: var(--primary, #5D4037); color: white; font-size: 0.7rem; padding: 0.15rem 0.4rem; border-radius: 10px; min-width: 18px; text-align: center;">0</span>
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal confirm-modal" style="display:none;">
    <div class="modal-content confirm-content">
        <h3>Mark as Complete?</h3>
        <p>Would you like to mark this chapter as complete before moving on?</p>
        <div class="confirm-actions">
            <button class="btn btn-secondary" onclick="confirmSkip()">Skip</button>
            <button class="btn btn-primary" onclick="confirmComplete()">Mark Complete</button>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div id="noteModal" class="modal" style="display:none; z-index: 1100;">
    <div class="modal-content" style="max-width: 500px; padding: 0;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color, #e0e0e0); display: flex; justify-content: space-between; align-items: center; background: var(--background, #f5f5f5);">
            <h3 id="noteModalTitle" style="margin: 0; font-size: 1.1rem; color: var(--primary, #5D4037);">Add Note</h3>
            <button onclick="closeNoteModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary, #666); line-height: 1;">&times;</button>
        </div>
        <div style="padding: 1.25rem;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.4rem; font-size: 0.9rem;">Title</label>
                <input type="text" id="noteTitle" placeholder="Enter a title for your note..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.4rem; font-size: 0.9rem;">Note</label>
                <textarea id="noteContent" placeholder="Write your thoughts, reflections, or insights..." rows="6" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem; resize: vertical; font-family: inherit; box-sizing: border-box; min-height: 150px;"></textarea>
            </div>
            <input type="hidden" id="editingNoteId" value="">
            <div style="display: flex; gap: 0.75rem; justify-content: space-between; align-items: center;">
                <button id="deleteNoteBtn" onclick="deleteCurrentNote()" style="display: none; padding: 0.6rem; background: none; border: 1px solid #dc3545; color: #dc3545; border-radius: 8px; cursor: pointer; font-size: 1.1rem; transition: all 0.2s;" title="Delete Note">
                    &#x1F5D1;
                </button>
                <div style="display: flex; gap: 0.75rem; margin-left: auto;">
                    <button onclick="closeNoteModal()" style="padding: 0.75rem 1.5rem; border: 1px solid var(--border-color, #ddd); background: white; border-radius: 8px; cursor: pointer; font-size: 0.95rem;">Cancel</button>
                    <button onclick="saveNoteFromModal()" style="padding: 0.75rem 1.5rem; background: var(--primary, #5D4037); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 500;">Save Note</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const currentWeek = <?php echo $currentWeek; ?>;
    const userTranslation = '<?php echo e($user['preferred_translation']); ?>';
    const secondaryTranslation = '<?php echo e($user['secondary_translation'] ?? ''); ?>';
    const hasDualTranslation = <?php echo !empty($user['secondary_translation']) ? 'true' : 'false'; ?>;
    const weekChapters = <?php echo json_encode($weekChapters); ?>;
    const csrfToken = '<?php echo getCsrfToken(); ?>';

    // Book name mapping for note titles
    const bookNames = <?php echo json_encode(ReadingPlan::getBookNames()); ?>;

    // Use var to make these global (accessible from app.js)
    var currentViewMode = 'primary'; // 'primary' or 'secondary'
    var cachedPrimaryData = null;
    var cachedSecondaryData = null;
    var notesPanelOpen = false;
    var currentNotesBook = '';
    var currentNotesChapter = 0;
    var chapterNotes = [];

    // Notes Functions
    function toggleNotesPanel() {
        const panel = document.getElementById('notesPanel');
        const readerContent = document.getElementById('readerContent');
        notesPanelOpen = !notesPanelOpen;

        if (notesPanelOpen) {
            panel.style.display = 'flex';
            panel.style.height = '25%';
            panel.style.minHeight = '180px';
            readerContent.style.flex = '0 0 75%';
        } else {
            panel.style.display = 'none';
            panel.style.height = '0';
            panel.style.minHeight = '0';
            readerContent.style.flex = '1';
        }
    }

    // Note Modal Functions
    function openNoteModal(noteId = null) {
        const modal = document.getElementById('noteModal');
        const titleInput = document.getElementById('noteTitle');
        const contentInput = document.getElementById('noteContent');
        const modalTitle = document.getElementById('noteModalTitle');
        const editingIdInput = document.getElementById('editingNoteId');
        const deleteBtn = document.getElementById('deleteNoteBtn');

        if (noteId) {
            // Edit mode
            const note = chapterNotes.find(n => n.id == noteId);
            if (note) {
                titleInput.value = note.title || '';
                contentInput.value = note.content || '';
                editingIdInput.value = noteId;
                modalTitle.textContent = 'Edit Note';
                deleteBtn.style.display = 'block';
            }
        } else {
            // New note mode - set default title with book, chapter, and week
            const bookName = bookNames[currentNotesBook] || currentNotesBook;
            const defaultTitle = `${bookName} ${currentNotesChapter} - Week ${currentWeek}`;
            titleInput.value = defaultTitle;
            contentInput.value = '';
            editingIdInput.value = '';
            modalTitle.textContent = 'Add Note';
            deleteBtn.style.display = 'none';
        }

        modal.style.display = 'flex';
        modal.classList.add('show');
        setTimeout(() => contentInput.focus(), 100);
    }

    function closeNoteModal() {
        const modal = document.getElementById('noteModal');
        modal.style.display = 'none';
        modal.classList.remove('show');
    }

    async function saveNoteFromModal() {
        const title = document.getElementById('noteTitle').value.trim() || 'Untitled Note';
        const content = document.getElementById('noteContent').value.trim();
        const editingId = document.getElementById('editingNoteId').value;

        if (!content) {
            alert('Please write something in your note');
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('ajax', '1');
        formData.append('title', title);
        formData.append('content', content);
        formData.append('book', currentNotesBook);
        formData.append('chapter', currentNotesChapter);
        formData.append('color', 'default');

        if (editingId) {
            formData.append('note_id', editingId);
        }

        try {
            const response = await fetch('/?route=notes/save', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                closeNoteModal();
                await loadNotesForChapter(currentNotesBook, currentNotesChapter);
            } else {
                alert('Failed to save note: ' + (result.error || 'Unknown error'));
            }
        } catch (e) {
            console.error('Save note error:', e);
            alert('Failed to save note. Please try again.');
        }
    }

    async function deleteCurrentNote() {
        const noteId = document.getElementById('editingNoteId').value;
        if (!noteId) return;

        if (!confirm('Are you sure you want to delete this note?')) return;

        try {
            const response = await fetch('/?route=notes/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ note_id: noteId, csrf_token: csrfToken })
            });
            const result = await response.json();
            if (result.success) {
                closeNoteModal();
                await loadNotesForChapter(currentNotesBook, currentNotesChapter);
            } else {
                alert('Failed to delete note');
            }
        } catch (e) {
            console.error('Delete note error:', e);
            alert('Failed to delete note');
        }
    }

    async function loadNotesForChapter(book, chapter) {
        currentNotesBook = book;
        currentNotesChapter = chapter;
        try {
            const response = await fetch(`/?route=api/notes/chapter&book=${book}&chapter=${chapter}`);
            const data = await response.json();
            if (data.success) {
                chapterNotes = data.notes || [];
                renderNotesList();
            }
        } catch (e) {
            console.error('Load notes error:', e);
            chapterNotes = [];
            renderNotesList();
        }
    }

    function renderNotesList() {
        const list = document.getElementById('notesList');
        const count = document.getElementById('notesCount');
        count.textContent = chapterNotes.length;

        if (chapterNotes.length === 0) {
            list.innerHTML = '<p id="noNotesMsg" style="grid-column: 1 / -1; text-align: center; color: var(--text-muted, #888); font-size: 0.9rem; padding: 1rem;">No notes for this chapter yet. Click "+ Add Note" to create one.</p>';
            return;
        }

        let html = '';
        chapterNotes.forEach(note => {
            html += `<div class="note-card" onclick="openNoteModal(${note.id})" style="background: var(--surface, #fff); border-radius: 10px; padding: 1rem; cursor: pointer; border: 1px solid var(--border-color, #e0e0e0); transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--primary, #5D4037);">${escapeHtml(note.title || 'Untitled')}</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary, #666); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.5;">${escapeHtml(note.content.substring(0, 150))}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted, #999); margin-top: 0.5rem;">${note.created_at ? new Date(note.created_at).toLocaleDateString() : ''}</div>
            </div>`;
        });
        list.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Legacy function for compatibility
    function editNote(noteId) {
        openNoteModal(noteId);
    }

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
