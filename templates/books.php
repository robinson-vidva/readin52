<?php
$user = Auth::getUser();
$categoryProgress = Bible::getBookProgressByCategory($user['id']);
$overallProgress = Bible::getOverallProgress($user['id']);

// Group categories by testament
$oldTestament = [];
$newTestament = [];

foreach ($categoryProgress as $catId => $category) {
    if ($category['testament'] === 'old') {
        $oldTestament[$catId] = $category;
    } else {
        $newTestament[$catId] = $category;
    }
}

ob_start();
?>

<div class="books-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Bible Book Progress</h1>
                <p>Track your reading progress by book</p>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="bp-stats-grid">
            <div class="bp-stat-card bp-stat-primary">
                <div class="bp-stat-value"><?php echo $overallProgress['percentage']; ?>%</div>
                <div class="bp-stat-label">Overall Progress</div>
                <div class="bp-stat-sub"><?php echo $overallProgress['completedChapters']; ?>/<?php echo $overallProgress['totalChapters']; ?> chapters</div>
            </div>
            <div class="bp-stat-card">
                <div class="bp-stat-value" style="color: #8B7355;"><?php echo $overallProgress['oldTestament']['percentage']; ?>%</div>
                <div class="bp-stat-label">Old Testament</div>
                <div class="bp-stat-sub"><?php echo $overallProgress['oldTestament']['completed']; ?>/<?php echo $overallProgress['oldTestament']['total']; ?> chapters</div>
            </div>
            <div class="bp-stat-card">
                <div class="bp-stat-value" style="color: #5B7DB1;"><?php echo $overallProgress['newTestament']['percentage']; ?>%</div>
                <div class="bp-stat-label">New Testament</div>
                <div class="bp-stat-sub"><?php echo $overallProgress['newTestament']['completed']; ?>/<?php echo $overallProgress['newTestament']['total']; ?> chapters</div>
            </div>
        </div>

        <!-- Books Completed Summary -->
        <div class="bp-books-summary">
            <div class="bp-books-summary-text">
                <span class="bp-books-icon">&#x1F4DA;</span>
                <span><?php echo $overallProgress['booksComplete']; ?> of <?php echo $overallProgress['totalBooks']; ?> books completed</span>
            </div>
            <div class="bp-books-summary-bar">
                <div class="bp-books-summary-fill" style="width: <?php echo round(($overallProgress['booksComplete'] / $overallProgress['totalBooks']) * 100); ?>%;"></div>
            </div>
        </div>

        <!-- Old Testament Section -->
        <div class="bp-testament-section">
            <h2 class="bp-testament-title">
                <span class="bp-testament-icon" style="color: #8B7355;">&#x1F4DC;</span>
                <span>Old Testament</span>
                <span class="bp-testament-percent"><?php echo $overallProgress['oldTestament']['percentage']; ?>%</span>
            </h2>

            <?php foreach ($oldTestament as $catId => $category): ?>
            <div class="bp-category-card">
                <div class="bp-category-header bp-category-header-ot">
                    <div class="bp-category-info">
                        <h3 class="bp-category-name"><?php echo e($category['name']); ?></h3>
                        <p class="bp-category-desc"><?php echo e($category['description']); ?></p>
                    </div>
                    <div class="bp-category-stats">
                        <span class="bp-category-percent" style="color: #8B7355;"><?php echo $category['percentage']; ?>%</span>
                        <span class="bp-category-books"><?php echo $category['booksComplete']; ?>/<?php echo $category['totalBooks']; ?> books</span>
                    </div>
                </div>
                <div class="bp-category-progress">
                    <div class="bp-category-progress-fill" style="width: <?php echo $category['percentage']; ?>%; background: #8B7355;"></div>
                </div>
                <div class="bp-books-grid">
                    <?php foreach ($category['books'] as $bookCode => $book): ?>
                    <div class="bp-book-item <?php echo $book['isComplete'] ? 'bp-book-complete' : ''; ?>">
                        <?php if ($book['isComplete']): ?>
                            <div class="bp-book-percent bp-book-check">&#x2714;</div>
                        <?php else: ?>
                            <div class="bp-book-percent" style="color: #8B7355;"><?php echo $book['percentage']; ?>%</div>
                        <?php endif; ?>
                        <div class="bp-book-name" title="<?php echo e($book['name']); ?>"><?php echo e($book['name']); ?></div>
                        <div class="bp-book-chapters">
                            <?php if ($book['total'] > 0): ?>
                                <?php echo $book['completed']; ?>/<?php echo $book['total']; ?> ch
                            <?php else: ?>
                                Not in plan
                            <?php endif; ?>
                        </div>
                        <?php if (!$book['isComplete'] && $book['total'] > 0): ?>
                        <div class="bp-book-bar">
                            <div class="bp-book-bar-fill" style="width: <?php echo $book['percentage']; ?>%; background: #8B7355;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- New Testament Section -->
        <div class="bp-testament-section">
            <h2 class="bp-testament-title">
                <span class="bp-testament-icon" style="color: #5B7DB1;">&#x2728;</span>
                <span>New Testament</span>
                <span class="bp-testament-percent"><?php echo $overallProgress['newTestament']['percentage']; ?>%</span>
            </h2>

            <?php foreach ($newTestament as $catId => $category): ?>
            <div class="bp-category-card">
                <div class="bp-category-header bp-category-header-nt">
                    <div class="bp-category-info">
                        <h3 class="bp-category-name"><?php echo e($category['name']); ?></h3>
                        <p class="bp-category-desc"><?php echo e($category['description']); ?></p>
                    </div>
                    <div class="bp-category-stats">
                        <span class="bp-category-percent" style="color: #5B7DB1;"><?php echo $category['percentage']; ?>%</span>
                        <span class="bp-category-books"><?php echo $category['booksComplete']; ?>/<?php echo $category['totalBooks']; ?> books</span>
                    </div>
                </div>
                <div class="bp-category-progress">
                    <div class="bp-category-progress-fill" style="width: <?php echo $category['percentage']; ?>%; background: #5B7DB1;"></div>
                </div>
                <div class="bp-books-grid">
                    <?php foreach ($category['books'] as $bookCode => $book): ?>
                    <div class="bp-book-item <?php echo $book['isComplete'] ? 'bp-book-complete' : ''; ?>">
                        <?php if ($book['isComplete']): ?>
                            <div class="bp-book-percent bp-book-check">&#x2714;</div>
                        <?php else: ?>
                            <div class="bp-book-percent" style="color: #5B7DB1;"><?php echo $book['percentage']; ?>%</div>
                        <?php endif; ?>
                        <div class="bp-book-name" title="<?php echo e($book['name']); ?>"><?php echo e($book['name']); ?></div>
                        <div class="bp-book-chapters">
                            <?php if ($book['total'] > 0): ?>
                                <?php echo $book['completed']; ?>/<?php echo $book['total']; ?> ch
                            <?php else: ?>
                                Not in plan
                            <?php endif; ?>
                        </div>
                        <?php if (!$book['isComplete'] && $book['total'] > 0): ?>
                        <div class="bp-book-bar">
                            <div class="bp-book-bar-fill" style="width: <?php echo $book['percentage']; ?>%; background: #5B7DB1;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$extraStyles = '
<style>
/* Books Page Styles */
.bp-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.bp-stat-card {
    background: var(--card-bg, #fff);
    padding: 1.25rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.bp-stat-primary {
    background: linear-gradient(135deg, #5D4037 0%, #4E342E 100%);
    color: #fff;
}
.bp-stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.2;
}
.bp-stat-label {
    font-size: 0.85rem;
    opacity: 0.85;
    margin-top: 0.25rem;
}
.bp-stat-sub {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0.25rem;
}
.bp-stat-card:not(.bp-stat-primary) .bp-stat-label {
    color: var(--text-secondary, #666);
}
.bp-stat-card:not(.bp-stat-primary) .bp-stat-sub {
    color: var(--text-muted, #888);
}

/* Books Summary */
.bp-books-summary {
    background: var(--card-bg, #fff);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.bp-books-summary-text {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
}
.bp-books-icon {
    font-size: 1.5rem;
}
.bp-books-summary-bar {
    flex: 1;
    min-width: 200px;
    height: 10px;
    background: var(--background, #f5f5f5);
    border-radius: 5px;
    overflow: hidden;
}
.bp-books-summary-fill {
    height: 100%;
    background: linear-gradient(90deg, #5D4037, #8B7355);
    border-radius: 5px;
    transition: width 0.3s ease;
}

/* Testament Section */
.bp-testament-section {
    margin-bottom: 2.5rem;
}
.bp-testament-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.bp-testament-icon {
    font-size: 1.25rem;
}
.bp-testament-percent {
    margin-left: auto;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-muted, #888);
}

/* Category Card */
.bp-category-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}
.bp-category-header {
    padding: 1rem 1.25rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: start;
}
.bp-category-header-ot {
    background: linear-gradient(135deg, rgba(139, 115, 85, 0.08) 0%, rgba(139, 115, 85, 0.03) 100%);
}
.bp-category-header-nt {
    background: linear-gradient(135deg, rgba(91, 125, 177, 0.08) 0%, rgba(91, 125, 177, 0.03) 100%);
}
.bp-category-info {
    min-width: 0;
}
.bp-category-name {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary, #333);
}
.bp-category-desc {
    margin: 0;
    font-size: 0.8rem;
    color: var(--text-muted, #888);
}
.bp-category-stats {
    text-align: right;
    flex-shrink: 0;
}
.bp-category-percent {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}
.bp-category-books {
    display: block;
    font-size: 0.75rem;
    color: var(--text-muted, #888);
    margin-top: 0.125rem;
}

/* Category Progress Bar */
.bp-category-progress {
    height: 4px;
    background: var(--background, #f5f5f5);
}
.bp-category-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

/* Books Grid */
.bp-books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 0.75rem;
    padding: 1rem 1.25rem;
}
.bp-book-item {
    background: var(--background, #f8f8f8);
    padding: 0.875rem 0.75rem;
    border-radius: 10px;
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.bp-book-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.bp-book-complete {
    border: 2px solid #4CAF50;
    background: rgba(76, 175, 80, 0.05);
}
.bp-book-percent {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.375rem;
}
.bp-book-check {
    color: #4CAF50;
}
.bp-book-name {
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-primary, #333);
}
.bp-book-chapters {
    font-size: 0.7rem;
    color: var(--text-muted, #888);
}
.bp-book-bar {
    height: 4px;
    background: var(--border-color, #e0e0e0);
    border-radius: 2px;
    margin-top: 0.5rem;
    overflow: hidden;
}
.bp-book-bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .bp-stats-grid {
        grid-template-columns: 1fr;
    }
    .bp-books-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .bp-books-summary {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    .bp-books-summary-bar {
        min-width: 100%;
    }
    .bp-category-header {
        grid-template-columns: 1fr auto;
        gap: 0.75rem;
    }
    .bp-category-percent {
        font-size: 1.25rem;
    }
}
</style>
';

$pageTitle = 'Books';
require TEMPLATE_PATH . '/layout.php';
