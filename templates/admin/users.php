<?php
$search = get('search', '');
$users = $search ? User::search($search) : User::getAll(100);
$translations = ReadingPlan::getTranslations();

ob_start();
?>

<div class="admin-users">
    <!-- Search & Actions -->
    <div class="toolbar">
        <form method="GET" action="/" class="search-form">
            <input type="hidden" name="route" value="admin/users">
            <input type="text" name="search" placeholder="Search users..."
                   value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="/?route=admin/users" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="admin-card">
        <div class="card-header">
            <h2>Users (<?php echo count($users); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Translation</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo e($user['name']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo e($user['preferred_translation']); ?></td>
                                <td><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Never'; ?></td>
                                <td><?php echo formatDate($user['created_at'], 'M j, Y'); ?></td>
                                <td class="actions">
                                    <button class="btn btn-sm btn-secondary"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        Edit
                                    </button>
                                    <?php if ($user['id'] !== Auth::getUserId()): ?>
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo e($user['name']); ?>')">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="/?route=admin/users" class="modal-body">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="editUserId">

            <div class="form-group">
                <label for="editName">Name</label>
                <input type="text" id="editName" name="name" required>
            </div>

            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email" required>
            </div>

            <div class="form-group">
                <label for="editRole">Role</label>
                <select id="editRole" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="editTranslation">Preferred Translation</label>
                <select id="editTranslation" name="preferred_translation">
                    <?php foreach ($translations as $trans): ?>
                        <option value="<?php echo e($trans['id']); ?>">
                            <?php echo e($trans['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="editPassword">New Password (leave blank to keep current)</label>
                <input type="password" id="editPassword" name="new_password" minlength="6">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>Delete User</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" action="/?route=admin/users" class="modal-body">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="deleteUserId">

            <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
            <p class="text-danger">This action cannot be undone. All reading progress will be lost.</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete User</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editUser(user) {
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editName').value = user.name;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editRole').value = user.role;
        document.getElementById('editTranslation').value = user.preferred_translation;
        document.getElementById('editPassword').value = '';
        document.getElementById('editUserModal').classList.add('show');
    }

    function closeEditModal() {
        document.getElementById('editUserModal').classList.remove('show');
    }

    function deleteUser(id, name) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('deleteUserModal').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteUserModal').classList.remove('show');
    }

    // Close modals on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Users';
require TEMPLATE_PATH . '/admin/layout.php';
