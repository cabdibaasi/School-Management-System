<?php
/**
 * User Profile and Security Settings Page
 */
$pageTitle = "My Profile";
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . 'views/layouts/header.php';

$db = Database::connect();
$userId = Auth::id();
$userRole = Auth::role();
$profileId = Auth::profileId();

$msgSuccess = '';
$msgError = '';

// Load user specific details from DB based on role
$userObj = null;
if ($userRole === 'admin') {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $userObj = $stmt->fetch();
} elseif ($userRole === 'teacher') {
    $stmt = $db->prepare("SELECT u.email, u.username, t.* FROM users u JOIN teachers t ON u.id = t.user_id WHERE u.id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $userObj = $stmt->fetch();
} elseif ($userRole === 'student') {
    $stmt = $db->prepare("SELECT u.email, u.username, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $userObj = $stmt->fetch();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        if ($action === 'update_profile') {
            // Update Phone, Address, Email
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            
            $validator = new Validation();
            $validator->required(['email' => 'Email'], $_POST);
            $validator->email('email', $email);
            if ($userRole !== 'admin') {
                $validator->phone('phone', $phone);
            }
            
            if ($validator->passes()) {
                try {
                    $db->beginTransaction();
                    
                    // Update email in users table (check uniqueness first)
                    $checkEmail = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                    $checkEmail->execute(['email' => $email, 'id' => $userId]);
                    if ($checkEmail->fetch()) {
                        $msgError = 'Email is already taken by another user.';
                        $db->rollBack();
                    } else {
                        $stmtUpdateUser = $db->prepare("UPDATE users SET email = :email WHERE id = :id");
                        $stmtUpdateUser->execute(['email' => $email, 'id' => $userId]);
                        
                        if ($userRole === 'teacher') {
                            $stmtUpdateProfile = $db->prepare("UPDATE teachers SET phone = :phone, address = :address, email = :email WHERE id = :id");
                            $stmtUpdateProfile->execute([
                                'phone' => $phone,
                                'address' => $address,
                                'email' => $email,
                                'id' => $profileId
                            ]);
                        } elseif ($userRole === 'student') {
                            $stmtUpdateProfile = $db->prepare("UPDATE students SET phone = :phone, address = :address, email = :email WHERE id = :id");
                            $stmtUpdateProfile->execute([
                                'phone' => $phone,
                                'address' => $address,
                                'email' => $email,
                                'id' => $profileId
                            ]);
                        }
                        
                        $db->commit();
                        Utility::setFlash('success', 'Profile updated successfully.');
                        redirect('views/profile.php');
                    }
                } catch (\Exception $e) {
                    $db->rollBack();
                    $msgError = 'Database error: ' . $e->getMessage();
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'update_password') {
            // Update password
            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $validator = new Validation();
            $validator->required([
                'old_password' => 'Current Password', 
                'new_password' => 'New Password', 
                'confirm_password' => 'Confirm Password'
            ], $_POST);
            
            if ($validator->passes()) {
                // Verify old password
                $stmt = $db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $userId]);
                $currPass = $stmt->fetchColumn();
                
                if (!password_verify($oldPassword, $currPass)) {
                    $msgError = 'Current password is incorrect.';
                } elseif ($newPassword !== $confirmPassword) {
                    $msgError = 'New password and confirmation do not match.';
                } elseif (strlen($newPassword) < 6) {
                    $msgError = 'New password must be at least 6 characters long.';
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmtUpdate = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
                    if ($stmtUpdate->execute(['pass' => $hashed, 'id' => $userId])) {
                        Utility::setFlash('success', 'Password updated successfully.');
                        redirect('views/profile.php');
                    } else {
                        $msgError = 'Failed to update password.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'upload_photo') {
            // Update Profile Photo
            if ($userRole === 'admin') {
                $msgError = 'Administrators do not require profile picture uploads.';
            } else {
                $fileResult = Utility::uploadImage($_FILES['photo'], 'profiles');
                if ($fileResult['success']) {
                    $newFilename = $fileResult['filename'];
                    try {
                        // Get old filename to delete
                        $oldPhoto = $userObj['photo'] ?? '';
                        
                        if ($userRole === 'teacher') {
                            $stmt = $db->prepare("UPDATE teachers SET photo = :photo WHERE id = :id");
                            $stmt->execute(['photo' => $newFilename, 'id' => $profileId]);
                        } elseif ($userRole === 'student') {
                            $stmt = $db->prepare("UPDATE students SET photo = :photo WHERE id = :id");
                            $stmt->execute(['photo' => $newFilename, 'id' => $profileId]);
                        }
                        
                        // Delete old file
                        if (!empty($oldPhoto) && file_exists(UPLOAD_PATH . 'profiles/' . $oldPhoto)) {
                            unlink(UPLOAD_PATH . 'profiles/' . $oldPhoto);
                        }
                        
                        // Update session photo
                        $_SESSION['photo'] = $newFilename;
                        
                        Utility::setFlash('success', 'Profile photo updated successfully.');
                        redirect('views/profile.php');
                    } catch (\Exception $e) {
                        $msgError = 'Database error: ' . $e->getMessage();
                    }
                } else {
                    $msgError = $fileResult['error'];
                }
            }
        }
    }
}

// Recalculate avatar source path
$photoPath = BASE_URL . 'assets/uploads/profiles/' . ($userObj['photo'] ?? '');
if (empty($userObj['photo']) || !file_exists(UPLOAD_PATH . 'profiles/' . $userObj['photo'])) {
    $photoPath = BASE_URL . 'assets/img/default-avatar.png';
}

$csrfToken = Utility::generateCSRFToken();
?>

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-id-card me-2"></i>My Profile Settings</h2>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Left Column: User Card & Photo Upload -->
    <div class="col-lg-4 mb-4">
        <div class="card metric-card h-100 text-center p-4">
            <div class="card-body">
                <div class="profile-pic-wrapper mb-3">
                    <img src="<?= $photoPath ?>" alt="Profile Picture" class="profile-pic" id="profileImagePreview">
                </div>
                
                <h4 class="fw-bold mb-1"><?= e(Auth::displayName()) ?></h4>
                <p class="text-muted text-uppercase fw-semibold mb-3" style="font-size:0.8rem; letter-spacing:0.5px;"><?= e(Auth::role()) ?></p>
                
                <?php if ($userRole !== 'admin'): ?>
                    <hr>
                    <form action="<?= BASE_URL ?>views/profile.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="upload_photo">
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label text-muted small">Update Profile Picture (Max 2MB)</label>
                            <input class="form-control form-control-sm" type="file" id="photo" name="photo" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100 rounded-3">
                            <i class="fas fa-upload me-2"></i> Upload Photo
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Details & Password updates -->
    <div class="col-lg-8">
        <!-- Tab System for separating Edit details vs Password settings -->
        <div class="card section-card">
            <ul class="nav nav-pills mb-4" id="profileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                        <i class="fas fa-user me-2"></i>Personal Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                        <i class="fas fa-shield-alt me-2"></i>Security & Password
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabContent">
                <!-- Personal Details Tab -->
                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <form action="<?= BASE_URL ?>views/profile.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">USERNAME</label>
                                <input type="text" class="form-control form-control-custom bg-light" value="<?= e($userObj['username']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                                <input type="email" class="form-control form-control-custom" name="email" value="<?= e($userObj['email']) ?>" required>
                            </div>
                            
                            <?php if ($userRole !== 'admin'): ?>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">PHONE NUMBER</label>
                                    <input type="text" class="form-control form-control-custom" name="phone" value="<?= e($userObj['phone']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">GENDER</label>
                                    <input type="text" class="form-control form-control-custom bg-light" value="<?= e(ucfirst($userObj['gender'])) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted small fw-bold">RESIDENTIAL ADDRESS</label>
                                    <textarea class="form-control form-control-custom" name="address" rows="3"><?= e($userObj['address']) ?></textarea>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($userRole === 'student'): ?>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">ADMISSION NUMBER</label>
                                    <input type="text" class="form-control form-control-custom bg-light" value="<?= e($userObj['admission_number']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">STUDENT CARD ID</label>
                                    <input type="text" class="form-control form-control-custom bg-light" value="<?= e($userObj['student_id_card']) ?>" readonly>
                                </div>
                            <?php elseif ($userRole === 'teacher'): ?>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">EMPLOYEE ID</label>
                                    <input type="text" class="form-control form-control-custom bg-light" value="<?= e($userObj['employee_id']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">QUALIFICATIONS</label>
                                    <input type="text" class="form-control form-control-custom bg-light" value="<?= e($userObj['qualification']) ?>" readonly>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary btn-primary-custom text-white">
                                <i class="fas fa-save me-2"></i> SAVE PROFILE CHANGES
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security & Password Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                    <form action="<?= BASE_URL ?>views/profile.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="update_password">
                        
                        <!-- Old Password -->
                        <div class="mb-3">
                            <label for="old_password" class="form-label text-muted small fw-bold">CURRENT PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-light"><i class="fas fa-lock-open text-muted"></i></span>
                                <input type="password" class="form-control form-control-custom border-start-0 border-end-0 bg-light" id="old_password" name="old_password" required>
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="old_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label text-muted small fw-bold">NEW PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control form-control-custom border-start-0 border-end-0 bg-light" id="new_password" name="new_password" required>
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">Minimum 6 characters required.</small>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label text-muted small fw-bold">CONFIRM NEW PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-light"><i class="fas fa-check-double text-muted"></i></span>
                                <input type="password" class="form-control form-control-custom border-start-0 border-end-0 bg-light" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-warning px-4 py-2 rounded-3 fw-bold">
                                <i class="fas fa-key me-2"></i> UPDATE PASSWORD
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle hash loading on page loading to auto switch tab to security
window.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#security') {
        const securityTab = document.querySelector('#security-tab');
        if (securityTab) {
            bootstrap.Tab.getOrCreateInstance(securityTab).show();
        }
    }
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
