<?php
$search = get('search', '');
$users = $search ? User::searchWithProgress($search) : User::getAllWithProgress(100);
$translations = ReadingPlan::getTranslations();
$totalReadings = 208; // 52 weeks × 4 categories

ob_start();
?>

<div class="admin-users">
    <!-- Search -->
    <div class="toolbar">
        <form method="GET" action="/" class="search-form">
            <input type="hidden" name="route" value="admin/users">
            <input type="text" name="search" placeholder="Search users..." value="<?php echo e($search); ?>">
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
<?php
for ($i = 0; $i < count($users); $i++):
    $uid = (int)$users[$i]['id'];
    $uname = $users[$i]['name'];
    $uemail = $users[$i]['email'];
    $urole = $users[$i]['role'];
    $ubadges = (int)$users[$i]['badge_count'];
    $ulastlogin = $users[$i]['last_login'];
    $ucreated = $users[$i]['created_at'];
    $ucompleted = (int)$users[$i]['completed_readings'];
    $progressPercent = $totalReadings > 0 ? round(($ucompleted / $totalReadings) * 100, 1) : 0;
?>
                        <tr>
                            <td><?php echo $uid; ?></td>
                            <td><?php echo e($uname); ?></td>
                            <td><?php echo e($uemail); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $urole; ?>">
                                    <?php echo ucfirst($urole); ?>
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
                                <?php if ($ubadges > 0): ?>
                                    <span class="badge-count">&#x1F3C6; <?php echo $ubadges; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $ulastlogin ? timeAgo($ulastlogin) : 'Never'; ?></td>
                            <td><?php echo formatDate($ucreated, 'M j, Y'); ?></td>
                            <td class="actions">
                                <a href="/?route=admin/user-progress&amp;id=<?php echo $uid; ?>" class="btn btn-sm btn-primary" title="View Progress">View</a>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openEditModal(<?php echo $uid; ?>, '<?php echo e(addslashes($uname)); ?>', '<?php echo e(addslashes($uemail)); ?>', '<?php echo $urole; ?>')">Edit</button>
                                <?php if ($uid !== Auth::getUserId()): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $uid; ?>, '<?php echo e(addslashes($uname)); ?>')">Delete</button>
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
<div id="editUserModal" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h2>Edit User</h2>
            <button type="button" class="admin-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="/?route=admin/users">
            <div class="admin-modal-body">
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
                    <label for="editPassword">New Password (leave blank to keep current)</label>
                    <input type="password" id="editPassword" name="new_password" minlength="6">
                </div>

                <div class="admin-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="admin-modal">
    <div class="admin-modal-content" style="max-width:400px;">
        <div class="admin-modal-header">
            <h2>Delete User</h2>
            <button type="button" class="admin-modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" action="/?route=admin/users">
            <div class="admin-modal-body">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId">

                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger">This action cannot be undone. All reading progress will be lost.</p>

                <div class="admin-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
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
}
/* Override global .progress-text styles for admin table */
.progress-cell .progress-text {
    position: static !important;
    inset: unset !important;
    display: inline !important;
    font-size: 0.85rem;
    color: #666;
    min-width: 40px;
}
.badge-count {
    font-size: 0.9rem;
}
.text-muted {
    color: #999;
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
/* Admin Modal overrides */
.admin-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    opacity: 1 !important;
    visibility: visible !important;
}
.admin-modal.show {
    display: flex;
}
.admin-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}
.admin-modal-header {
    padding: 1rem;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.admin-modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}
.admin-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.admin-modal-body {
    padding: 1rem;
}
.admin-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;
}
</style>

<script>
function openEditModal(id, name, email, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editPassword').value = '';
    document.getElementById('editUserModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editUserModal').classList.remove('show');
}

function openDeleteModal(id, name) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').classList.remove('show');
}

// Close modals on outside click
document.getElementById('editUserModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.getElementById('deleteUserModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Users';
require TEMPLATE_PATH . '/admin/layout.php';
