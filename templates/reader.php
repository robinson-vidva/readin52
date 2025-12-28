<?php
$user = Auth::getUser();
$book = $book ?? 'GEN';
$chapter = $chapter ?? 1;
$translation = $user['preferred_translation'] ?? 'eng_kjv';
$secondaryTranslation = $user['secondary_translation'] ?? '';
$bookName = ReadingPlan::getBookName($book);
$translations = ReadingPlan::getTranslations();

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
                    <div class="translation-toggle" style="display: flex; gap: 0; align-items: center;">
                        <button type="button" class="trans-btn active" id="btnPrimary" onclick="showTranslation('primary')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: var(--primary, #5D4037); color: white; border-radius: 6px 0 0 6px; cursor: pointer; font-size: 0.875rem;">
                            <?php
                            $primaryTrans = array_filter($translations, fn($t) => $t['id'] === $translation);
                            echo e(reset($primaryTrans)['name'] ?? 'Primary');
                            ?>
                        </button>
                        <button type="button" class="trans-btn" id="btnSecondary" onclick="showTranslation('secondary')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: transparent; color: var(--primary, #5D4037); border-radius: 0; cursor: pointer; font-size: 0.875rem; margin-left: -2px;">
                            <?php
                            $secTrans = array_filter($translations, fn($t) => $t['id'] === $secondaryTranslation);
                            echo e(reset($secTrans)['name'] ?? 'Secondary');
                            ?>
                        </button>
                        <button type="button" class="trans-btn" id="btnBoth" onclick="showTranslation('both')" style="padding: 0.5rem 1rem; border: 2px solid var(--primary, #5D4037); background: transparent; color: var(--primary, #5D4037); border-radius: 0 6px 6px 0; cursor: pointer; font-size: 0.875rem; margin-left: -2px;">
                            Both
                        </button>
                    </div>
                <?php else: ?>
                    <select id="translationSelect" onchange="changeTranslation(this.value)">
                        <?php foreach ($translations as $trans): ?>
                            <option value="<?php echo e($trans['id']); ?>"
                                    <?php echo $trans['id'] === $translation ? 'selected' : ''; ?>>
                                <?php echo e($trans['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <a href="/?route=dashboard" class="btn btn-secondary">Back to Dashboard</a>
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
    let currentBook = '<?php echo e($book); ?>';
    let currentChapter = <?php echo intval($chapter); ?>;
    let currentTranslation = '<?php echo e($translation); ?>';
    const secondaryTranslation = '<?php echo e($secondaryTranslation); ?>';
    const hasDualTranslation = <?php echo !empty($secondaryTranslation) ? 'true' : 'false'; ?>;
    let currentViewMode = 'primary';
    let cachedPrimaryData = null;
    let cachedSecondaryData = null;

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
        loadStandaloneChapter(currentBook, currentChapter + direction);
    }

    function changeTranslation(translation) {
        currentTranslation = translation;
        loadStandaloneChapter(currentBook, currentChapter);
    }
</script>

<?php
$content = ob_get_clean();
$pageTitle = "$bookName $chapter";
require TEMPLATE_PATH . '/layout.php';
