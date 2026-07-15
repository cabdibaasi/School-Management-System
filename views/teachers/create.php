<?php
/**
 * Register New Teacher Profile
 */
$pageTitle = "Register Teacher";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        // Collect inputs
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'active';

        $employeeId = trim($_POST['employee_id'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $teacherEmail = trim($_POST['teacher_email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $salary = (float)($_POST['salary'] ?? 0.00);
        $dateJoined = $_POST['date_joined'] ?? '';

        // Form Validation
        $validator = new Validation();
        $validator->required([
            'username' => 'Portal Username',
            'email' => 'Portal Email',
            'password' => 'Portal Password',
            'employee_id' => 'Employee ID',
            'full_name' => 'Full Name',
            'gender' => 'Gender',
            'qualification' => 'Qualification',
            'date_joined' => 'Date Joined'
        ], $_POST);

        $validator->email('email', $email);
        if (!empty($teacherEmail)) $validator->email('teacher_email', $teacherEmail);
        if (!empty($phone)) $validator->phone('phone', $phone);
        $validator->numeric('salary', $salary);

        if ($validator->passes()) {
            // Check uniqueness
            $checkUser = $db->prepare("SELECT id FROM users WHERE username = :uname OR email = :email");
            $checkUser->execute(['uname' => $username, 'email' => $email]);
            
            $checkTch = $db->prepare("SELECT id FROM teachers WHERE employee_id = :empid");
            $checkTch->execute(['empid' => $employeeId]);

            if ($checkUser->fetch()) {
                $msgError = 'The login username or email is already taken.';
            } elseif ($checkTch->fetch()) {
                $msgError = 'Employee ID is already in use by another teacher.';
            } else {
                // Upload Photo
                $photoName = '';
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadRes = Utility::uploadImage($_FILES['photo'], 'profiles');
                    if ($uploadRes['success']) {
                        $photoName = $uploadRes['filename'];
                    } else {
                        $msgError = $uploadRes['error'];
                    }
                }

                if (empty($msgError)) {
                    $userData = [
                        'username' => $username,
                        'email' => $email,
                        'password' => $password,
                        'status' => $status
                    ];

                    $teacherData = [
                        'employee_id' => $employeeId,
                        'full_name' => $fullName,
                        'gender' => $gender,
                        'phone' => $phone,
                        'email' => $teacherEmail ?: $email,
                        'address' => $address,
                        'qualification' => $qualification,
                        'salary' => $salary,
                        'date_joined' => $dateJoined,
                        'status' => $status,
                        'photo' => $photoName
                    ];

                    try {
                        if (Teacher::create($userData, $teacherData)) {
                            Utility::setFlash('success', 'Teacher registered successfully.');
                            redirect('views/teachers/index.php');
                        } else {
                            $msgError = 'Failed to create teacher account.';
                        }
                    } catch (\Exception $ex) {
                        $msgError = 'Transaction error: ' . $ex->getMessage();
                    }
                }
            }
        } else {
            $errors = $validator->getErrors();
            $msgError = reset($errors);
        }
    }
}

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/teachers/index.php">Teachers</a></li>
        <li class="breadcrumb-item active" aria-current="page">Register Teacher</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-user-plus me-2"></i>Register New Teacher</h2>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>views/teachers/create.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="row">
        <!-- Credentials Card -->
        <div class="col-lg-4 mb-4">
            <div class="card section-card h-100">
                <h5 class="fw-bold text-primary mb-3"><i class="fas fa-user-shield me-2"></i>Login Credentials</h5>
                
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold small text-muted">PORTAL USERNAME</label>
                    <input type="text" class="form-control form-control-custom" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="e.g. jsmith" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-muted">PORTAL EMAIL</label>
                    <input type="email" class="form-control form-control-custom" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="e.g. smith@school.com" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small text-muted">PORTAL PASSWORD</label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-custom border-end-0 bg-light" id="password" name="password" placeholder="Min 6 chars" required>
                        <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password-btn" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label fw-semibold small text-muted">ACCOUNT STATUS</label>
                    <select class="form-select form-control-custom" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <label for="photo" class="form-label fw-semibold small text-muted">TEACHER PHOTO (MAX 2MB)</label>
                    <input class="form-control form-control-sm" type="file" id="photo" name="photo" accept="image/*">
                </div>
            </div>
        </div>

        <!-- Personal / Professional Details Card -->
        <div class="col-lg-8 mb-4">
            <div class="card section-card">
                <h5 class="fw-bold text-primary mb-3"><i class="fas fa-id-card me-2"></i>Personal & Professional Information</h5>
                
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="full_name" class="form-label fw-semibold small text-muted">FULL NAME</label>
                        <input type="text" class="form-control form-control-custom" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="e.g. Prof. John Smith" required>
                    </div>
                    <div class="col-md-4">
                        <label for="gender" class="form-label fw-semibold small text-muted">GENDER</label>
                        <select class="form-select form-control-custom" id="gender" name="gender" required>
                            <option value="">-- Select --</option>
                            <option value="male" <?= (($_POST['gender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= (($_POST['gender'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="employee_id" class="form-label fw-semibold small text-muted">EMPLOYEE ID / NUMBER</label>
                        <input type="text" class="form-control form-control-custom font-monospace" id="employee_id" name="employee_id" value="<?= e($_POST['employee_id'] ?? 'TCH-' . rand(1000, 9999)) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="qualification" class="form-label fw-semibold small text-muted">PROFESSIONAL QUALIFICATION</label>
                        <input type="text" class="form-control form-control-custom" id="qualification" name="qualification" value="<?= e($_POST['qualification'] ?? '') ?>" placeholder="e.g. M.Sc. in Mathematics, PhD" required>
                    </div>

                    <div class="col-md-6">
                        <label for="salary" class="form-label fw-semibold small text-muted">MONTHLY SALARY (<?= Setting::get('currency','USD') ?>)</label>
                        <input type="number" step="0.01" class="form-control form-control-custom" id="salary" name="salary" value="<?= e($_POST['salary'] ?? '3500.00') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="date_joined" class="form-label fw-semibold small text-muted">DATE JOINED</label>
                        <input type="date" class="form-control form-control-custom" id="date_joined" name="date_joined" value="<?= e($_POST['date_joined'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold small text-muted">CONTACT PHONE</label>
                        <input type="text" class="form-control form-control-custom" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="teacher_email" class="form-label fw-semibold small text-muted">CONTACT EMAIL (IF DIFFERENT)</label>
                        <input type="email" class="form-control form-control-custom" id="teacher_email" name="teacher_email" value="<?= e($_POST['teacher_email'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label fw-semibold small text-muted">RESIDENTIAL ADDRESS</label>
                        <textarea class="form-control form-control-custom" id="address" name="address" rows="3"><?= e($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="<?= BASE_URL ?>views/teachers/index.php" class="btn btn-secondary px-4 rounded-3 me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-primary-custom text-white px-5">
                        <i class="fas fa-save me-2"></i> Register Teacher
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
