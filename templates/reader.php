<?php
$user = Auth::getUser();
$book = $book ?? 'GEN';
$chapter = $chapter ?? 1;
$translation = $user['preferred_translation'] ?? 'eng_kjv';
$secondaryTranslation = $user['secondary_translation'] ?? '';
$bookName = ReadingPlan::getBookName($book);
$translationsByLanguage = ReadingPlan::getTranslationsGroupedByLanguage();

// Get notes for this chapter
$chapterNotes = Note::getForChapter($user['id'], $book, $chapter);

$colorMap = [
    'default' => ['bg' => '#fff', 'border' => '#e0e0e0'],
    'yellow' => ['bg' => '#fff9c4', 'border' => '#fbc02d'],
    'blue' => ['bg' => '#e3f2fd', 'border' => '#1976d2'],
    'green' => ['bg' => '#e8f5e9', 'border' => '#388e3c'],
    'pink' => ['bg' => '#fce4ec', 'border' => '#c2185b'],
    'purple' => ['bg' => '#f3e5f5', 'border' => '#7b1fa2'],
];

ob_start();
?>

<div class="standalone-reader">
    <div class="reader-container">
        <div class="reader-header">
            <div class="reader-nav">
                <button class="reader-nav-btn prev" onclick="navigateChapter(-1)" title="Previous Chapter">
                    &larr;
                </button>
                <h1 id="readerTitle"><?php echo e($bookName); ?> <?php echo e($chapter); ?></h1>
                <button class="reader-nav-btn next" onclick="navigateChapter(1)" title="Next Chapter">
                    &rarr;
                </button>
            </div>
            <div class="reader-controls">
                <?php if (!empty($secondaryTranslation)): ?>
                    <?php
                    $allTranslations = ReadingPlan::getTranslations();
                    $primaryTransName = 'Primary';
                    $secTransName = 'Secondary';
                    foreach ($allTranslations as $t) {
                        if ($t['id'] === $translation) $primaryTransName = $t['name'];
                        if ($t['id'] === $secondaryTranslation) $secTransName = $t['name'];
                    }
                    ?>
                    <div class="translation-toggle" style="display: flex; gap: 0; align-items: center;">
                        <button type="button" class="trans-btn active" id="btnPrimary" onclick="showTranslation('primary')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: var(--primary, #5D4037); color: white; border-radius: 6px 0 0 6px; cursor: pointer; font-size: 0.875rem;">
                            <?php echo e($primaryTransName); ?>
                        </button>
                        <button type="button" class="trans-btn" id="btnSecondary" onclick="showTranslation('secondary')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: transparent; color: var(--primary, #5D4037); border-radius: 0; cursor: pointer; font-size: 0.875rem; margin-left: -2px;">
                            <?php echo e($secTransName); ?>
                        </button>
                        <button type="button" class="trans-btn" id="btnBoth" onclick="showTranslation('both')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: transparent; color: var(--primary, #5D4037); border-radius: 0 6px 6px 0; cursor: pointer; font-size: 0.875rem; margin-left: -2px;">
                            Both
                        </button>
                    </div>
                <?php else: ?>
                    <div class="searchable-select" id="readerTransSelect" style="min-width: 200px;">
                        <button type="button" class="searchable-select-trigger" aria-haspopup="listbox" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                            <span class="selected-text">
                                <?php
                                foreach ($translationsByLanguage as $lang => $translations) {
                                    foreach ($translations as $t) {
                                        if ($t['id'] === $translation) {
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
                                <?php foreach ($translationsByLanguage as $language => $langTranslations): ?>
                                    <div class="searchable-select-group"><?php echo e($language); ?></div>
                                    <?php foreach ($langTranslations as $trans): ?>
                                        <div class="searchable-select-option <?php echo $trans['id'] === $translation ? 'selected' : ''; ?>"
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
                <button onclick="toggleNotesPanel()" class="btn btn-secondary" id="notesToggleBtn" style="display: flex; align-items: center; gap: 0.25rem;">
                    <span style="font-size: 1.1rem;">&#x1F4DD;</span>
                    <span id="notesCount"><?php echo count($chapterNotes); ?></span>
                </button>
                <a href="/?route=dashboard" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <div class="reader-main" style="display: flex; gap: 0; position: relative;">
            <div class="reader-body" id="readerContent" style="flex: 1; transition: margin-right 0.3s;">
                <div class="loading-spinner"></div>
            </div>

            <!-- Notes Panel -->
            <div id="notesPanel" style="width: 320px; border-left: 1px solid var(--border-color, #e0e0e0); background: var(--card-bg, #fff); display: none; flex-direction: column; max-height: calc(100vh - 200px); position: sticky; top: 0;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--border-color, #e0e0e0); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1rem;">Notes</h3>
                    <button onclick="openNewNoteForm()" style="background: var(--primary, #5D4037); color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">+ Add</button>
                </div>

                <!-- New Note Form (hidden by default) -->
                <div id="newNoteForm" style="display: none; padding: 1rem; border-bottom: 1px solid var(--border-color, #e0e0e0); background: var(--background, #f8f8f8);">
                    <input type="text" id="noteTitle" placeholder="Note title (optional)" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 6px; margin-bottom: 0.5rem; font-size: 0.9rem;">
                    <textarea id="noteContent" placeholder="Write your note..." rows="4" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 6px; margin-bottom: 0.5rem; font-size: 0.9rem; resize: vertical; font-family: inherit;"></textarea>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <?php foreach ($colorMap as $colorName => $colorValues): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="noteColor" value="<?php echo $colorName; ?>" <?php echo $colorName === 'default' ? 'checked' : ''; ?> style="display: none;">
                            <span class="color-dot" style="display: block; width: 24px; height: 24px; border-radius: 50%; background: <?php echo $colorValues['bg']; ?>; border: 2px solid <?php echo $colorValues['border']; ?>;"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="saveNote()" style="flex: 1; background: var(--primary, #5D4037); color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer;">Save</button>
                        <button onclick="cancelNote()" style="background: transparent; border: 1px solid var(--border-color, #e0e0e0); padding: 0.5rem 0.75rem; border-radius: 6px; cursor: pointer;">Cancel</button>
                    </div>
                </div>

                <!-- Notes List -->
                <div id="notesList" style="flex: 1; overflow-y: auto; padding: 0.75rem;">
                    <?php if (empty($chapterNotes)): ?>
                        <p id="noNotesMsg" style="text-align: center; color: var(--text-muted, #888); font-size: 0.9rem; padding: 2rem 1rem;">No notes for this chapter yet.<br>Click "+ Add" to create one.</p>
                    <?php else: ?>
                        <?php foreach ($chapterNotes as $note):
                            $colors = $colorMap[$note['color']] ?? $colorMap['default'];
                        ?>
                        <div class="note-item" data-id="<?php echo $note['id']; ?>" style="background: <?php echo $colors['bg']; ?>; border: 1px solid <?php echo $colors['border']; ?>; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.5rem; cursor: pointer;" onclick="editNote(<?php echo $note['id']; ?>)">
                            <div style="font-weight: 500; font-size: 0.9rem; margin-bottom: 0.25rem;"><?php echo e($note['title']); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary, #555); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?php echo e(substr($note['content'], 0, 150)); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted, #888); margin-top: 0.5rem;"><?php echo date('M j, g:i a', strtotime($note['updated_at'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="reader-footer">
            <div class="reader-progress">
                <span id="readerProgress"></span>
            </div>
        </div>
    </div>
</div>

<!-- Edit Note Modal -->
<div id="editNoteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 500px; width: 90%; padding: 1.25rem;">
        <h3 style="margin: 0 0 1rem;">Edit Note</h3>
        <input type="hidden" id="editNoteId">
        <input type="text" id="editNoteTitle" placeholder="Note title" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 6px; margin-bottom: 0.5rem;">
        <textarea id="editNoteContent" placeholder="Write your note..." rows="6" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 6px; margin-bottom: 0.5rem; font-family: inherit;"></textarea>
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
            <?php foreach ($colorMap as $colorName => $colorValues): ?>
            <label style="cursor: pointer;">
                <input type="radio" name="editNoteColor" value="<?php echo $colorName; ?>" style="display: none;">
                <span class="color-dot-edit" style="display: block; width: 24px; height: 24px; border-radius: 50%; background: <?php echo $colorValues['bg']; ?>; border: 2px solid <?php echo $colorValues['border']; ?>;"></span>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <button onclick="deleteNote()" style="background: #f44336; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">Delete</button>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="closeEditModal()" style="background: transparent; border: 1px solid var(--border-color, #e0e0e0); padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">Cancel</button>
                <button onclick="updateNote()" style="background: var(--primary, #5D4037); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">Save</button>
            </div>
        </div>
    </div>
</div>

<style>
    .color-dot { opacity: 0.7; transition: transform 0.2s; }
    input[type="radio"]:checked + .color-dot { opacity: 1; transform: scale(1.15); box-shadow: 0 0 0 2px var(--card-bg, #fff), 0 0 0 4px var(--primary, #5D4037); }
    .color-dot-edit { opacity: 0.7; transition: transform 0.2s; }
    input[type="radio"]:checked + .color-dot-edit { opacity: 1; transform: scale(1.15); box-shadow: 0 0 0 2px var(--card-bg, #fff), 0 0 0 4px var(--primary, #5D4037); }
    .note-item:hover { opacity: 0.9; }
    @media (max-width: 768px) {
        #notesPanel { position: fixed !important; right: 0; top: 60px; bottom: 0; width: 100% !important; max-width: 350px; z-index: 100; box-shadow: -2px 0 10px rgba(0,0,0,0.1); max-height: none !important; }
    }
</style>

<script>
    let currentBook = '<?php echo e($book); ?>';
    let currentChapter = <?php echo intval($chapter); ?>;
    let currentTranslation = '<?php echo e($translation); ?>';
    const secondaryTranslation = '<?php echo e($secondaryTranslation); ?>';
    const hasDualTranslation = <?php echo !empty($secondaryTranslation) ? 'true' : 'false'; ?>;
    let currentViewMode = 'primary';
    let cachedPrimaryData = null;
    let cachedSecondaryData = null;
    let notesPanelOpen = false;
    const csrfToken = '<?php echo getCsrfToken(); ?>';

    // Notes Panel Functions
    function toggleNotesPanel() {
        const panel = document.getElementById('notesPanel');
        notesPanelOpen = !notesPanelOpen;
        panel.style.display = notesPanelOpen ? 'flex' : 'none';
    }

    function openNewNoteForm() {
        document.getElementById('newNoteForm').style.display = 'block';
        document.getElementById('noteTitle').value = '';
        document.getElementById('noteContent').value = '';
        document.querySelector('input[name="noteColor"][value="default"]').checked = true;
        document.getElementById('noteContent').focus();
    }

    function cancelNote() {
        document.getElementById('newNoteForm').style.display = 'none';
    }

    async function saveNote() {
        const title = document.getElementById('noteTitle').value.trim() || 'Untitled Note';
        const content = document.getElementById('noteContent').value.trim();
        const color = document.querySelector('input[name="noteColor"]:checked').value;

        if (!content) {
            alert('Please write something in your note.');
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('title', title);
        formData.append('content', content);
        formData.append('color', color);
        formData.append('book', currentBook);
        formData.append('chapter', currentChapter);

        try {
            const response = await fetch('/?route=notes/save', {
                method: 'POST',
                body: formData
            });

            // Reload page to show new note
            window.location.reload();
        } catch (error) {
            alert('Failed to save note. Please try again.');
        }
    }

    function editNote(noteId) {
        fetch('/?route=api/notes/' + noteId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editNoteId').value = noteId;
                    document.getElementById('editNoteTitle').value = data.note.title;
                    document.getElementById('editNoteContent').value = data.note.content;
                    document.querySelector('input[name="editNoteColor"][value="' + data.note.color + '"]').checked = true;
                    document.getElementById('editNoteModal').style.display = 'flex';
                }
            });
    }

    function closeEditModal() {
        document.getElementById('editNoteModal').style.display = 'none';
    }

    async function updateNote() {
        const noteId = document.getElementById('editNoteId').value;
        const title = document.getElementById('editNoteTitle').value.trim() || 'Untitled Note';
        const content = document.getElementById('editNoteContent').value.trim();
        const color = document.querySelector('input[name="editNoteColor"]:checked').value;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('note_id', noteId);
        formData.append('title', title);
        formData.append('content', content);
        formData.append('color', color);

        try {
            await fetch('/?route=notes/save', {
                method: 'POST',
                body: formData
            });
            window.location.reload();
        } catch (error) {
            alert('Failed to update note.');
        }
    }

    async function deleteNote() {
        if (!confirm('Delete this note?')) return;

        const noteId = document.getElementById('editNoteId').value;
        try {
            await fetch('/?route=notes/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ note_id: noteId, csrf_token: csrfToken })
            });
            window.location.reload();
        } catch (error) {
            alert('Failed to delete note.');
        }
    }

    // Close modal on backdrop click
    document.getElementById('editNoteModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    // Load chapter on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadStandaloneChapter(currentBook, currentChapter);
    });

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
        if (cachedPrimaryData) {
            renderStandaloneContent(cachedPrimaryData, cachedSecondaryData, mode);
        }
    }

    function renderStandaloneContent(primaryData, secondaryData, mode) {
        const content = document.getElementById('readerContent');

        if (mode === 'both' && secondaryData) {
            let html = '<div class="dual-scripture" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">';

            html += '<div class="scripture-column" style="border-right: 1px solid var(--border-color, #e0e0e0); padding-right: 1rem;">';
            html += '<h4 style="margin: 0 0 1rem; color: var(--primary, #5D4037); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">' + (primaryData.translationName || 'Primary') + '</h4>';
            html += '<div class="scripture-text">';
            primaryData.verses.forEach(verse => {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div></div>';

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
            const data = mode === 'secondary' && secondaryData ? secondaryData : primaryData;
            let html = '<div class="scripture-text">';
            data.verses.forEach(verse => {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div>';
            content.innerHTML = html;
        }
    }

    async function loadStandaloneChapter(book, chapter) {
        const content = document.getElementById('readerContent');
        content.innerHTML = '<div class="loading-spinner"></div>';

        try {
            const data = await BibleAPI.getChapter(currentTranslation, book, chapter);

            if (data.error) {
                content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
                return;
            }

            cachedPrimaryData = data;
            cachedSecondaryData = null;

            // Load secondary translation if enabled
            if (hasDualTranslation && secondaryTranslation) {
                try {
                    const secData = await BibleAPI.getChapter(secondaryTranslation, book, chapter);
                    if (!secData.error) {
                        cachedSecondaryData = secData;
                    }
                } catch (e) {
                    console.log('Secondary translation load failed:', e);
                }
            }

            // Render based on view mode
            if (hasDualTranslation) {
                renderStandaloneContent(data, cachedSecondaryData, currentViewMode);
            } else {
                let html = '<div class="scripture-text">';
                data.verses.forEach(verse => {
                    html += `<span class="verse"><sup class="verse-num">${verse.verse}</sup>${verse.text}</span> `;
                });
                html += '</div>';
                content.innerHTML = html;
            }

            // Update title
            document.getElementById('readerTitle').textContent = `${data.bookName} ${chapter}`;
            document.getElementById('readerProgress').textContent = `Chapter ${chapter} of ${data.totalChapters}`;

            // Update URL without reload
            history.replaceState(null, '', `/?route=reader/${book}/${chapter}`);

            currentBook = book;
            currentChapter = chapter;

        } catch (error) {
            content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
        }
    }

    function navigateChapter(direction) {
        // Close notes panel when navigating (notes are chapter-specific)
        if (notesPanelOpen) {
            toggleNotesPanel();
        }
        // Navigate and reload to get new chapter's notes
        window.location.href = '/?route=reader/' + currentBook + '/' + (currentChapter + direction);
    }

    function changeTranslation(translation) {
        currentTranslation = translation;
        loadStandaloneChapter(currentBook, currentChapter);
    }

    // Searchable Select for Translation
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

    // Initialize searchable select
    initReaderTranslationSelect();
</script>

<?php
$content = ob_get_clean();
$pageTitle = "$bookName $chapter";
require TEMPLATE_PATH . '/layout.php';
