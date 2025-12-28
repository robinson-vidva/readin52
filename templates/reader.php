<?php
$user = Auth::getUser();
$book = $book ?? 'GEN';
$chapter = $chapter ?? 1;
$translation = $user['preferred_translation'] ?? 'eng_kjv';
$bookName = ReadingPlan::getBookName($book);

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
                <select id="translationSelect" onchange="changeTranslation(this.value)">
                    <?php foreach (ReadingPlan::getTranslations() as $trans): ?>
                        <option value="<?php echo e($trans['id']); ?>"
                                <?php echo $trans['id'] === $translation ? 'selected' : ''; ?>>
                            <?php echo e($trans['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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

    // Load chapter on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadChapter(currentBook, currentChapter);
    });

    async function loadChapter(book, chapter) {
        const content = document.getElementById('readerContent');
        content.innerHTML = '<div class="loading-spinner"></div>';

        try {
            const data = await BibleAPI.getChapter(currentTranslation, book, chapter);

            if (data.error) {
                content.innerHTML = '<p class="error">Failed to load chapter. Please try again.</p>';
                return;
            }

            let html = '<div class="scripture-text">';
            data.verses.forEach(verse => {
                html += `<span class="verse"><sup class="verse-num">${verse.verse}</sup>${verse.text}</span> `;
            });
            html += '</div>';

            content.innerHTML = html;

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
        loadChapter(currentBook, currentChapter + direction);
    }

    function changeTranslation(translation) {
        currentTranslation = translation;
        loadChapter(currentBook, currentChapter);
    }
</script>

<?php
$content = ob_get_clean();
$pageTitle = "$bookName $chapter";
require TEMPLATE_PATH . '/layout.php';
