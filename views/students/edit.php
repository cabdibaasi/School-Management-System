<?php
/**
 * Edit Student Profile Page - Custom Simplified Registry Layout
 */
$pageTitle = "Edit Student Profile";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard - Admin only
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$classes = SchoolClass::getAll();

$id = (int)($_GET['id'] ?? 0);
$student = Student::getById($id);

if (!$student) {
    Utility::setFlash('danger', 'Student profile not found.');
    redirect('views/students/index.php');
}

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
            // Check uniqueness in user and student details excluding current user
            $checkUser = $db->prepare("SELECT id FROM users WHERE username = :uname AND id != :uid");
            $checkUser->execute(['uname' => $admNum, 'uid' => $student['user_id']]);
            
            $checkStd = $db->prepare("SELECT id FROM students WHERE admission_number = :adm AND id != :id");
            $checkStd->execute(['adm' => $admNum, 'id' => $id]);
            
            if ($checkUser->fetch()) {
                $msgError = "The Student ID '{$admNum}' is already taken by another account.";
            } elseif ($checkStd->fetch()) {
                $msgError = "The Student ID '{$admNum}' is already in use by another student.";
            } else {
                
                // Split name
                $parts = explode(' ', $fullName);
                $firstName = $parts[0] ?? '';
                $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '.';
                
                $status = ($statusVal === 'Present') ? 'active' : 'inactive';
                $acadYear = $student['class_academic_year'] ?? date('Y') . '-' . (date('Y') + 1);
                
                try {
                    $db->beginTransaction();
                    
                    // 1. Update User Account
                    $stmtUser = $db->prepare("UPDATE users SET username = :username, email = :email, status = :status WHERE id = :id");
                    $stmtUser->execute([
                        'username' => $admNum,
                        'email'    => $admNum . '@school.com',
                        'status'   => $status,
                        'id'       => $student['user_id']
                    ]);
                    
                    // 2. Update Student Profile
                    $stmtStd = $db->prepare("UPDATE students SET 
                        admission_number = :admission_number,
                        student_id_card = :student_id_card,
                        first_name = :first_name,
                        last_name = :last_name,
                        date_of_birth = :date_of_birth,
                        nationality = :nationality,
                        phone = :phone,
                        email = :email,
                        address = :address,
                        parent_name = :parent_name,
                        parent_phone = :parent_phone,
                        class_id = :class_id,
                        status = :status
                        WHERE id = :id");
                        
                    $stmtStd->execute([
                        'admission_number' => $admNum,
                        'student_id_card'  => $admNum,
                        'first_name'       => $firstName,
                        'last_name'        => $lastName,
                        'date_of_birth'    => !empty($dateRegister) ? $dateRegister : null,
                        'nationality'      => $courseType,
                        'phone'            => $parentPhone,
                        'email'            => $admNum . '@school.com',
                        'address'          => $dateOut,
                        'parent_name'      => $parentName,
                        'parent_phone'     => $parentPhone,
                        'class_id'         => $classId,
                        'status'           => $status,
                        'id'               => $id
                    ]);
                    
                    $db->commit();
                    Utility::setFlash('success', "Student '{$fullName}' updated successfully.");
                    redirect('views/students/index.php');
                } catch (\Exception $e) {
                    $db->rollBack();
                    $msgError = 'Failed to update student: ' . $e->getMessage();
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
        <li class="breadcrumb-item active" aria-current="page">Edit Student</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-10">
        <div class="card section-card">
            <div class="card-header bg-white border-0 py-3 text-center">
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-user-edit me-2"></i>Edit Student Profile</h4>
                <p class="text-muted small mb-0">Modify the registration details below.</p>
            </div>
            <div class="card-body">
                
                <?php if (!empty($msgError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="row">
                        <!-- ID / Admission No -->
                        <div class="col-md-6 mb-3">
                            <label for="id" class="form-label fw-bold">ID (Admission Number)</label>
                            <input type="text" class="form-control form-control-custom" id="id" name="id" value="<?= e($student['admission_number']) ?>" required autofocus>
                            <div class="form-text small text-muted">Updates the login username as well.</div>
                        </div>
                        
                        <!-- Student Full Name -->
                        <div class="col-md-6 mb-3">
                            <label for="name_of_students" class="form-label fw-bold">Name of Students</label>
                            <input type="text" class="form-control form-control-custom" id="name_of_students" name="name_of_students" value="<?= e($student['first_name'] . ' ' . $student['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Parent Name -->
                        <div class="col-md-6 mb-3">
                            <label for="parent_name" class="form-label fw-bold">Parent's Name</label>
                            <input type="text" class="form-control form-control-custom" id="parent_name" name="parent_name" value="<?= e($student['parent_name']) ?>" required>
                        </div>
                        
                        <!-- Parent Number -->
                        <div class="col-md-6 mb-3">
                            <label for="parent_phone" class="form-label fw-bold">Parent Number (Phone)</label>
                            <input type="text" class="form-control form-control-custom" id="parent_phone" name="parent_phone" value="<?= e($student['parent_phone']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Class Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="class_id" class="form-label fw-bold">Class</label>
                            <select class="form-select form-control-custom" id="class_id" name="class_id" required>
                                <option value="">-- Choose Class --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($student['class_id'] == $c['id']) ? 'selected' : '' ?>>
                                        <?= e($c['class_name']) ?> - <?= e($c['section']) ?> (<?= e($c['academic_year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Course Type -->
                        <div class="col-md-6 mb-3">
                            <label for="course_type" class="form-label fw-bold">Course Type</label>
                            <input type="text" class="form-control form-control-custom" id="course_type" name="course_type" value="<?= e($student['nationality']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Date Register -->
                        <div class="col-md-6 mb-3">
                            <label for="date_register" class="form-label fw-bold">Date Register</label>
                            <input type="date" class="form-control form-control-custom" id="date_register" name="date_register" value="<?= e($student['date_of_birth']) ?>" required>
                        </div>

                        <!-- Date Out -->
                        <div class="col-md-6 mb-3">
                            <label for="date_out" class="form-label fw-bold">Date Out (Optional)</label>
                            <input type="date" class="form-control form-control-custom" id="date_out" name="date_out" value="<?= e($student['address']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Status Selection -->
                        <div class="col-md-6 mb-4">
                            <label for="status" class="form-label fw-bold">Status</label>
                            <select class="form-select form-control-custom" id="status" name="status" required>
                                <option value="Present" <?= ($student['status'] === 'active') ? 'selected' : '' ?>>Present (Active)</option>
                                <option value="Inactive" <?= ($student['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>views/students/index.php" class="btn btn-light px-4 rounded-3">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-primary-custom text-white px-4">Save Changes</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
