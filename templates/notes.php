<?php
$user = Auth::getUser();
$search = get('search', '');
$page = max(1, (int) get('page', 1));
$perPage = 9;

$result = Note::getPaginated($user['id'], $page, $perPage, $search ?: null);
$notes = $result['notes'];
$noteCount = $result['total'];
$totalPages = $result['totalPages'];

ob_start();
?>

<div class="notes-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>My Notes</h1>
                <p><?php echo $noteCount; ?> note<?php echo $noteCount !== 1 ? 's' : ''; ?> total</p>
            </div>
            <?php if ($noteCount > 0 || $search): ?>
            <button onclick="openNewNoteModal()" class="btn btn-primary">
                <span>+</span> New Note
            </button>
            <?php endif; ?>
        </div>

        <?php if ($noteCount > 0 || $search): ?>
        <!-- Search Bar -->
        <div class="search-form" style="margin-bottom: var(--spacing-xl, 2rem);">
            <form method="GET" action="/?route=notes" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="route" value="notes">
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search notes..." style="flex: 1;">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="/?route=notes" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($search): ?>
            <p style="margin-bottom: 1rem; color: var(--text-secondary, #666);">Showing results for "<?php echo e($search); ?>"</p>
        <?php endif; ?>

        <!-- Notes Grid -->
        <?php if (empty($notes)): ?>
            <div style="text-align: center; padding: 3rem; background: var(--card-bg, #fff); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">&#x1F4DD;</div>
                <?php if ($search): ?>
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--text-primary, #333);">No notes found</h3>
                    <p style="margin: 0; color: var(--text-secondary, #666);">Try a different search term</p>
                <?php else: ?>
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--text-primary, #333);">No notes yet</h3>
                    <p style="margin: 0 0 1.5rem 0; color: var(--text-secondary, #666);">Start adding notes while reading to capture your thoughts and insights</p>
                    <button onclick="openNewNoteModal()" class="btn btn-primary">Create Your First Note</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
                <?php foreach ($notes as $note): ?>
                    <?php $reference = Note::formatReference($note); ?>
                    <div class="note-card" onclick="openEditNoteModal(<?php echo $note['id']; ?>)" style="background: var(--card-bg, #fff); border-radius: 12px; padding: 1.25rem; cursor: pointer; border: 1px solid var(--border-color, #e0e0e0); transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1.1rem; color: var(--primary, #5D4037); font-weight: 600;"><?php echo e($note['title'] ?: 'Untitled'); ?></h3>
                        </div>
                        <?php if ($reference): ?>
                            <div style="display: inline-block; background: var(--background, #f5f5f5); padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.8rem; color: var(--text-secondary, #666); margin-bottom: 0.75rem;">
                                <?php echo e($reference); ?>
                            </div>
                        <?php endif; ?>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary, #666); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.6;"><?php echo e(substr($note['content'], 0, 200)); ?></p>
                        <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted, #999);">
                            <?php echo date('M j, Y', strtotime($note['updated_at'] ?? $note['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <!-- Pagination -->
            <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                <?php
                $baseUrl = '/?route=notes' . ($search ? '&search=' . urlencode($search) : '');
                ?>

                <?php if ($page > 1): ?>
                    <a href="<?php echo $baseUrl; ?>&page=1" class="pagination-btn" title="First page">&laquo;</a>
                    <a href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">&lsaquo; Prev</a>
                <?php endif; ?>

                <?php
                // Show page numbers
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                if ($startPage > 1): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php endif;

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-btn pagination-current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor;

                if ($endPage < $totalPages): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">Next &rsaquo;</a>
                    <a href="<?php echo $baseUrl; ?>&page=<?php echo $totalPages; ?>" class="pagination-btn" title="Last page">&raquo;</a>
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted, #999);">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?> (<?php echo $noteCount; ?> notes)
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Note Modal for Create/Edit -->
<div id="notePageModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px; padding: 0;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color, #e0e0e0); display: flex; justify-content: space-between; align-items: center; background: var(--background, #f5f5f5);">
            <h3 id="notePageModalTitle" style="margin: 0; font-size: 1.2rem; color: var(--primary, #5D4037);">New Note</h3>
            <button onclick="closeNotePageModal()" style="background: none; border: none; font-size: 1.75rem; cursor: pointer; color: var(--text-secondary, #666); line-height: 1;">&times;</button>
        </div>
        <div style="padding: 1.5rem;">
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; font-size: 0.95rem;">Title</label>
                <input type="text" id="notePageTitle" placeholder="Enter a title for your note..." style="width: 100%; padding: 0.875rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; font-size: 0.95rem;">Note</label>
                <textarea id="notePageContent" placeholder="Write your thoughts, reflections, or insights..." rows="8" style="width: 100%; padding: 0.875rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem; resize: vertical; font-family: inherit; box-sizing: border-box; min-height: 200px;"></textarea>
            </div>
            <input type="hidden" id="notePageEditingId" value="">
            <div style="display: flex; gap: 0.75rem; justify-content: space-between; align-items: center;">
                <button id="notePageDeleteBtn" onclick="deleteNoteFromPage()" style="display: none; padding: 0.75rem; background: none; border: 1px solid #dc3545; color: #dc3545; border-radius: 8px; cursor: pointer; font-size: 1.1rem; transition: all 0.2s;" title="Delete Note">
                    &#x1F5D1;
                </button>
                <div style="display: flex; gap: 0.75rem; margin-left: auto;">
                    <button onclick="closeNotePageModal()" style="padding: 0.875rem 1.75rem; border: 1px solid var(--border-color, #ddd); background: white; border-radius: 8px; cursor: pointer; font-size: 1rem;">Cancel</button>
                    <button onclick="saveNoteFromPage()" style="padding: 0.875rem 1.75rem; background: var(--primary, #5D4037); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 500;">Save Note</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.note-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.75rem;
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e0e0e0);
    border-radius: 8px;
    color: var(--text-primary, #333);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.pagination-btn:hover {
    background: var(--primary, #5D4037);
    color: white;
    border-color: var(--primary, #5D4037);
}
.pagination-current {
    background: var(--primary, #5D4037);
    color: white;
    border-color: var(--primary, #5D4037);
}
.pagination-ellipsis {
    padding: 0 0.5rem;
    color: var(--text-muted, #999);
}
</style>

<script>
const csrfToken = '<?php echo getCsrfToken(); ?>';
const allNotes = <?php echo json_encode($notes); ?>;

function openNewNoteModal() {
    document.getElementById('notePageModalTitle').textContent = 'New Note';
    document.getElementById('notePageTitle').value = '';
    document.getElementById('notePageContent').value = '';
    document.getElementById('notePageEditingId').value = '';
    document.getElementById('notePageDeleteBtn').style.display = 'none';
    document.getElementById('notePageModal').style.display = 'flex';
    document.getElementById('notePageModal').classList.add('show');
    setTimeout(() => document.getElementById('notePageContent').focus(), 100);
}

function openEditNoteModal(noteId) {
    const note = allNotes.find(n => n.id == noteId);
    if (!note) return;

    document.getElementById('notePageModalTitle').textContent = 'Edit Note';
    document.getElementById('notePageTitle').value = note.title || '';
    document.getElementById('notePageContent').value = note.content || '';
    document.getElementById('notePageEditingId').value = noteId;
    document.getElementById('notePageDeleteBtn').style.display = 'block';
    document.getElementById('notePageModal').style.display = 'flex';
    document.getElementById('notePageModal').classList.add('show');
}

function closeNotePageModal() {
    document.getElementById('notePageModal').style.display = 'none';
    document.getElementById('notePageModal').classList.remove('show');
}

async function saveNoteFromPage() {
    const title = document.getElementById('notePageTitle').value.trim() || 'Untitled Note';
    const content = document.getElementById('notePageContent').value.trim();
    const editingId = document.getElementById('notePageEditingId').value;

    if (!content) {
        alert('Please write something in your note');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('ajax', '1');
    formData.append('title', title);
    formData.append('content', content);
    formData.append('color', 'default');

    if (editingId) {
        formData.append('note_id', editingId);
    }

    try {
        const response = await fetch('/?route=notes/save', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert('Failed to save note: ' + (result.error || 'Unknown error'));
        }
    } catch (e) {
        console.error('Save note error:', e);
        alert('Failed to save note. Please try again.');
    }
}

async function deleteNoteFromPage() {
    const noteId = document.getElementById('notePageEditingId').value;
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
            window.location.reload();
        } else {
            alert('Failed to delete note');
        }
    } catch (e) {
        console.error('Delete note error:', e);
        alert('Failed to delete note');
    }
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'My Notes';
require TEMPLATE_PATH . '/layout.php';
