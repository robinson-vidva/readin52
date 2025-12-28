/**
 * ReadIn52 - Main Application JavaScript
 */

(function() {
    'use strict';

    // State
    let currentTranslation = 'eng_kjv';
    let currentBook = 'GEN';
    let currentChapter = 1;
    let currentPassages = [];
    let currentVerses = [];      // Store verses for verse-by-verse mode
    let currentVerseIndex = 0;   // Current verse being shown
    let viewMode = 'chapter';    // 'chapter' or 'verse'

    /**
     * Initialize app
     */
    function init() {
        // Get user translation if available
        if (typeof userTranslation !== 'undefined') {
            currentTranslation = userTranslation;
        }

        // Setup navbar toggle
        setupNavbar();

        // Setup modals
        setupModals();
    }

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
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    }

    /**
     * Change week
     */
    window.changeWeek = function(direction) {
        const select = document.getElementById('weekSelect');
        if (select) {
            const newWeek = parseInt(select.value) + direction;
            if (newWeek >= 1 && newWeek <= 52) {
                goToWeek(newWeek);
            }
        }
    };

    /**
     * Go to specific week
     */
    window.goToWeek = function(week) {
        window.location.href = '/?route=dashboard&week=' + week;
    };

    /**
     * Toggle reading progress (legacy - category level)
     */
    window.toggleProgress = async function(week, category, button) {
        try {
            const response = await fetch('/?route=api/progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ week, category })
            });

            const result = await response.json();

            if (result.success) {
                // Update button
                button.classList.toggle('checked', result.completed);
                button.innerHTML = result.completed ? '&#10003;' : '';

                // Update card
                const card = button.closest('.reading-card');
                if (card) {
                    card.classList.toggle('completed', result.completed);

                    // Update overlay
                    let overlay = card.querySelector('.completed-overlay');
                    if (result.completed && !overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'completed-overlay';
                        overlay.innerHTML = '<span class="completed-check">&#10003;</span>';
                        card.appendChild(overlay);
                    } else if (!result.completed && overlay) {
                        overlay.remove();
                    }
                }

                // Reload page to update progress bars
                location.reload();
            }
        } catch (error) {
            console.error('Error updating progress:', error);
            alert('Failed to update progress. Please try again.');
        }
    };

    /**
     * Toggle chapter progress (granular - chapter level)
     */
    window.toggleChapter = async function(week, category, book, chapter, checkbox) {
        try {
            const response = await fetch('/?route=api/chapter-progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ week, category, book, chapter })
            });

            const result = await response.json();

            if (result.success) {
                // Update checkbox label styling
                const label = checkbox.closest('.chapter-check');
                if (label) {
                    label.classList.toggle('checked', result.completed);
                }

                // Update card if category is complete
                const card = checkbox.closest('.reading-card');
                if (card) {
                    card.classList.toggle('completed', result.categoryComplete);

                    // Update category progress display
                    const progressSpan = card.querySelector('.category-progress');
                    if (progressSpan && result.weekCounts) {
                        // Recalculate category progress
                        const checkboxes = card.querySelectorAll('.chapter-check input[type="checkbox"]');
                        const total = checkboxes.length;
                        const completed = card.querySelectorAll('.chapter-check input[type="checkbox"]:checked').length;
                        progressSpan.textContent = `${completed}/${total}`;
                    }

                    // Update overlay
                    let overlay = card.querySelector('.completed-overlay');
                    if (result.categoryComplete && !overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'completed-overlay';
                        overlay.innerHTML = '<span class="completed-check">&#10003;</span>';
                        card.appendChild(overlay);
                    } else if (!result.categoryComplete && overlay) {
                        overlay.remove();
                    }
                }

                // Update weekly progress bar
                if (result.weekCounts) {
                    const fill = document.getElementById('weeklyFill');
                    const text = document.getElementById('weeklyText');
                    if (fill && text) {
                        const percentage = result.weekCounts.total > 0
                            ? (result.weekCounts.completed / result.weekCounts.total) * 100
                            : 0;
                        fill.style.width = percentage + '%';
                        text.textContent = `${result.weekCounts.completed}/${result.weekCounts.total} chapters this week`;
                    }
                }
            } else {
                // Revert checkbox if failed
                checkbox.checked = !checkbox.checked;
                alert('Failed to update progress. Please try again.');
            }
        } catch (error) {
            console.error('Error updating chapter progress:', error);
            checkbox.checked = !checkbox.checked;
            alert('Failed to update progress. Please try again.');
        }
    };

    /**
     * Open Bible reader modal
     */
    window.openReader = function(book, chapter, passagesJson) {
        const modal = document.getElementById('readerModal');
        if (!modal) return;

        // Parse passages
        try {
            currentPassages = JSON.parse(passagesJson);
        } catch (e) {
            currentPassages = [{ book: book, chapters: [chapter] }];
        }

        currentBook = book;
        currentChapter = chapter;

        // Get translation from select if available
        const select = document.getElementById('translationSelect');
        if (select) {
            currentTranslation = select.value;
        }

        modal.classList.add('show');
        loadChapter(book, chapter);
    };

    /**
     * Close Bible reader modal
     */
    window.closeReader = function() {
        const modal = document.getElementById('readerModal');
        if (modal) {
            modal.classList.remove('show');
        }
    };

    /**
     * Load chapter content
     */
    async function loadChapter(book, chapter) {
        const content = document.getElementById('readerContent');
        const title = document.getElementById('readerTitle');
        const progress = document.getElementById('readerProgress');

        if (!content) return;

        content.innerHTML = '<div class="loading-spinner"></div>';

        try {
            const data = await BibleAPI.getChapter(currentTranslation, book, chapter);

            if (data.error) {
                content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
                return;
            }

            // Store verses for verse-by-verse mode
            currentVerses = data.verses;
            currentVerseIndex = 0;

            // Update title
            if (title) {
                title.textContent = `${data.bookName} ${chapter}`;
            }

            // Update progress
            if (progress) {
                progress.textContent = `Chapter ${chapter} of ${data.totalChapters}`;
            }

            currentBook = book;
            currentChapter = chapter;

            // Render based on view mode
            renderContent();

        } catch (error) {
            console.error('Error loading chapter:', error);
            content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
        }
    }

    /**
     * Render content based on view mode
     */
    function renderContent() {
        const content = document.getElementById('readerContent');
        const verseNav = document.getElementById('verseNav');

        if (!content) return;

        if (viewMode === 'chapter') {
            // Show all verses at once
            let html = '<div class="scripture-text">';
            currentVerses.forEach(verse => {
                html += `<span class="verse"><sup class="verse-num">${verse.verse}</sup>${verse.text}</span> `;
            });
            html += '</div>';
            content.innerHTML = html;

            // Hide verse navigation
            if (verseNav) verseNav.style.display = 'none';
        } else {
            // Show single verse
            if (currentVerses.length > 0) {
                const verse = currentVerses[currentVerseIndex];
                content.innerHTML = `
                    <div class="verse-single">
                        <div class="verse-number">Verse ${verse.verse}</div>
                        <div class="verse-text">${verse.text}</div>
                    </div>
                `;

                // Update verse indicator
                const indicator = document.getElementById('verseIndicator');
                if (indicator) {
                    indicator.textContent = `Verse ${currentVerseIndex + 1} of ${currentVerses.length}`;
                }
            }

            // Show verse navigation
            if (verseNav) verseNav.style.display = 'flex';
        }
    }

    /**
     * Set view mode (chapter or verse)
     */
    window.setViewMode = function(mode) {
        viewMode = mode;

        // Update button states
        const chapterBtn = document.getElementById('chapterViewBtn');
        const verseBtn = document.getElementById('verseViewBtn');

        if (chapterBtn) chapterBtn.classList.toggle('active', mode === 'chapter');
        if (verseBtn) verseBtn.classList.toggle('active', mode === 'verse');

        // Re-render content
        if (currentVerses.length > 0) {
            renderContent();
        }
    };

    /**
     * Navigate to previous/next verse
     */
    window.navigateVerse = function(direction) {
        const newIndex = currentVerseIndex + direction;

        if (newIndex >= 0 && newIndex < currentVerses.length) {
            currentVerseIndex = newIndex;
            renderContent();
        } else if (newIndex < 0 && currentVerseIndex === 0) {
            // Go to previous chapter's last verse
            navigateChapter(-1);
            // After loading, go to last verse
            setTimeout(() => {
                currentVerseIndex = currentVerses.length - 1;
                renderContent();
            }, 500);
        } else if (newIndex >= currentVerses.length) {
            // Go to next chapter's first verse
            navigateChapter(1);
        }
    };

    /**
     * Navigate to previous/next chapter
     */
    window.navigateChapter = function(direction) {
        let newChapter = currentChapter + direction;
        let newBook = currentBook;

        const totalChapters = BibleAPI.getTotalChapters(currentBook);

        // Check if we need to change books
        if (newChapter < 1) {
            // Find previous book in passages
            const currentPassageIndex = currentPassages.findIndex(p => p.book === currentBook);
            if (currentPassageIndex > 0) {
                newBook = currentPassages[currentPassageIndex - 1].book;
                newChapter = BibleAPI.getTotalChapters(newBook);
            } else {
                return; // Can't go back further
            }
        } else if (newChapter > totalChapters) {
            // Find next book in passages
            const currentPassageIndex = currentPassages.findIndex(p => p.book === currentBook);
            if (currentPassageIndex < currentPassages.length - 1) {
                newBook = currentPassages[currentPassageIndex + 1].book;
                newChapter = 1;
            } else {
                return; // Can't go forward further
            }
        }

        loadChapter(newBook, newChapter);
    };

    /**
     * Change translation
     */
    window.changeTranslation = function(translation) {
        currentTranslation = translation;
        loadChapter(currentBook, currentChapter);
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
