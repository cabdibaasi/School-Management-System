<?php
/**
 * Offline-friendly Password Reset / Identity Verification
 */
require_once dirname(__DIR__) . '/config/config.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect('views/dashboard.php');
}

$step = 1;
$error = '';
$success = '';
$userId = null;
$usernameOrEmail = '';
$verificationPrompt = '';
$matchedPhone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $error = 'Invalid request security token.';
    } else {
        
        // STEP 1: Check Username / Email
        if ($action === 'find_account') {
            $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
            
            if (empty($usernameOrEmail)) {
                $error = 'Please enter your username or email address.';
            } else {
                $db = Database::connect();
                $stmt = $db->prepare("SELECT id, role, username, email FROM users WHERE username = :username OR email = :email LIMIT 1");
                $stmt->execute(['username' => $usernameOrEmail, 'email' => $usernameOrEmail]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $userId = $user['id'];
                    $role = $user['role'];
                    
                    // Fetch phone number depending on user type
                    if ($role === 'admin') {
                        // Admins verify the school contact phone set in global configurations
                        $matchedPhone = Setting::get('school_phone', '+1 (555) 019-2834');
                        $verificationPrompt = 'Enter the registered School Contact Phone Number';
                    } elseif ($role === 'teacher') {
                        $stmt = $db->prepare("SELECT phone FROM teachers WHERE user_id = :user_id LIMIT 1");
                        $stmt->execute(['user_id' => $userId]);
                        $t = $stmt->fetch();
                        $matchedPhone = $t ? $t['phone'] : '';
                        $verificationPrompt = 'Enter your registered Teacher Phone Number';
                    } elseif ($role === 'student') {
                        $stmt = $db->prepare("SELECT phone, parent_phone FROM students WHERE user_id = :user_id LIMIT 1");
                        $stmt->execute(['user_id' => $userId]);
                        $s = $stmt->fetch();
                        // Students can verify either student phone or parent phone
                        $matchedPhone = $s ? [$s['phone'], $s['parent_phone']] : '';
                        $verificationPrompt = 'Enter your registered Student or Parent Phone Number';
                    }
                    
                    if (empty($matchedPhone) || (is_array($matchedPhone) && empty(array_filter($matchedPhone)))) {
                        $error = 'No phone verification number is set on your profile. Please contact administration.';
                    } else {
                        // Move to verification step
                        $step = 2;
                    }
                } else {
                    $error = 'No account matches that username or email address.';
                }
            }
        }
        
        // STEP 2: Verify Phone Number
        elseif ($action === 'verify_identity') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $phoneInput = trim($_POST['phone_input'] ?? '');
            $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
            
            if (empty($phoneInput)) {
                $error = 'Please enter the verification phone number.';
                $step = 2;
            } else {
                $db = Database::connect();
                // Get role again to check phone matching
                $stmt = $db->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $role = $user['role'];
                    $isVerified = false;
                    
                    // Clean input characters (numbers only) to prevent spacing mismatch
                    $cleanInput = preg_replace('/[^0-9]/', '', $phoneInput);
                    
                    if ($role === 'admin') {
                        $adminPhone = Setting::get('school_phone', '+1 (555) 019-2834');
                        $isVerified = (preg_replace('/[^0-9]/', '', $adminPhone) === $cleanInput);
                    } elseif ($role === 'teacher') {
                        $stmt = $db->prepare("SELECT phone FROM teachers WHERE user_id = :user_id LIMIT 1");
                        $stmt->execute(['user_id' => $userId]);
                        $t = $stmt->fetch();
                        if ($t) {
                            $isVerified = (preg_replace('/[^0-9]/', '', $t['phone']) === $cleanInput);
                        }
                    } elseif ($role === 'student') {
                        $stmt = $db->prepare("SELECT phone, parent_phone FROM students WHERE user_id = :user_id LIMIT 1");
                        $stmt->execute(['user_id' => $userId]);
                        $s = $stmt->fetch();
                        if ($s) {
                            $cleanStd = preg_replace('/[^0-9]/', '', $s['phone']);
                            $cleanPrnt = preg_replace('/[^0-9]/', '', $s['parent_phone']);
                            $isVerified = ($cleanStd === $cleanInput || $cleanPrnt === $cleanInput);
                        }
                    }
                    
                    if ($isVerified) {
                        $step = 3;
                    } else {
                        $error = 'Verification failed. The phone number provided does not match our records.';
                        // Reload step 2 parameters
                        $step = 2;
                        if ($role === 'admin') {
                            $verificationPrompt = 'Enter the registered School Contact Phone Number';
                        } elseif ($role === 'teacher') {
                            $verificationPrompt = 'Enter your registered Teacher Phone Number';
                        } else {
                            $verificationPrompt = 'Enter your registered Student or Parent Phone Number';
                        }
                    }
                } else {
                    $error = 'Session expired or invalid user request.';
                }
            }
        }
        
        // STEP 3: Reset Password
        elseif ($action === 'reset_password') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword) || strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters long.';
                $step = 3;
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
                $step = 3;
            } else {
                $db = Database::connect();
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                $res = $stmt->execute(['password' => $hashed, 'id' => $userId]);
                
                if ($res) {
                    Utility::setFlash('success', 'Your password has been reset successfully. Please sign in.');
                    redirect('views/login.php');
                } else {
                    $error = 'Failed to update password. Please try again.';
                    $step = 3;
                }
            }
        }
    }
}

$csrfToken = Utility::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Dark Mode Loader -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
            document.addEventListener('DOMContentLoaded', function() {
                document.body && document.body.setAttribute('data-theme', theme);
            });
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #1e3c72;
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --secondary-color: #00b4d8;
            --light-bg: #f4f7f6;
            --card-bg: rgba(255, 255, 255, 0.96);
            --text-dark: #2d3748;
        }
        [data-theme="dark"] {
            --light-bg: #0b0f19;
            --card-bg: #151f32;
            --text-dark: #cbd5e1;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            padding: 20px;
            font-size: var(--fs-body, 0.95rem);
        }
        .reset-card {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            transition: background 0.3s, color 0.3s;
        }
        .reset-header {
            background: var(--primary-color);
            color: #fff;
            padding: 30px;
        }
        .reset-body {
            padding: 40px;
        }
        .form-control-custom {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            background-color: var(--card-bg);
            color: var(--text-dark);
        }
        .form-control-custom:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15);
        }
        .btn-primary-custom {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: #fff !important;
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        [data-theme="dark"] body {
            background: #0b0f19 !important;
        }
    </style>
</head>
<body>

<div class="reset-card">
    <div class="reset-header px-4 d-flex align-items-center justify-content-between">
        <!-- Left Side: English Name & Subtitle -->
        <div class="text-start" style="line-height: 1.25;">
            <h4 class="fw-bold mb-0 text-white" style="letter-spacing: -0.5px; font-size: 1.25rem;">Talent Institute</h4>
            <span class="text-white-50 d-block" style="font-size: 0.68rem; opacity: 0.85; font-weight: 500;">Reset Password</span>
        </div>
        
        <!-- Right Side: Arabic Name & Subtitle -->
        <div class="text-end" style="line-height: 1.25;">
            <h4 class="fw-bold mb-0 text-white" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 1.35rem; letter-spacing: -0.3px;">معـهـد تـالـنـت</h4>
            <span class="text-white-50 d-block" style="font-size: 0.68rem; opacity: 0.85; font-weight: 500;">استعادة كلمة المرور</span>
        </div>
    </div>
    <div class="reset-body">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- STEP 1: Username Check Form -->
        <?php if ($step === 1): ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="find_account">
                
                <div class="mb-4">
                    <label for="username_or_email" class="form-label fw-bold small text-muted">USERNAME OR EMAIL</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control form-control-custom border-start-0 bg-light" id="username_or_email" name="username_or_email" placeholder="Enter username or email" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-primary-custom w-100 mb-3 text-white">
                    Continue <i class="fas fa-arrow-right ms-1"></i>
                </button>
                <div class="text-center">
                    <a href="<?= BASE_URL ?>views/login.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
                </div>
            </form>
        <?php endif; ?>

        <!-- STEP 2: Phone Verification Form -->
        <?php if ($step === 2): ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="verify_identity">
                <input type="hidden" name="user_id" value="<?= $userId ?>">
                <input type="hidden" name="username_or_email" value="<?= e($usernameOrEmail) ?>">
                
                <div class="mb-4">
                    <label for="phone_input" class="form-label fw-bold small text-muted text-uppercase"><?= e($verificationPrompt) ?></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control form-control-custom border-start-0 bg-light" id="phone_input" name="phone_input" placeholder="e.g. +1 (555) 019-2834" required autofocus>
                    </div>
                    <div class="form-text mt-2 text-muted small">
                        Verify using the contact phone number registered to your profile record.
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-primary-custom w-100 mb-3 text-white">
                    Verify Profile <i class="fas fa-shield-alt ms-1"></i>
                </button>
                <div class="text-center">
                    <a href="<?= BASE_URL ?>views/login.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
                </div>
            </form>
        <?php endif; ?>

        <!-- STEP 3: New Password Form -->
        <?php if ($step === 3): ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?= $userId ?>">
                
                <div class="mb-3">
                    <label for="new_password" class="form-label fw-bold small text-muted">NEW PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control form-control-custom border-start-0 bg-light" id="new_password" name="new_password" placeholder="Min. 6 characters" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label fw-bold small text-muted">CONFIRM NEW PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-check-double"></i></span>
                        <input type="password" class="form-control form-control-custom border-start-0 bg-light" id="confirm_password" name="confirm_password" placeholder="Retype new password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-primary-custom w-100 mb-3 text-white">
                    Update Password <i class="fas fa-save ms-1"></i>
                </button>
            </form>
        <?php endif; ?>

    </div>
</div>

<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
