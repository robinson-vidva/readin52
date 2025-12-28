<?php
$weeks = ReadingPlan::getWeeks();
$categories = ReadingPlan::getCategories();
$selectedWeek = get('week', 1);
$selectedWeek = max(1, min(52, intval($selectedWeek)));
$weekData = ReadingPlan::getWeek($selectedWeek);

ob_start();
?>

<div class="admin-reading-plan">
    <!-- Export/Import -->
    <div class="toolbar">
        <div class="toolbar-left">
            <a href="/admin/reading-plan/export" class="btn btn-secondary">Export JSON</a>
            <button class="btn btn-secondary" onclick="showImportModal()">Import JSON</button>
        </div>
    </div>

    <!-- Week Selector -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Select Week</h2>
        </div>
        <div class="card-body">
            <div class="week-grid">
                <?php for ($w = 1; $w <= 52; $w++): ?>
                    <a href="/admin/reading-plan?week=<?php echo $w; ?>"
                       class="week-btn <?php echo $w == $selectedWeek ? 'active' : ''; ?>">
                        <?php echo $w; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Week Details -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Week <?php echo $selectedWeek; ?> Readings</h2>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/reading-plan" class="reading-plan-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="week" value="<?php echo $selectedWeek; ?>">

                <div class="readings-grid">
                    <?php foreach ($categories as $category): ?>
                        <?php
                        $catId = $category['id'];
                        $reading = $weekData['readings'][$catId] ?? null;
                        ?>
                        <div class="reading-edit-card" style="border-color: <?php echo e($category['color']); ?>">
                            <div class="reading-edit-header" style="background-color: <?php echo e($category['color']); ?>">
                                <?php echo e($category['name']); ?>
                            </div>
                            <div class="reading-edit-body">
                                <div class="form-group">
                                    <label>Reference</label>
                                    <input type="text"
                                           name="readings[<?php echo $catId; ?>][reference]"
                                           value="<?php echo e($reading['reference'] ?? ''); ?>"
                                           placeholder="e.g., Genesis 1-7">
                                </div>

                                <div class="form-group">
                                    <label>Passages (JSON)</label>
                                    <textarea name="readings[<?php echo $catId; ?>][passages]"
                                              rows="3"
                                              placeholder='[{"book": "GEN", "chapters": [1,2,3]}]'><?php
                                        echo e(json_encode($reading['passages'] ?? [], JSON_PRETTY_PRINT));
                                    ?></textarea>
                                    <small class="form-hint">
                                        Format: [{"book": "BOOK_ID", "chapters": [1, 2, 3]}]
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Week <?php echo $selectedWeek; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- All Weeks Overview -->
    <div class="admin-card">
        <div class="card-header">
            <h2>All Weeks Overview</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table compact">
                    <thead>
                        <tr>
                            <th>Week</th>
                            <?php foreach ($categories as $cat): ?>
                                <th style="color: <?php echo e($cat['color']); ?>">
                                    <?php echo e($cat['name']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weeks as $week): ?>
                            <tr class="<?php echo $week['week'] == $selectedWeek ? 'highlight' : ''; ?>">
                                <td>
                                    <a href="/admin/reading-plan?week=<?php echo $week['week']; ?>">
                                        Week <?php echo $week['week']; ?>
                                    </a>
                                </td>
                                <?php foreach ($categories as $cat): ?>
                                    <td>
                                        <?php echo e($week['readings'][$cat['id']]['reference'] ?? '-'); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Import Reading Plan</h2>
            <button class="modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <form method="POST" action="/admin/reading-plan/import" enctype="multipart/form-data" class="modal-body">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>Upload JSON File</label>
                <input type="file" name="json_file" accept=".json" required>
            </div>

            <p class="text-warning">
                Warning: This will replace the entire reading plan. Make sure to export a backup first.
            </p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showImportModal() {
        document.getElementById('importModal').classList.add('show');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.remove('show');
    }

    // Close modal on outside click
    document.getElementById('importModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeImportModal();
        }
    });
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Reading Plan';
require TEMPLATE_PATH . '/admin/layout.php';
