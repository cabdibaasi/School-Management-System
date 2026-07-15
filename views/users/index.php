<?php
/**
 * User Management Administration Page
 */
$pageTitle = "Manage User Accounts";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Check - Only Admins can manage accounts
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$msgSuccess = '';
$currentUserId = Auth::id();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        if ($action === 'create_admin') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $validator = new Validation();
            $validator->required([
                'username' => 'Username',
                'email' => 'Email',
                'password' => 'Password'
            ], $_POST);
            $validator->email('email', $email);
            
            if ($validator->passes()) {
                // Check username/email uniqueness
                $check = $db->prepare("SELECT id FROM users WHERE username = :uname OR email = :email");
                $check->execute(['uname' => $username, 'email' => $email]);
                if ($check->fetch()) {
                    $msgError = 'Username or Email is already taken.';
                } else {
                    if (User::create($username, $email, $password, 'admin', 'active')) {
                        Utility::setFlash('success', 'Admin user created successfully.');
                        redirect('views/users/index.php');
                    } else {
                        $msgError = 'Failed to create user account.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'update_user') {
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';
            
            $validator = new Validation();
            $validator->required([
                'username' => 'Username',
                'email' => 'Email',
                'role' => 'Role',
                'status' => 'Status'
            ], $_POST);
            $validator->email('email', $email);
            
            if ($validator->passes()) {
                // Don't allow changing role/status of oneself to avoid locking oneself out
                if ($id === $currentUserId && ($role !== 'admin' || $status !== 'active')) {
                    $msgError = 'You cannot demote or deactivate your own account.';
                } else {
                    // Check uniqueness excluding current ID
                    $check = $db->prepare("SELECT id FROM users WHERE (username = :uname OR email = :email) AND id != :id");
                    $check->execute(['uname' => $username, 'email' => $email, 'id' => $id]);
                    if ($check->fetch()) {
                        $msgError = 'Username or Email is already taken.';
                    } else {
                        if (User::update($id, $username, $email, $role, $status)) {
                            Utility::setFlash('success', 'User account updated successfully.');
                            redirect('views/users/index.php');
                        } else {
                            $msgError = 'Failed to update user account.';
                        }
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'reset_password') {
            $id = (int)($_POST['id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword) || strlen($newPassword) < 6) {
                $msgError = 'Password must be at least 6 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $msgError = 'Passwords do not match.';
            } else {
                if (User::resetPassword($id, $newPassword)) {
                    Utility::setFlash('success', 'User password reset successfully.');
                    redirect('views/users/index.php');
                } else {
                    $msgError = 'Failed to reset password.';
                }
            }
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === $currentUserId) {
                $msgError = 'You cannot delete your own logged-in account.';
            } else {
                if (User::delete($id)) {
                    Utility::setFlash('success', 'User account deleted successfully.');
                    redirect('views/users/index.php');
                } else {
                    $msgError = 'Failed to delete user account.';
                }
            }
        }
    }
}

$usersList = User::getAll();
$csrfToken = Utility::generateCSRFToken();

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Users</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-users-cog me-2"></i>User Account Management</h2>
    <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#createAdminModal">
        <i class="fas fa-plus me-2"></i> Add Admin
    </button>
</div>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="alert alert-info d-flex align-items-center border-0 rounded-3 mb-4" role="alert">
    <i class="fas fa-info-circle fs-4 me-3"></i>
    <div>
        <strong>Tip:</strong> Student and Teacher user accounts are automatically created when you add a Student or Teacher profile under their respective management dashboards. Use this page for quick credentials updates, activations, or adding extra system administrators.
    </div>
</div>

<!-- Users Table Card -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $usr): 
                    $roleBadge = 'bg-primary';
                    if ($usr['role'] == 'teacher') $roleBadge = 'bg-cyan-light text-dark';
                    if ($usr['role'] == 'student') $roleBadge = 'bg-purple-light text-dark';
                    
                    $statusBadge = ($usr['status'] == 'active') ? 'bg-success' : 'bg-danger';
                    ?>
                    <tr>
                        <td>
                            <strong class="text-dark"><?= e($usr['username']) ?></strong>
                            <?php if ($usr['id'] === $currentUserId): ?>
                                <span class="badge bg-secondary ms-1">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($usr['email']) ?></td>
                        <td><span class="badge <?= $roleBadge ?> px-3 py-2 rounded-pill text-uppercase" style="font-size:0.75rem; font-weight:600;"><?= e($usr['role']) ?></span></td>
                        <td><span class="badge <?= $statusBadge ?> px-3 py-1 rounded-pill"><?= ucfirst($usr['status']) ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning rounded-3 me-1 edit-user-btn"
                                    data-id="<?= $usr['id'] ?>"
                                    data-username="<?= e($usr['username']) ?>"
                                    data-email="<?= e($usr['email']) ?>"
                                    data-role="<?= e($usr['role']) ?>"
                                    data-status="<?= e($usr['status']) ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-info rounded-3 me-1 reset-pass-btn"
                                    data-id="<?= $usr['id'] ?>"
                                    data-username="<?= e($usr['username']) ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#resetPasswordModal">
                                <i class="fas fa-key"></i> Pass
                            </button>
                            <?php if ($usr['id'] !== $currentUserId): ?>
                                <button class="btn btn-sm btn-outline-danger rounded-3 delete-user-btn"
                                        data-id="<?= $usr['id'] ?>"
                                        data-username="<?= e($usr['username']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteUserModal">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CREATE ADMIN MODAL -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/users/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create_admin">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-primary" id="createAdminModalLabel"><i class="fas fa-user-plus me-2"></i>Create Admin Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold small text-muted">USERNAME</label>
                        <input type="text" class="form-control form-control-custom" id="username" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small text-muted">EMAIL ADDRESS</label>
                        <input type="email" class="form-control form-control-custom" id="email" name="email" placeholder="Enter email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small text-muted">PASSWORD</label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-custom border-end-0 bg-light" id="password" name="password" placeholder="Enter password" required>
                            <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 text-white">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/users/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-warning" id="editUserModalLabel"><i class="fas fa-edit me-2"></i>Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label fw-semibold small text-muted">USERNAME</label>
                        <input type="text" class="form-control form-control-custom" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label fw-semibold small text-muted">EMAIL ADDRESS</label>
                        <input type="email" class="form-control form-control-custom" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label fw-semibold small text-muted">ROLE</label>
                        <select class="form-select form-control-custom" id="edit_role" name="role" required>
                            <option value="admin">Administrator</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label fw-semibold small text-muted">STATUS</label>
                        <select class="form-select form-control-custom" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 rounded-3 text-dark fw-bold">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- RESET PASSWORD MODAL -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/users/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" id="reset_id" name="id">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-info" id="resetPasswordModalLabel"><i class="fas fa-key me-2"></i>Reset User Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">Setting new password for: <strong id="reset_user_display" class="text-primary"></strong></p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold small text-muted">NEW PASSWORD</label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-custom border-end-0 bg-light" id="new_password" name="new_password" placeholder="Minimum 6 characters" required>
                            <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-semibold small text-muted">CONFIRM NEW PASSWORD</label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-custom border-end-0 bg-light" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info px-4 rounded-3 text-white">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE USER MODAL -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/users/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteUserModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete User Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted">You are about to delete user account <strong id="delete_user_display" class="text-danger"></strong>.</p>
                    <p class="text-muted small">Warning: Deleting the credential account will ALSO delete the linked student/teacher profile records due to cascade referential integrity!</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Edit details
    const editBtns = document.querySelectorAll(".edit-user-btn");
    editBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("edit_id").value = this.getAttribute("data-id");
            document.getElementById("edit_username").value = this.getAttribute("data-username");
            document.getElementById("edit_email").value = this.getAttribute("data-email");
            document.getElementById("edit_role").value = this.getAttribute("data-role");
            document.getElementById("edit_status").value = this.getAttribute("data-status");
        });
    });

    // Reset password mapping
    const resetBtns = document.querySelectorAll(".reset-pass-btn");
    resetBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("reset_id").value = this.getAttribute("data-id");
            document.getElementById("reset_user_display").innerText = this.getAttribute("data-username");
        });
    });

    // Delete mapping
    const deleteBtns = document.querySelectorAll(".delete-user-btn");
    deleteBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_user_display").innerText = this.getAttribute("data-username");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
