<?php
$search = get('search', '');
$users = $search ? User::searchWithProgress($search) : User::getAllWithProgress(100);
$translations = ReadingPlan::getTranslations();
$totalReadings = 208; // 52 weeks × 4 categories

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
                            <th>Progress</th>
                            <th>Badges</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < count($users); $i++):
                            $progressPercent = $totalReadings > 0 ? round(($users[$i]['completed_readings'] / $totalReadings) * 100, 1) : 0;
                        ?>
                            <tr class="clickable-row" data-user-id="<?php echo $users[$i]['id']; ?>" style="cursor: pointer;">
                                <td><?php echo $users[$i]['id']; ?></td>
                                <td>
                                    <a href="/?route=admin/user-progress&amp;id=<?php echo $users[$i]['id']; ?>" class="user-link">
                                        <?php echo e($users[$i]['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo e($users[$i]['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $users[$i]['role']; ?>">
                                        <?php echo ucfirst($users[$i]['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-cell">
                                        <div class="progress-bar-mini">
                                            <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $progressPercent; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($users[$i]['badge_count'] > 0): ?>
                                        <span class="badge-count" title="<?php echo $users[$i]['badge_count']; ?> badges earned">
                                            &#x1F3C6; <?php echo $users[$i]['badge_count']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-count-none">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $users[$i]['last_login'] ? timeAgo($users[$i]['last_login']) : 'Never'; ?></td>
                                <td><?php echo formatDate($users[$i]['created_at'], 'M j, Y'); ?></td>
                                <td class="actions">
                                    <a href="/?route=admin/user-progress&amp;id=<?php echo $users[$i]['id']; ?>" class="btn btn-sm btn-primary" title="View Progress">
                                        &#x1F4CA;
                                    </a>
                                    <button class="btn btn-sm btn-secondary"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($users[$i])); ?>)">
                                        Edit
                                    </button>
                                    <?php if ($users[$i]['id'] !== Auth::getUserId()): ?>
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deleteUser(<?php echo $users[$i]['id']; ?>, '<?php echo e($users[$i]['name']); ?>')">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
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

<style>
    .user-link {
        color: var(--primary, #5D4037);
        text-decoration: none;
        font-weight: 500;
    }
    .user-link:hover {
        text-decoration: underline;
    }
    .progress-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .progress-bar-mini {
        width: 80px;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-bar-mini .progress-fill {
        height: 100%;
        background: #43A047;
        border-radius: 4px;
        transition: width 0.3s ease;
        min-width: 2px;
    }
    .progress-text {
        font-size: 0.85rem;
        color: var(--text-secondary, #666);
        min-width: 45px;
        font-weight: 500;
    }
    .badge-count {
        font-size: 0.9rem;
        white-space: nowrap;
    }
    .badge-count-none {
        color: var(--text-secondary, #999);
    }
    .clickable-row:hover {
        background-color: #f8f9fa;
    }
    .admin-table td {
        vertical-align: middle;
    }
    .actions {
        white-space: nowrap;
    }
    .actions .btn {
        margin-right: 0.25rem;
    }
    .actions .btn:last-child {
        margin-right: 0;
    }
</style>

<script>
    // Add click handler for rows
    document.querySelectorAll('.clickable-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.actions') || e.target.closest('a') || e.target.closest('button')) {
                return; // Don't navigate if clicking actions
            }
            var userId = this.getAttribute('data-user-id');
            if (userId) {
                window.location.href = '/?route=admin/user-progress&id=' + userId;
            }
        });
    });

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
