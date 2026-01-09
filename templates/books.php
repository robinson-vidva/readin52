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
    <div class="container" style="max-width: 1100px;">
        <!-- Page Header -->
        <div class="page-header" style="margin-bottom: 1.5rem;">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 1.75rem;">Bible Book Progress</h1>
            <p style="margin: 0; color: var(--text-secondary, #666);">Track your reading progress by book</p>
        </div>

        <!-- Overall Stats -->
        <div class="overall-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: linear-gradient(135deg, #5D4037 0%, #4E342E 100%); padding: 1.25rem; border-radius: 10px; text-align: center; color: #fff;">
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $overallProgress['percentage']; ?>%</div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Overall Progress</div>
                <div style="font-size: 0.75rem; opacity: 0.7; margin-top: 0.25rem;"><?php echo $overallProgress['completedChapters']; ?>/<?php echo $overallProgress['totalChapters']; ?> chapters</div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 1.25rem; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 2rem; font-weight: 700; color: #8B7355;"><?php echo $overallProgress['oldTestament']['percentage']; ?>%</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary, #666);">Old Testament</div>
                <div style="font-size: 0.75rem; color: var(--text-muted, #888); margin-top: 0.25rem;"><?php echo $overallProgress['oldTestament']['completed']; ?>/<?php echo $overallProgress['oldTestament']['total']; ?> chapters</div>
            </div>
            <div style="background: var(--card-bg, #fff); padding: 1.25rem; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 2rem; font-weight: 700; color: #5D8B55;"><?php echo $overallProgress['newTestament']['percentage']; ?>%</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary, #666);">New Testament</div>
                <div style="font-size: 0.75rem; color: var(--text-muted, #888); margin-top: 0.25rem;"><?php echo $overallProgress['newTestament']['completed']; ?>/<?php echo $overallProgress['newTestament']['total']; ?> chapters</div>
            </div>
        </div>

        <!-- Books Completed Summary -->
        <div style="background: var(--card-bg, #fff); padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">&#x1F4DA;</span>
                <span style="font-weight: 600;"><?php echo $overallProgress['booksComplete']; ?> of <?php echo $overallProgress['totalBooks']; ?> books completed</span>
            </div>
            <div style="background: var(--background, #f8f8f8); height: 10px; border-radius: 5px; flex: 1; min-width: 200px; max-width: 400px;">
                <div style="width: <?php echo round(($overallProgress['booksComplete'] / $overallProgress['totalBooks']) * 100); ?>%; height: 100%; background: linear-gradient(90deg, #5D4037, #8B7355); border-radius: 5px;"></div>
            </div>
        </div>

        <!-- Old Testament Section -->
        <div class="testament-section" style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #8B7355;">&#x1F4DC;</span>
                Old Testament
                <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-muted, #888); margin-left: auto;"><?php echo $overallProgress['oldTestament']['percentage']; ?>%</span>
            </h2>

            <?php foreach ($oldTestament as $catId => $category): ?>
            <div class="category-section" style="background: var(--card-bg, #fff); border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
                <div class="category-header" style="padding: 1rem 1.25rem; background: linear-gradient(135deg, rgba(139, 115, 85, 0.1), rgba(139, 115, 85, 0.05)); border-bottom: 1px solid var(--border-color, #e0e0e0);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 1rem;"><?php echo e($category['name']); ?></h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted, #888);"><?php echo e($category['description']); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 1.25rem; font-weight: 600; color: #8B7355;"><?php echo $category['percentage']; ?>%</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted, #888);"><?php echo $category['booksComplete']; ?>/<?php echo $category['totalBooks']; ?> books</span>
                        </div>
                    </div>
                    <div style="background: var(--background, #f8f8f8); height: 6px; border-radius: 3px; overflow: hidden;">
                        <div style="width: <?php echo $category['percentage']; ?>%; height: 100%; background: #8B7355; border-radius: 3px;"></div>
                    </div>
                </div>
                <div class="books-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; padding: 1rem 1.25rem;">
                    <?php foreach ($category['books'] as $bookCode => $book): ?>
                    <div class="book-item <?php echo $book['isComplete'] ? 'complete' : ''; ?>" style="background: var(--background, #f8f8f8); padding: 0.75rem; border-radius: 8px; text-align: center; <?php echo $book['isComplete'] ? 'border: 2px solid #4CAF50;' : ''; ?>">
                        <?php if ($book['isComplete']): ?>
                            <div style="font-size: 1.25rem; color: #4CAF50; margin-bottom: 0.25rem;">&#x2714;</div>
                        <?php else: ?>
                            <div style="font-size: 1.25rem; color: #8B7355; margin-bottom: 0.25rem;"><?php echo $book['percentage']; ?>%</div>
                        <?php endif; ?>
                        <div style="font-weight: 500; font-size: 0.85rem; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo e($book['name']); ?>"><?php echo e($book['name']); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted, #888);">
                            <?php if ($book['total'] > 0): ?>
                                <?php echo $book['completed']; ?>/<?php echo $book['total']; ?> ch
                            <?php else: ?>
                                Not in plan
                            <?php endif; ?>
                        </div>
                        <?php if (!$book['isComplete'] && $book['total'] > 0): ?>
                        <div style="background: var(--border-color, #e0e0e0); height: 4px; border-radius: 2px; margin-top: 0.5rem; overflow: hidden;">
                            <div style="width: <?php echo $book['percentage']; ?>%; height: 100%; background: #8B7355; border-radius: 2px;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- New Testament Section -->
        <div class="testament-section">
            <h2 style="font-size: 1.25rem; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #5D8B55;">&#x2728;</span>
                New Testament
                <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-muted, #888); margin-left: auto;"><?php echo $overallProgress['newTestament']['percentage']; ?>%</span>
            </h2>

            <?php foreach ($newTestament as $catId => $category): ?>
            <div class="category-section" style="background: var(--card-bg, #fff); border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
                <div class="category-header" style="padding: 1rem 1.25rem; background: linear-gradient(135deg, rgba(93, 139, 85, 0.1), rgba(93, 139, 85, 0.05)); border-bottom: 1px solid var(--border-color, #e0e0e0);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 1rem;"><?php echo e($category['name']); ?></h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted, #888);"><?php echo e($category['description']); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 1.25rem; font-weight: 600; color: #5D8B55;"><?php echo $category['percentage']; ?>%</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted, #888);"><?php echo $category['booksComplete']; ?>/<?php echo $category['totalBooks']; ?> books</span>
                        </div>
                    </div>
                    <div style="background: var(--background, #f8f8f8); height: 6px; border-radius: 3px; overflow: hidden;">
                        <div style="width: <?php echo $category['percentage']; ?>%; height: 100%; background: #5D8B55; border-radius: 3px;"></div>
                    </div>
                </div>
                <div class="books-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; padding: 1rem 1.25rem;">
                    <?php foreach ($category['books'] as $bookCode => $book): ?>
                    <div class="book-item <?php echo $book['isComplete'] ? 'complete' : ''; ?>" style="background: var(--background, #f8f8f8); padding: 0.75rem; border-radius: 8px; text-align: center; <?php echo $book['isComplete'] ? 'border: 2px solid #4CAF50;' : ''; ?>">
                        <?php if ($book['isComplete']): ?>
                            <div style="font-size: 1.25rem; color: #4CAF50; margin-bottom: 0.25rem;">&#x2714;</div>
                        <?php else: ?>
                            <div style="font-size: 1.25rem; color: #5D8B55; margin-bottom: 0.25rem;"><?php echo $book['percentage']; ?>%</div>
                        <?php endif; ?>
                        <div style="font-weight: 500; font-size: 0.85rem; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo e($book['name']); ?>"><?php echo e($book['name']); ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted, #888);">
                            <?php if ($book['total'] > 0): ?>
                                <?php echo $book['completed']; ?>/<?php echo $book['total']; ?> ch
                            <?php else: ?>
                                Not in plan
                            <?php endif; ?>
                        </div>
                        <?php if (!$book['isComplete'] && $book['total'] > 0): ?>
                        <div style="background: var(--border-color, #e0e0e0); height: 4px; border-radius: 2px; margin-top: 0.5rem; overflow: hidden;">
                            <div style="width: <?php echo $book['percentage']; ?>%; height: 100%; background: #5D8B55; border-radius: 2px;"></div>
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
    @media (max-width: 768px) {
        .overall-stats { grid-template-columns: 1fr !important; }
        .books-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)) !important; }
    }
</style>
';

$pageTitle = 'Books';
require TEMPLATE_PATH . '/layout.php';
