<?php
$weeks = ReadingPlan::getWeeks();
$categories = ReadingPlan::getCategories();
$selectedWeek = get('week', 1);
$selectedWeek = max(1, min(52, intval($selectedWeek)));
$weekData = ReadingPlan::getWeek($selectedWeek);

// Bible books for dropdown
$bibleBooks = [
    'Old Testament' => [
        'GEN' => 'Genesis', 'EXO' => 'Exodus', 'LEV' => 'Leviticus', 'NUM' => 'Numbers',
        'DEU' => 'Deuteronomy', 'JOS' => 'Joshua', 'JDG' => 'Judges', 'RUT' => 'Ruth',
        '1SA' => '1 Samuel', '2SA' => '2 Samuel', '1KI' => '1 Kings', '2KI' => '2 Kings',
        '1CH' => '1 Chronicles', '2CH' => '2 Chronicles', 'EZR' => 'Ezra', 'NEH' => 'Nehemiah',
        'EST' => 'Esther', 'JOB' => 'Job', 'PSA' => 'Psalms', 'PRO' => 'Proverbs',
        'ECC' => 'Ecclesiastes', 'SNG' => 'Song of Solomon', 'ISA' => 'Isaiah', 'JER' => 'Jeremiah',
        'LAM' => 'Lamentations', 'EZK' => 'Ezekiel', 'DAN' => 'Daniel', 'HOS' => 'Hosea',
        'JOL' => 'Joel', 'AMO' => 'Amos', 'OBA' => 'Obadiah', 'JON' => 'Jonah',
        'MIC' => 'Micah', 'NAM' => 'Nahum', 'HAB' => 'Habakkuk', 'ZEP' => 'Zephaniah',
        'HAG' => 'Haggai', 'ZEC' => 'Zechariah', 'MAL' => 'Malachi'
    ],
    'New Testament' => [
        'MAT' => 'Matthew', 'MRK' => 'Mark', 'LUK' => 'Luke', 'JHN' => 'John',
        'ACT' => 'Acts', 'ROM' => 'Romans', '1CO' => '1 Corinthians', '2CO' => '2 Corinthians',
        'GAL' => 'Galatians', 'EPH' => 'Ephesians', 'PHP' => 'Philippians', 'COL' => 'Colossians',
        '1TH' => '1 Thessalonians', '2TH' => '2 Thessalonians', '1TI' => '1 Timothy', '2TI' => '2 Timothy',
        'TIT' => 'Titus', 'PHM' => 'Philemon', 'HEB' => 'Hebrews', 'JAS' => 'James',
        '1PE' => '1 Peter', '2PE' => '2 Peter', '1JN' => '1 John', '2JN' => '2 John',
        '3JN' => '3 John', 'JUD' => 'Jude', 'REV' => 'Revelation'
    ]
];

// Flatten for easy lookup
$allBooks = array_merge($bibleBooks['Old Testament'], $bibleBooks['New Testament']);

ob_start();
?>

<div class="admin-reading-plan">
    <!-- Export/Import -->
    <div class="toolbar">
        <div class="toolbar-left">
            <a href="/?route=admin/reading-plan/export" class="btn btn-secondary">Export JSON</a>
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
                    <a href="/?route=admin/reading-plan&week=<?php echo $w; ?>"
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

            <form method="POST" action="/?route=admin/reading-plan" class="reading-plan-form" id="readingPlanForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="week" value="<?php echo $selectedWeek; ?>">

                <div class="readings-grid">
                    <?php foreach ($categories as $category): ?>
                        <?php
                        $catId = $category['id'];
                        $reading = $weekData['readings'][$catId] ?? null;
                        $passages = $reading['passages'] ?? [];
                        ?>
                        <div class="reading-edit-card" style="border-color: <?php echo e($category['color']); ?>">
                            <div class="reading-edit-header" style="background-color: <?php echo e($category['color']); ?>">
                                <?php echo e($category['name']); ?>
                            </div>
                            <div class="reading-edit-body">
                                <!-- Hidden fields for form submission -->
                                <input type="hidden" name="readings[<?php echo $catId; ?>][reference]"
                                       id="ref_<?php echo $catId; ?>"
                                       value="<?php echo e($reading['reference'] ?? ''); ?>">
                                <input type="hidden" name="readings[<?php echo $catId; ?>][passages]"
                                       id="passages_<?php echo $catId; ?>"
                                       value="<?php echo e(json_encode($passages)); ?>">

                                <!-- Display current reference -->
                                <div class="form-group">
                                    <label>Current Reference</label>
                                    <div class="reference-display" id="display_<?php echo $catId; ?>" style="padding: 0.5rem; background: #f5f5f5; border-radius: 4px; min-height: 2rem;">
                                        <?php echo e($reading['reference'] ?? 'Not set'); ?>
                                    </div>
                                </div>

                                <!-- Passage Editor -->
                                <div class="passage-editor" data-category="<?php echo $catId; ?>">
                                    <label>Edit Passages</label>
                                    <div class="passage-list" id="list_<?php echo $catId; ?>">
                                        <?php if (!empty($passages)): ?>
                                            <?php foreach ($passages as $idx => $passage): ?>
                                                <div class="passage-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;">
                                                    <select class="passage-book" style="flex: 2;" data-cat="<?php echo $catId; ?>">
                                                        <option value="">Select Book</option>
                                                        <optgroup label="Old Testament">
                                                            <?php foreach ($bibleBooks['Old Testament'] as $code => $name): ?>
                                                                <option value="<?php echo $code; ?>" <?php echo ($passage['book'] ?? '') === $code ? 'selected' : ''; ?>>
                                                                    <?php echo e($name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                        <optgroup label="New Testament">
                                                            <?php foreach ($bibleBooks['New Testament'] as $code => $name): ?>
                                                                <option value="<?php echo $code; ?>" <?php echo ($passage['book'] ?? '') === $code ? 'selected' : ''; ?>>
                                                                    <?php echo e($name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    </select>
                                                    <input type="text" class="passage-chapters" placeholder="e.g., 1-7 or 1,2,3"
                                                           style="flex: 1;" data-cat="<?php echo $catId; ?>"
                                                           value="<?php echo e(implode(',', $passage['chapters'] ?? [])); ?>">
                                                    <button type="button" class="btn btn-sm btn-danger remove-passage" style="padding: 0.25rem 0.5rem;">×</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="passage-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;">
                                                <select class="passage-book" style="flex: 2;" data-cat="<?php echo $catId; ?>">
                                                    <option value="">Select Book</option>
                                                    <optgroup label="Old Testament">
                                                        <?php foreach ($bibleBooks['Old Testament'] as $code => $name): ?>
                                                            <option value="<?php echo $code; ?>"><?php echo e($name); ?></option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                    <optgroup label="New Testament">
                                                        <?php foreach ($bibleBooks['New Testament'] as $code => $name): ?>
                                                            <option value="<?php echo $code; ?>"><?php echo e($name); ?></option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                </select>
                                                <input type="text" class="passage-chapters" placeholder="e.g., 1-7 or 1,2,3"
                                                       style="flex: 1;" data-cat="<?php echo $catId; ?>">
                                                <button type="button" class="btn btn-sm btn-danger remove-passage" style="padding: 0.25rem 0.5rem;">×</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-secondary add-passage" data-cat="<?php echo $catId; ?>" style="margin-top: 0.5rem;">
                                        + Add Book
                                    </button>
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
                                    <a href="/?route=admin/reading-plan&week=<?php echo $week['week']; ?>">
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
        <form method="POST" action="/?route=admin/reading-plan/import" enctype="multipart/form-data" class="modal-body">
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

<!-- Book names for JavaScript -->
<script>
const bookNames = <?php echo json_encode($allBooks); ?>;

// Parse chapter string (e.g., "1-7" or "1,2,3") into array of integers
function parseChapters(str) {
    if (!str || !str.trim()) return [];
    const chapters = [];
    const parts = str.split(',').map(p => p.trim());

    for (const part of parts) {
        if (part.includes('-')) {
            const [start, end] = part.split('-').map(n => parseInt(n.trim()));
            if (!isNaN(start) && !isNaN(end)) {
                for (let i = start; i <= end; i++) {
                    if (!chapters.includes(i)) chapters.push(i);
                }
            }
        } else {
            const num = parseInt(part);
            if (!isNaN(num) && !chapters.includes(num)) {
                chapters.push(num);
            }
        }
    }
    return chapters.sort((a, b) => a - b);
}

// Format chapters array to string (e.g., [1,2,3,4,5] -> "1-5")
function formatChapters(chapters) {
    if (!chapters || chapters.length === 0) return '';

    chapters = [...chapters].sort((a, b) => a - b);
    const ranges = [];
    let start = chapters[0];
    let end = chapters[0];

    for (let i = 1; i < chapters.length; i++) {
        if (chapters[i] === end + 1) {
            end = chapters[i];
        } else {
            ranges.push(start === end ? String(start) : `${start}-${end}`);
            start = end = chapters[i];
        }
    }
    ranges.push(start === end ? String(start) : `${start}-${end}`);

    return ranges.join(', ');
}

// Build reference string from passages
function buildReference(passages) {
    const parts = [];
    for (const p of passages) {
        if (p.book && p.chapters.length > 0) {
            const bookName = bookNames[p.book] || p.book;
            const chapStr = formatChapters(p.chapters);
            parts.push(`${bookName} ${chapStr}`);
        }
    }
    return parts.join(', ');
}

// Update hidden fields and display for a category
function updateCategory(catId) {
    const list = document.getElementById('list_' + catId);
    const rows = list.querySelectorAll('.passage-row');
    const passages = [];

    rows.forEach(row => {
        const book = row.querySelector('.passage-book').value;
        const chaptersStr = row.querySelector('.passage-chapters').value;
        const chapters = parseChapters(chaptersStr);

        if (book && chapters.length > 0) {
            passages.push({ book, chapters });
        }
    });

    const reference = buildReference(passages);
    document.getElementById('ref_' + catId).value = reference;
    document.getElementById('passages_' + catId).value = JSON.stringify(passages);
    document.getElementById('display_' + catId).textContent = reference || 'Not set';
}

// Add new passage row
function addPassageRow(catId) {
    const list = document.getElementById('list_' + catId);
    const row = document.createElement('div');
    row.className = 'passage-row';
    row.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;';
    row.innerHTML = `
        <select class="passage-book" style="flex: 2;" data-cat="${catId}">
            <option value="">Select Book</option>
            <optgroup label="Old Testament">
                <?php foreach ($bibleBooks['Old Testament'] as $code => $name): ?>
                    <option value="<?php echo $code; ?>"><?php echo e($name); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="New Testament">
                <?php foreach ($bibleBooks['New Testament'] as $code => $name): ?>
                    <option value="<?php echo $code; ?>"><?php echo e($name); ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
        <input type="text" class="passage-chapters" placeholder="e.g., 1-7 or 1,2,3" style="flex: 1;" data-cat="${catId}">
        <button type="button" class="btn btn-sm btn-danger remove-passage" style="padding: 0.25rem 0.5rem;">×</button>
    `;
    list.appendChild(row);
    attachRowListeners(row, catId);
}

// Attach event listeners to a passage row
function attachRowListeners(row, catId) {
    row.querySelector('.passage-book').addEventListener('change', () => updateCategory(catId));
    row.querySelector('.passage-chapters').addEventListener('input', () => updateCategory(catId));
    row.querySelector('.remove-passage').addEventListener('click', function() {
        const list = document.getElementById('list_' + catId);
        if (list.querySelectorAll('.passage-row').length > 1) {
            row.remove();
            updateCategory(catId);
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Add passage buttons
    document.querySelectorAll('.add-passage').forEach(btn => {
        btn.addEventListener('click', function() {
            addPassageRow(this.dataset.cat);
        });
    });

    // Existing passage rows
    document.querySelectorAll('.passage-row').forEach(row => {
        const catId = row.querySelector('.passage-book').dataset.cat;
        attachRowListeners(row, catId);
    });

    // Update on form submit
    document.getElementById('readingPlanForm').addEventListener('submit', function(e) {
        <?php foreach ($categories as $cat): ?>
        updateCategory('<?php echo $cat['id']; ?>');
        <?php endforeach; ?>
    });
});

function showImportModal() {
    document.getElementById('importModal').classList.add('show');
}

function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
}

document.getElementById('importModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportModal();
    }
});
</script>

<style>
.passage-editor { margin-top: 1rem; }
.passage-row select, .passage-row input {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.passage-row select:focus, .passage-row input:focus {
    border-color: #5D4037;
    outline: none;
}
.reference-display {
    font-weight: 600;
    color: #333;
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Reading Plan';
require TEMPLATE_PATH . '/admin/layout.php';
