<?php
/**
 * Warehouse Management System - User Management
 * MODULE 05 — USER MANAGEMENT
 */

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$pageTitle = 'User Management';
$activeNav = 'users';

$currentUser = getCurrentUser();
$errors = [];

// -------------------------------------------------------------
// POST ACTIONS (ADD, EDIT, RESET PASSWORD, DELETE)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (CSRF mismatch). Please try again.');
        redirect('/admin/users.php');
    }

    $action = sanitize($_POST['form_action'] ?? '');

    // 1. ADD USER
    if ($action === 'add') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $username = strtolower(sanitize($_POST['username'] ?? ''));
        $role = strtolower(sanitize($_POST['role'] ?? 'user'));
        $password = (string)($_POST['password'] ?? '');

        // Validation
        if (empty($fullName)) $errors[] = 'Full Name is required.';
        if (empty($username)) $errors[] = 'Username is required.';
        if (!in_array($role, ['admin', 'user'], true)) $errors[] = 'Invalid role selected.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        // Check Username Uniqueness
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetch()) {
                $errors[] = "Username '{$username}' is already in use. Please select a different username.";
            }
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (full_name, username, password_hash, role, is_active)
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmtInsert->execute([$fullName, $username, $hash, $role]);
                setFlash('success', "User '{$username}' ({$fullName}) created successfully.");
                redirect('/admin/users.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to create user: ' . $e->getMessage();
            }
        }
    }

    // 2. EDIT USER
    if ($action === 'edit') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $fullName = sanitize($_POST['full_name'] ?? '');
        $username = strtolower(sanitize($_POST['username'] ?? ''));
        $role = strtolower(sanitize($_POST['role'] ?? 'user'));
        $password = (string)($_POST['password'] ?? '');

        if ($userId <= 0) $errors[] = 'Invalid user ID.';
        if (empty($fullName)) $errors[] = 'Full Name is required.';
        if (empty($username)) $errors[] = 'Username is required.';
        if (!in_array($role, ['admin', 'user'], true)) $errors[] = 'Invalid role.';

        // Prevent admin from removing their own admin role
        if ($userId === $currentUser['id'] && $role !== 'admin') {
            $errors[] = 'You cannot demote your own account from the Administrator role.';
        }

        // Check Username Uniqueness (excluding self)
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $stmtCheck->execute([$username, $userId]);
            if ($stmtCheck->fetch()) {
                $errors[] = "Username '{$username}' is already taken by another account.";
            }
        }

        if (empty($errors)) {
            try {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $errors[] = 'New password must be at least 6 characters.';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmtUpdate = $pdo->prepare("
                            UPDATE users 
                            SET full_name = ?, username = ?, role = ?, password_hash = ?
                            WHERE id = ?
                        ");
                        $stmtUpdate->execute([$fullName, $username, $role, $hash, $userId]);
                    }
                } else {
                    // Password left blank -> keep existing password
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users 
                        SET full_name = ?, username = ?, role = ?
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$fullName, $username, $role, $userId]);
                }

                if (empty($errors)) {
                    // Update session name if editing self
                    if ($userId === $currentUser['id']) {
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['username'] = $username;
                    }
                    setFlash('success', "User '{$username}' updated successfully.");
                    redirect('/admin/users.php');
                }
            } catch (PDOException $e) {
                $errors[] = 'Failed to update user: ' . $e->getMessage();
            }
        }
    }

    // 3. RESET PASSWORD
    if ($action === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPassword = (string)($_POST['new_password'] ?? '');

        if ($userId <= 0) $errors[] = 'Invalid user ID.';
        if (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters long.';

        if (empty($errors)) {
            try {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmtReset = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmtReset->execute([$hash, $userId]);
                setFlash('success', 'Password reset successfully.');
                redirect('/admin/users.php');
            } catch (PDOException $e) {
                $errors[] = 'Failed to reset password: ' . $e->getMessage();
            }
        }
    }

    // 4. DELETE USER
    if ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);

        // Security requirement: Prevent Admin from deleting their own currently logged-in account
        if ($userId === $currentUser['id']) {
            setFlash('danger', 'Operation Denied: You cannot delete your own currently logged-in administrator account.');
            redirect('/admin/users.php');
        }

        // Prevent deletion if associated with stock movement records
        $stmtSmCheck = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE created_by = ?");
        $stmtSmCheck->execute([$userId]);
        $hasMovements = (int)$stmtSmCheck->fetchColumn();

        if ($hasMovements > 0) {
            setFlash('danger', 'Cannot delete this user because they are associated with existing stock movement transaction records.');
            redirect('/admin/users.php');
        } else {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmtDel->execute([$userId]);
                setFlash('success', 'User deleted successfully.');
                redirect('/admin/users.php');
            } catch (PDOException $e) {
                setFlash('danger', 'Failed to delete user: ' . $e->getMessage());
                redirect('/admin/users.php');
            }
        }
    }
}

// Retrieve All Users
$stmtUsers = $pdo->query("SELECT id, full_name, username, role, is_active, created_at, updated_at FROM users ORDER BY id ASC");
$users = $stmtUsers->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1>User Management</h1>
        <div class="page-subtitle">Manage system users, access roles, and authentication credentials</div>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" data-modal-target="addUserModal">
            <span>&plus;</span> Add New User
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div class="alert-content">
            <ul style="margin-left: 18px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Users Listing Table -->
<div class="card-panel">
    <div class="card-header">
        <div class="card-title">
            <span>&#128101;</span> System Users (<?= count($users); ?> Accounts)
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th class="text-center" style="width: 250px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= (int)$u['id']; ?></td>
                            <td>
                                <strong><?= e($u['full_name']); ?></strong>
                                <?php if ((int)$u['id'] === $currentUser['id']): ?>
                                    <span class="badge badge-info" style="margin-left: 6px;">(You)</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($u['username']); ?></code></td>
                            <td>
                                <span class="badge user-role-badge <?= e($u['role']); ?>">
                                    <?= e(strtoupper($u['role'])); ?>
                                </span>
                            </td>
                            <td style="font-size: 0.82rem; color: #64748b;"><?= formatDateTime($u['created_at']); ?></td>
                            <td style="font-size: 0.82rem; color: #64748b;"><?= formatDateTime($u['updated_at']); ?></td>
                            <td class="text-center">
                                <div class="action-buttons" style="justify-content: center;">
                                    <!-- Edit Action -->
                                    <button type="button" 
                                            class="btn btn-warning btn-sm" 
                                            title="Edit User"
                                            onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>)">
                                        Edit
                                    </button>

                                    <!-- Reset Password Action -->
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm" 
                                            title="Reset Password"
                                            onclick="openResetPasswordModal(<?= (int)$u['id']; ?>, '<?= e(addslashes($u['username'])); ?>')">
                                        Reset Password
                                    </button>

                                    <!-- Delete Action (Disabled for own logged-in account) -->
                                    <?php if ((int)$u['id'] === $currentUser['id']): ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                disabled 
                                                title="You cannot delete your own logged-in account">
                                            Delete
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="<?= url('/admin/users.php'); ?>" style="display: inline;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-danger btn-sm" 
                                                    data-confirm="Are you sure you want to delete user '<?= e($u['username']); ?>'? This action cannot be undone."
                                                    title="Delete User">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL 1: ADD USER
     ========================================================= -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">&plus; Create New System User</h3>
            <button type="button" class="modal-close-btn">&times;</button>
        </div>
        <form method="POST" action="<?= url('/admin/users.php'); ?>">
            <?= csrfField(); ?>
            <input type="hidden" name="form_action" value="add">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="addFullName">Full Name <span class="required">*</span></label>
                    <input type="text" id="addFullName" name="full_name" class="form-control" required placeholder="e.g. John Doe">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="addUsername">Username <span class="required">*</span></label>
                        <input type="text" id="addUsername" name="username" class="form-control" required placeholder="e.g. jdoe">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="addRole">Role <span class="required">*</span></label>
                        <select id="addRole" name="role" class="form-control" required>
                            <option value="user" selected>User (Read-only)</option>
                            <option value="admin">Admin (Full Control)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addPassword">Password <span class="required">*</span></label>
                    <input type="password" id="addPassword" name="password" class="form-control" required minlength="6" placeholder="Min. 6 characters">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     MODAL 2: EDIT USER
     ========================================================= -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">&#9998; Edit System User</h3>
            <button type="button" class="modal-close-btn">&times;</button>
        </div>
        <form method="POST" action="<?= url('/admin/users.php'); ?>">
            <?= csrfField(); ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" id="editUserId" name="user_id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="editFullName">Full Name <span class="required">*</span></label>
                    <input type="text" id="editFullName" name="full_name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="editUsername">Username <span class="required">*</span></label>
                        <input type="text" id="editUsername" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="editRole">Role <span class="required">*</span></label>
                        <select id="editRole" name="role" class="form-control" required>
                            <option value="user">User (Read-only)</option>
                            <option value="admin">Admin (Full Control)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editPassword">Change Password</label>
                    <input type="password" id="editPassword" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current password">
                    <div class="form-help">Leave blank to retain the user's existing password.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     MODAL 3: RESET PASSWORD
     ========================================================= -->
<div class="modal-overlay" id="resetPasswordModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">&#128273; Reset User Password</h3>
            <button type="button" class="modal-close-btn">&times;</button>
        </div>
        <form method="POST" action="<?= url('/admin/users.php'); ?>">
            <?= csrfField(); ?>
            <input type="hidden" name="form_action" value="reset_password">
            <input type="hidden" id="resetUserId" name="user_id" value="">

            <div class="modal-body">
                <p style="margin-bottom: 16px;">
                    Resetting password for user account: <strong id="resetTargetUsername" style="color: #1b4332;"></strong>
                </p>

                <div class="form-group">
                    <label class="form-label" for="resetNewPassword">New Password <span class="required">*</span></label>
                    <input type="password" 
                           id="resetNewPassword" 
                           name="new_password" 
                           class="form-control" 
                           required 
                           minlength="6" 
                           placeholder="Enter new password (min. 6 chars)">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editPassword').value = '';
    openModal('editUserModal');
}

function openResetPasswordModal(userId, username) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetTargetUsername').textContent = username;
    document.getElementById('resetNewPassword').value = '';
    openModal('resetPasswordModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
