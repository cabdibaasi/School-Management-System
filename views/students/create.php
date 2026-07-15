<?php
/**
 * Enroll New Student Page - Custom Simplified Registry Layout
 */
$pageTitle = "Enroll Student";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard - Admin only
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$classes = SchoolClass::getAll();

// Get the next sequential Admission ID from database
$stmtMax = $db->query("SELECT MAX(CAST(admission_number AS UNSIGNED)) FROM students");
$maxVal = (int)$stmtMax->fetchColumn();
$defaultId = $maxVal > 0 ? $maxVal + 1 : 1001;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        // Collect custom fields matching user's spreadsheet template
        $admNum       = trim($_POST['id'] ?? '');
        $fullName     = trim($_POST['name_of_students'] ?? '');
        $parentName   = trim($_POST['parent_name'] ?? '');
        $parentPhone  = trim($_POST['parent_phone'] ?? '');
        $classId      = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
        $courseType   = trim($_POST['course_type'] ?? '');
        $feeAmount    = (float)($_POST['fee'] ?? 0);
        $dateRegister = trim($_POST['date_register'] ?? '');
        $dateOut      = trim($_POST['date_out'] ?? '');
        $statusVal    = trim($_POST['status'] ?? 'Present');
        
        // Basic Validations
        $validator = new Validation();
        $validator->required([
            'id' => 'ID (Admission Number)',
            'name_of_students' => 'Name of Students',
            'parent_name' => 'Parent\'s Name',
            'parent_phone' => 'Parent Number',
            'class_id' => 'Class',
            'date_register' => 'Date Register'
        ], $_POST);
        
        if ($validator->passes()) {
            // Check uniqueness in user and student details
            $checkUser = $db->prepare("SELECT id FROM users WHERE username = :uname");
            $checkUser->execute(['uname' => $admNum]);
            
            $checkStd = $db->prepare("SELECT id FROM students WHERE admission_number = :adm");
            $checkStd->execute(['adm' => $admNum]);
            
            if ($checkUser->fetch()) {
                $msgError = "The login username (Student ID) '{$admNum}' is already taken.";
            } elseif ($checkStd->fetch()) {
                $msgError = "The Student ID '{$admNum}' is already in use.";
            } else {
                
                // Split name
                $parts = explode(' ', $fullName);
                $firstName = $parts[0] ?? '';
                $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '.';
                
                $status = ($statusVal === 'Present') ? 'active' : 'inactive';
                $acadYear = date('Y') . '-' . (date('Y') + 1);
                
                // Prepare database structures
                $userData = [
                    'username' => $admNum,
                    'email' => $admNum . '@school.com',
                    'password' => password_hash($admNum, PASSWORD_BCRYPT), // default password is ID
                    'status' => $status
                ];
                
                $studentData = [
                    'admission_number' => $admNum,
                    'student_id_card' => $admNum,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => 'male', // default placeholder
                    'date_of_birth' => !empty($dateRegister) ? $dateRegister : null, // save date register to DOB
                    'nationality' => $courseType, // save Course Type to nationality
                    'blood_group' => null,
                    'phone' => $parentPhone,
                    'email' => $admNum . '@school.com',
                    'address' => $dateOut, // save date out to address
                    'parent_name' => $parentName,
                    'parent_phone' => $parentPhone,
                    'parent_email' => null,
                    'class_id' => $classId,
                    'roll_number' => '',
                    'academic_year' => $acadYear,
                    'status' => $status,
                    'photo' => null
                ];
                
                if (Student::create($userData, $studentData)) {
                    // Auto-create Fee invoice if Fee value is positive
                    if ($feeAmount > 0) {
                        // Retrieve the newly inserted student ID
                        $stmtUser = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
                        $stmtUser->execute(['username' => $admNum]);
                        $u = $stmtUser->fetch();
                        if ($u) {
                            $stmtStd = $db->prepare("SELECT id FROM students WHERE user_id = :user_id LIMIT 1");
                            $stmtStd->execute(['user_id' => $u['id']]);
                            $std = $stmtStd->fetch();
                            if ($std) {
                                $studentId = $std['id'];
                                $feeType = !empty($courseType) ? $courseType : 'Tuition Fee';
                                $dueDate = !empty($dateRegister) ? $dateRegister : date('Y-m-d');
                                Fee::create($studentId, $feeType, $feeAmount, $dueDate, $acadYear);
                            }
                        }
                    }
                    
                    Utility::setFlash('success', "Student '{$fullName}' registered successfully.");
                    redirect('views/students/index.php');
                } else {
                    $msgError = 'Failed to register student record.';
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
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/students/index.php">Students</a></li>
        <li class="breadcrumb-item active" aria-current="page">Enroll Student</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-10">
        <div class="card section-card">
            <div class="card-header bg-white border-0 py-3 text-center">
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-user-plus me-2"></i>New Student Registration</h4>
                <p class="text-muted small mb-0">Fill out these details to register a student and create their login.</p>
            </div>
            <div class="card-body">
                
                <?php if (!empty($msgError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="needs-validation">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="row">
                        <!-- ID / Admission No -->
                        <div class="col-md-6 mb-3">
                            <label for="id" class="form-label fw-bold">ID (Admission Number)</label>
                            <input type="text" class="form-control form-control-custom" id="id" name="id" value="<?= $defaultId ?>" required autofocus>
                            <div class="form-text small text-muted">This will also be the student's login username & password.</div>
                        </div>
                        
                        <!-- Student Full Name -->
                        <div class="col-md-6 mb-3">
                            <label for="name_of_students" class="form-label fw-bold">Name of Students</label>
                            <input type="text" class="form-control form-control-custom" id="name_of_students" name="name_of_students" placeholder="e.g. Fartuun Hussein Mohamed" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Parent Name -->
                        <div class="col-md-6 mb-3">
                            <label for="parent_name" class="form-label fw-bold">Parent's Name</label>
                            <input type="text" class="form-control form-control-custom" id="parent_name" name="parent_name" placeholder="e.g. Fartuun Hussein" required>
                        </div>
                        
                        <!-- Parent Number -->
                        <div class="col-md-6 mb-3">
                            <label for="parent_phone" class="form-label fw-bold">Parent Number (Phone)</label>
                            <input type="text" class="form-control form-control-custom" id="parent_phone" name="parent_phone" placeholder="e.g. 615240687" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Class Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="class_id" class="form-label fw-bold">Class</label>
                            <select class="form-select form-control-custom" id="class_id" name="class_id" required>
                                <option value="">-- Choose Class --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= e($c['class_name']) ?> - <?= e($c['section']) ?> (<?= e($c['academic_year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Course Type -->
                        <div class="col-md-6 mb-3">
                            <label for="course_type" class="form-label fw-bold">Course Type</label>
                            <input type="text" class="form-control form-control-custom" id="course_type" name="course_type" placeholder="e.g. Somaali & Xisaab">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Fee Amount -->
                        <div class="col-md-6 mb-3">
                            <label for="fee" class="form-label fw-bold">Tuition Fee ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-custom" id="fee" name="fee" placeholder="e.g. 6.00" value="6.00">
                            <div class="form-text small text-muted">A tuition invoice will be automatically logged under this amount.</div>
                        </div>

                        <!-- Date Register -->
                        <div class="col-md-6 mb-3">
                            <label for="date_register" class="form-label fw-bold">Date Register</label>
                            <input type="date" class="form-control form-control-custom" id="date_register" name="date_register" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Date Out -->
                        <div class="col-md-6 mb-3">
                            <label for="date_out" class="form-label fw-bold">Date Out (Optional)</label>
                            <input type="date" class="form-control form-control-custom" id="date_out" name="date_out">
                        </div>

                        <!-- Status Selection -->
                        <div class="col-md-6 mb-4">
                            <label for="status" class="form-label fw-bold">Status</label>
                            <select class="form-select form-control-custom" id="status" name="status" required>
                                <option value="Present">Present (Active)</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>views/students/index.php" class="btn btn-light px-4 rounded-3">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-primary-custom text-white px-4">Register Student</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
