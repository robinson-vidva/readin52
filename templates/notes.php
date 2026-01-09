<?php
$user = Auth::getUser();
$search = $_GET['search'] ?? '';
$notes = Note::getAllForUser($user['id'], $search ?: null);
$noteCount = Note::getCount($user['id']);

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

<div class="notes-page">
    <div class="container" style="max-width: 900px;">
        <!-- Page Header -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="margin: 0 0 0.25rem 0; font-size: 1.75rem;">My Notes</h1>
                <p style="margin: 0; color: var(--text-secondary, #666);"><?php echo $noteCount; ?> note<?php echo $noteCount !== 1 ? 's' : ''; ?></p>
            </div>
            <button onclick="openNoteModal()" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.25rem;">+</span> New Note
            </button>
        </div>

        <!-- Search -->
        <div style="margin-bottom: 1.5rem;">
            <form method="GET" action="/?route=notes" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="route" value="notes">
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search notes..."
                       style="flex: 1; padding: 0.75rem 1rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem;">
                <?php if ($search): ?>
                    <a href="/?route=notes" class="btn btn-secondary" style="padding: 0.75rem 1rem;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Notes Grid -->
        <?php if (empty($notes)): ?>
            <div style="text-align: center; padding: 3rem; background: var(--card-bg, #fff); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">&#x1F4DD;</div>
                <?php if ($search): ?>
                    <h3 style="margin: 0 0 0.5rem 0;">No notes found</h3>
                    <p style="color: var(--text-secondary, #666); margin: 0;">Try a different search term</p>
                <?php else: ?>
                    <h3 style="margin: 0 0 0.5rem 0;">No notes yet</h3>
                    <p style="color: var(--text-secondary, #666); margin: 0 0 1rem 0;">Start capturing your thoughts and insights</p>
                    <button onclick="openNoteModal()" class="btn btn-primary">Create your first note</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="notes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                <?php foreach ($notes as $note):
                    $colors = $colorMap[$note['color']] ?? $colorMap['default'];
                    $reference = Note::formatReference($note);
                ?>
                <div class="note-card"
                     style="background: <?php echo $colors['bg']; ?>; border: 1px solid <?php echo $colors['border']; ?>; border-radius: 12px; padding: 1rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                     onclick="openNoteModal(<?php echo $note['id']; ?>)"
                     onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';"
                     onmouseout="this.style.transform=''; this.style.boxShadow='';">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo e($note['title']); ?></h3>
                    <p style="margin: 0 0 0.75rem 0; font-size: 0.9rem; color: var(--text-secondary, #555); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.4;"><?php echo e(substr($note['content'], 0, 200)); ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-muted, #888);">
                        <span><?php echo date('M j, Y', strtotime($note['updated_at'])); ?></span>
                        <?php if ($reference): ?>
                            <span style="background: rgba(0,0,0,0.05); padding: 0.25rem 0.5rem; border-radius: 4px;"><?php echo e($reference); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Note Modal -->
<div id="noteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--card-bg, #fff); border-radius: 12px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative;">
        <form id="noteForm" method="POST" action="/?route=notes/save">
            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
            <input type="hidden" name="note_id" id="noteId" value="">

            <div style="padding: 1.25rem; border-bottom: 1px solid var(--border-color, #e0e0e0); display: flex; justify-content: space-between; align-items: center;">
                <h2 id="modalTitle" style="margin: 0; font-size: 1.25rem;">New Note</h2>
                <button type="button" onclick="closeNoteModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted, #888);">&times;</button>
            </div>

            <div style="padding: 1.25rem;">
                <div style="margin-bottom: 1rem;">
                    <input type="text" name="title" id="noteTitle" placeholder="Note title"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem; font-weight: 500;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <textarea name="content" id="noteContent" placeholder="Write your thoughts..." rows="8"
                              style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; font-size: 1rem; resize: vertical; font-family: inherit;"></textarea>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary, #666);">Color</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <?php foreach ($colorMap as $colorName => $colorValues): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="color" value="<?php echo $colorName; ?>" <?php echo $colorName === 'default' ? 'checked' : ''; ?> style="display: none;">
                            <span class="color-option" style="display: block; width: 32px; height: 32px; border-radius: 50%; background: <?php echo $colorValues['bg']; ?>; border: 2px solid <?php echo $colorValues['border']; ?>; transition: transform 0.2s;"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div style="padding: 1.25rem; border-top: 1px solid var(--border-color, #e0e0e0); display: flex; justify-content: space-between; align-items: center;">
                <button type="button" id="deleteBtn" onclick="deleteNote()" style="display: none; background: #f44336; color: white; border: none; padding: 0.75rem 1rem; border-radius: 8px; cursor: pointer;">Delete</button>
                <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                    <button type="button" onclick="closeNoteModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

$extraStyles = '
<style>
    .color-option { opacity: 0.7; }
    input[type="radio"]:checked + .color-option {
        opacity: 1;
        transform: scale(1.1);
        box-shadow: 0 0 0 2px var(--card-bg, #fff), 0 0 0 4px var(--primary-brown, #5D4037);
    }
    @media (max-width: 600px) {
        .notes-grid { grid-template-columns: 1fr !important; }
    }
</style>
';

$extraScripts = '
<script>
    const noteModal = document.getElementById("noteModal");
    const noteForm = document.getElementById("noteForm");
    const noteIdInput = document.getElementById("noteId");
    const noteTitleInput = document.getElementById("noteTitle");
    const noteContentInput = document.getElementById("noteContent");
    const modalTitle = document.getElementById("modalTitle");
    const deleteBtn = document.getElementById("deleteBtn");

    function openNoteModal(noteId = null) {
        noteForm.reset();
        noteIdInput.value = noteId || "";

        if (noteId) {
            modalTitle.textContent = "Edit Note";
            deleteBtn.style.display = "block";
            // Fetch note data
            fetch("/?route=api/notes/" + noteId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        noteTitleInput.value = data.note.title;
                        noteContentInput.value = data.note.content;
                        // Set color
                        const colorRadio = document.querySelector(\'input[name="color"][value="\' + data.note.color + \'"]\');
                        if (colorRadio) colorRadio.checked = true;
                    }
                });
        } else {
            modalTitle.textContent = "New Note";
            deleteBtn.style.display = "none";
        }

        noteModal.style.display = "flex";
        noteTitleInput.focus();
    }

    function closeNoteModal() {
        noteModal.style.display = "none";
    }

    function deleteNote() {
        if (!confirm("Are you sure you want to delete this note?")) return;

        const noteId = noteIdInput.value;
        fetch("/?route=notes/delete", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ note_id: noteId, csrf_token: "<?php echo getCsrfToken(); ?>" })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert("Failed to delete note");
            }
        });
    }

    // Close modal on backdrop click
    noteModal.addEventListener("click", function(e) {
        if (e.target === noteModal) closeNoteModal();
    });

    // Close modal on Escape key
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") closeNoteModal();
    });
</script>
';

$pageTitle = 'Notes';
require TEMPLATE_PATH . '/layout.php';
