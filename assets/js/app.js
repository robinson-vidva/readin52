/**
 * ReadIn52 - Main Application JavaScript
 */

// Global state
let currentTranslation = 'eng_kjv';
let currentCategory = '';
let currentBook = '';
let currentChapter = 0;
let currentChapterIndex = -1;
let currentVerses = [];
let isCurrentChapterComplete = false;

/**
 * Initialize app when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('ReadIn52 app initializing...');

    // Get user translation if available
    if (typeof userTranslation !== 'undefined') {
        currentTranslation = userTranslation;
        console.log('User translation:', currentTranslation);
    }

    // Setup navbar toggle
    setupNavbar();

    // Setup modals
    setupModals();

    console.log('ReadIn52 app initialized');
});

/**
 * Setup mobile navbar toggle
 */
function setupNavbar() {
    const toggle = document.getElementById('navbarToggle');
    const menu = document.getElementById('navbarMenu');

    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('show');
        });
    }
}

/**
 * Setup modal close on outside click
 */
function setupModals() {
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this && this.id !== 'confirmModal') {
                closeReader();
            }
        });
    });
}

/**
 * Change week
 */
function changeWeek(direction) {
    const select = document.getElementById('weekSelect');
    if (select) {
        const newWeek = parseInt(select.value) + direction;
        if (newWeek >= 1 && newWeek <= 52) {
            goToWeek(newWeek);
        }
    }
}

/**
 * Go to specific week
 */
function goToWeek(week) {
    window.location.href = '/?route=dashboard&week=' + week;
}

/**
 * Open chapter reader
 */
function openChapter(category, book, chapter) {
    console.log('openChapter called:', category, book, chapter);

    const modal = document.getElementById('readerModal');
    if (!modal) {
        console.error('readerModal not found!');
        return;
    }

    currentCategory = category;
    currentBook = book;
    currentChapter = chapter;

    // Find index in weekChapters
    if (typeof weekChapters !== 'undefined') {
        currentChapterIndex = weekChapters.findIndex(function(ch) {
            return ch.category === category && ch.book === book && ch.chapter === chapter;
        });
        isCurrentChapterComplete = currentChapterIndex >= 0 && weekChapters[currentChapterIndex].completed;
    }

    // Get translation from select if available
    const select = document.getElementById('translationSelect');
    if (select) {
        currentTranslation = select.value;
    }

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    loadChapter(book, chapter);
    updateFooterButtons();
}

/**
 * Close Bible reader modal
 */
function closeReader() {
    const modal = document.getElementById('readerModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    hideConfirmModal();
}

/**
 * Load chapter content
 */
async function loadChapter(book, chapter) {
    console.log('loadChapter called:', book, chapter);

    const content = document.getElementById('readerContent');
    const title = document.getElementById('readerTitle');
    const progressEl = document.getElementById('readerProgress');
    const verseCountEl = document.getElementById('verseCount');
    const readingTimeEl = document.getElementById('readingTime');

    if (!content) {
        console.error('readerContent not found!');
        return;
    }

    content.innerHTML = '<div class="loading-spinner"></div>';

    try {
        console.log('Calling BibleAPI.getChapter:', currentTranslation, book, chapter);

        if (typeof BibleAPI === 'undefined') {
            console.error('BibleAPI is not defined!');
            content.innerHTML = '<p class="error">BibleAPI not loaded. Please refresh the page.</p>';
            return;
        }

        // Load primary translation
        const data = await BibleAPI.getChapter(currentTranslation, book, chapter);
        console.log('BibleAPI response:', data);

        if (data.error) {
            content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
            return;
        }

        currentVerses = data.verses;

        // Store primary data for dual mode
        if (typeof cachedPrimaryData !== 'undefined') {
            cachedPrimaryData = data;
            cachedSecondaryData = null;
            console.log('Stored primary data, cachedPrimaryData set');
        }

        // Load secondary translation if enabled
        let secondaryData = null;
        console.log('Dual translation check:', {
            hasDualDefined: typeof hasDualTranslation !== 'undefined',
            hasDualValue: typeof hasDualTranslation !== 'undefined' ? hasDualTranslation : 'N/A',
            secondaryTrans: typeof secondaryTranslation !== 'undefined' ? secondaryTranslation : 'N/A'
        });

        if (typeof hasDualTranslation !== 'undefined' && hasDualTranslation && typeof secondaryTranslation !== 'undefined' && secondaryTranslation) {
            try {
                console.log('Loading secondary translation:', secondaryTranslation);
                secondaryData = await BibleAPI.getChapter(secondaryTranslation, book, chapter);
                console.log('Secondary API response:', secondaryData);
                if (!secondaryData.error) {
                    cachedSecondaryData = secondaryData;
                    console.log('Secondary translation loaded successfully');
                }
            } catch (e) {
                console.log('Secondary translation load failed:', e);
            }
        } else {
            console.log('Secondary translation not enabled or not set');
        }

        // Count words for reading time
        let totalWords = 0;
        data.verses.forEach(function(verse) {
            totalWords += verse.text.split(/\s+/).length;
        });

        // Render based on current view mode
        if (typeof currentViewMode !== 'undefined' && typeof hasDualTranslation !== 'undefined' && hasDualTranslation) {
            console.log('Rendering with dual mode, currentViewMode:', currentViewMode);
            renderContent(data, secondaryData, currentViewMode);
        } else {
            // Build HTML for single translation
            let html = '<div class="scripture-text">';
            data.verses.forEach(function(verse) {
                html += '<span class="verse"><sup class="verse-num">' + verse.verse + '</sup>' + verse.text + '</span> ';
            });
            html += '</div>';
            content.innerHTML = html;
        }

        // Update title
        if (title) {
            title.textContent = data.bookName + ' ' + chapter;
        }

        // Update verse count
        if (verseCountEl) {
            verseCountEl.textContent = data.verses.length + ' verses';
        }

        // Calculate reading time (~200 words per minute)
        if (readingTimeEl) {
            const minutes = Math.ceil(totalWords / 200);
            readingTimeEl.textContent = '~' + minutes + ' min read';
        }

        // Update progress (position in week's reading)
        if (progressEl && typeof weekChapters !== 'undefined') {
            const pos = currentChapterIndex + 1;
            const total = weekChapters.length;
            progressEl.textContent = 'Chapter ' + pos + ' of ' + total + ' this week';
        }

        currentBook = book;
        currentChapter = chapter;

    } catch (error) {
        console.error('Error loading chapter:', error);
        content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
    }
}

/**
 * Update footer buttons based on current state
 */
function updateFooterButtons() {
    const btnComplete = document.getElementById('btnComplete');

    if (btnComplete) {
        if (isCurrentChapterComplete) {
            btnComplete.textContent = 'Next Chapter';
        } else {
            btnComplete.textContent = 'Mark Complete & Next';
        }
    }
}

/**
 * Mark current chapter as complete and go to next
 */
async function markCompleteAndNext() {
    // If already complete, just go to next
    if (isCurrentChapterComplete) {
        goToNextChapter();
        return;
    }

    // Mark as complete
    await markChapterComplete();

    // Go to next
    goToNextChapter();
}

/**
 * Skip chapter without marking complete
 */
function skipChapter() {
    if (!isCurrentChapterComplete) {
        // Show confirmation
        showConfirmModal();
    } else {
        goToNextChapter();
    }
}

/**
 * Show confirmation modal
 */
function showConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.classList.add('show');
    }
}

/**
 * Hide confirmation modal
 */
function hideConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Confirm skip (from modal)
 */
function confirmSkip() {
    hideConfirmModal();
    goToNextChapter();
}

/**
 * Confirm complete (from modal)
 */
async function confirmComplete() {
    hideConfirmModal();
    await markChapterComplete();
    goToNextChapter();
}

/**
 * Mark current chapter as complete
 */
async function markChapterComplete() {
    try {
        const response = await fetch('/?route=api/chapter-progress', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                week: currentWeek,
                category: currentCategory,
                book: currentBook,
                chapter: currentChapter
            })
        });

        const result = await response.json();

        if (result.success) {
            isCurrentChapterComplete = true;

            // Update weekChapters array
            if (currentChapterIndex >= 0 && typeof weekChapters !== 'undefined') {
                weekChapters[currentChapterIndex].completed = true;
            }

            // Update chapter item in the list
            updateChapterItem(currentCategory, currentBook, currentChapter, true);

            // Update weekly progress bar
            if (result.weekCounts) {
                updateWeeklyProgress(result.weekCounts);
            }

            return true;
        }
    } catch (error) {
        console.error('Error marking chapter complete:', error);
    }
    return false;
}

/**
 * Update chapter item appearance in the list
 */
function updateChapterItem(category, book, chapter, completed) {
    const item = document.querySelector(
        '.chapter-item[data-category="' + category + '"][data-book="' + book + '"][data-chapter="' + chapter + '"]'
    );
    if (item) {
        if (completed) {
            item.classList.add('completed');
            if (!item.querySelector('.chapter-done')) {
                const checkmark = document.createElement('span');
                checkmark.className = 'chapter-done';
                checkmark.innerHTML = '&#10003;';
                item.appendChild(checkmark);
            }
        } else {
            item.classList.remove('completed');
            const checkmark = item.querySelector('.chapter-done');
            if (checkmark) checkmark.remove();
        }
    }

    // Update category progress
    updateCategoryProgress(category);
}

/**
 * Update category progress display
 */
function updateCategoryProgress(category) {
    const section = document.querySelector('.category-section[data-category="' + category + '"]');
    if (!section) return;

    const items = section.querySelectorAll('.chapter-item');
    const completed = section.querySelectorAll('.chapter-item.completed').length;
    const total = items.length;

    const progressEl = section.querySelector('.category-progress');
    if (progressEl) {
        progressEl.textContent = completed + '/' + total;
    }

    // Update section completed state
    if (completed === total) {
        section.classList.add('completed');
    } else {
        section.classList.remove('completed');
    }
}

/**
 * Update weekly progress bar
 */
function updateWeeklyProgress(counts) {
    const fill = document.getElementById('weeklyFill');
    const text = document.getElementById('weeklyText');

    if (fill && text) {
        const percentage = counts.total > 0 ? (counts.completed / counts.total) * 100 : 0;
        fill.style.width = percentage + '%';
        text.textContent = counts.completed + '/' + counts.total + ' chapters this week';
    }
}

/**
 * Go to next chapter in the week's reading
 */
function goToNextChapter() {
    if (typeof weekChapters === 'undefined' || currentChapterIndex < 0) {
        closeReader();
        return;
    }

    const nextIndex = currentChapterIndex + 1;

    // Check if we've reached the end
    if (nextIndex >= weekChapters.length) {
        // Week complete, go back to list
        closeReader();
        return;
    }

    // Load next chapter
    const next = weekChapters[nextIndex];
    currentChapterIndex = nextIndex;
    currentCategory = next.category;
    currentBook = next.book;
    currentChapter = next.chapter;
    isCurrentChapterComplete = next.completed;

    loadChapter(next.book, next.chapter);
    updateFooterButtons();
}

/**
 * Change translation
 */
function changeTranslation(translation) {
    currentTranslation = translation;
    if (currentBook && currentChapter) {
        loadChapter(currentBook, currentChapter);
    }
}
