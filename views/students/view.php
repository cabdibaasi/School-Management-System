<?php
/**
 * View Student Profile Details Page
 */
$pageTitle = "Student Profile";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher', 'student']);

$id = (int)($_GET['id'] ?? 0);

// If the logged in user is a student, they can only view their own profile!
if (Auth::role() === 'student' && Auth::profileId() !== $id) {
    Utility::setFlash('danger', 'You do not have permission to view other student profiles.');
    redirect('views/dashboard.php');
}

$student = Student::getById($id);

if (!$student) {
    Utility::setFlash('danger', 'Student not found.');
    redirect('views/students/index.php');
}

// Generate age
$dob = new DateTime($student['date_of_birth']);
$today = new DateTime();
$age = $today->diff($dob)->y;

$photoPath = BASE_URL . 'assets/uploads/profiles/' . $student['photo'];
if (empty($student['photo']) || !file_exists(UPLOAD_PATH . 'profiles/' . $student['photo'])) {
    $photoPath = BASE_URL . 'assets/img/default-avatar.png';
}

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <?php if (Auth::role() === 'admin'): ?>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/students/index.php">Students</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page">Student Profile</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-id-badge me-2"></i>Student Details Profile</h2>
    <div>
        <button onclick="window.print();" class="btn btn-outline-dark rounded-3 me-1">
            <i class="fas fa-print me-1"></i> Print Profile
        </button>
        <?php if (Auth::role() === 'admin'): ?>
            <a href="<?= BASE_URL ?>views/students/edit.php?id=<?= $student['id'] ?>" class="btn btn-warning rounded-3 fw-bold text-dark me-1">
                <i class="fas fa-edit me-1"></i> Edit Profile
            </a>
            <a href="<?= BASE_URL ?>views/students/index.php" class="btn btn-secondary rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Directory
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Profile Left Card Column -->
    <div class="col-lg-4 mb-4">
        <div class="card metric-card text-center p-4">
            <div class="card-body">
                <div class="profile-pic-wrapper mb-3" style="width:130px; height:130px;">
                    <img src="<?= $photoPath ?>" alt="Avatar" class="profile-pic" style="width:130px; height:130px;">
                </div>
                <h4 class="fw-bold mb-1"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h4>
                <p class="text-muted small font-monospace mb-2">Admission No: <?= e($student['admission_number']) ?></p>
                <span class="badge <?= ($student['status'] === 'active') ? 'bg-success' : 'bg-danger' ?> px-3 py-2 rounded-pill"><?= ucfirst($student['status']) ?></span>
                
                <hr class="my-4">
                
                <!-- School Info Quick Read -->
                <div class="text-start">
                    <h6 class="fw-bold text-secondary-color mb-2">CLASSROOM ASSIGNMENT</h6>
                    <p class="mb-1 text-dark fw-semibold"><i class="fas fa-school text-muted me-2"></i><?= e($student['class_name'] ?? 'Unassigned') ?> - <?= e($student['section'] ?? '') ?></p>
                    <p class="mb-1 text-muted small"><i class="fas fa-list-ol text-muted me-2"></i>Roll No: <?= e($student['roll_number'] ?: 'N/A') ?></p>
                    <p class="mb-0 text-muted small"><i class="far fa-calendar-alt text-muted me-2"></i>Year: <?= e($student['academic_year']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Sheets Column -->
    <div class="col-lg-8 mb-4">
        <div class="card section-card">
            <!-- Sheet 1: Personal details -->
            <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-user me-2"></i>Personal Particulars</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-4">
                    <span class="text-muted small d-block">FULL NAME</span>
                    <strong class="text-dark"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></strong>
                </div>
                <div class="col-md-6 col-lg-4">
                    <span class="text-muted small d-block">GENDER</span>
                    <strong class="text-dark text-capitalize"><?= e($student['gender']) ?></strong>
                </div>
                <div class="col-md-6 col-lg-4">
                    <span class="text-muted small d-block">DATE OF BIRTH</span>
                    <strong class="text-dark"><?= e($student['date_of_birth']) ?> (<?= $age ?> Years Old)</strong>
                </div>
                <div class="col-md-6 col-lg-4">
                    <span class="text-muted small d-block">STUDENT ID CARD</span>
                    <strong class="text-dark font-monospace"><?= e($student['student_id_card']) ?></strong>
                </div>
                <div class="col-md-6 col-lg-4">
                    <span class="text-muted small d-block">BLOOD GROUP</span>
                    <strong class="text-dark"><?= e($student['blood_group'] ?: 'N/A') ?></strong>
                </div>
                <div class="col-md-6 col-lg-4">
                    <span class="text-muted small d-block">NATIONALITY</span>
                    <strong class="text-dark"><?= e($student['nationality']) ?></strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">CONTACT PHONE</span>
                    <strong class="text-dark"><?= e($student['phone'] ?: 'N/A') ?></strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">CONTACT EMAIL</span>
                    <strong class="text-dark"><?= e($student['email'] ?: 'N/A') ?></strong>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">RESIDENTIAL ADDRESS</span>
                    <strong class="text-dark"><?= nl2br(e($student['address'])) ?></strong>
                </div>
            </div>

            <!-- Sheet 2: Parent details -->
            <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-users me-2"></i>Parent / Guardian Information</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <span class="text-muted small d-block">PARENT NAME</span>
                    <strong class="text-dark"><?= e($student['parent_name']) ?></strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">PARENT PHONE NO.</span>
                    <strong class="text-dark"><?= e($student['parent_phone']) ?></strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">PARENT EMAIL</span>
                    <strong class="text-dark"><?= e($student['parent_email'] ?: 'N/A') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS styles targeting browser print layouts directly */
@media print {
    .no-print, nav, #sidebar, .navbar-custom, .btn {
        display: none !important;
    }
    #content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .profile-pic {
        border: 2px solid #000 !important;
    }
    body {
        background-color: #fff !important;
        color: #000 !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
