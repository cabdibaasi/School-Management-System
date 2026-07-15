<?php
/**
 * View Teacher Profile Details Page
 */
$pageTitle = "Teacher Profile";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher', 'student']);

$id = (int)($_GET['id'] ?? 0);

// If the logged in user is a teacher, they can only view their own profile!
if (Auth::role() === 'teacher' && Auth::profileId() !== $id) {
    Utility::setFlash('danger', 'You do not have permission to view other teacher profiles.');
    redirect('views/dashboard.php');
}

$teacher = Teacher::getById($id);

if (!$teacher) {
    Utility::setFlash('danger', 'Teacher not found.');
    redirect('views/teachers/index.php');
}

$photoPath = BASE_URL . 'assets/uploads/profiles/' . $teacher['photo'];
if (empty($teacher['photo']) || !file_exists(UPLOAD_PATH . 'profiles/' . $teacher['photo'])) {
    $photoPath = BASE_URL . 'assets/img/default-avatar.png';
}

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <?php if (Auth::role() === 'admin'): ?>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/teachers/index.php">Teachers</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page">Teacher Profile</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-id-badge me-2"></i>Teacher Profile Sheet</h2>
    <div>
        <button onclick="window.print();" class="btn btn-outline-dark rounded-3 me-1">
            <i class="fas fa-print me-1"></i> Print Profile
        </button>
        <?php if (Auth::role() === 'admin'): ?>
            <a href="<?= BASE_URL ?>views/teachers/edit.php?id=<?= $teacher['id'] ?>" class="btn btn-warning rounded-3 fw-bold text-dark me-1">
                <i class="fas fa-edit me-1"></i> Edit Profile
            </a>
            <a href="<?= BASE_URL ?>views/teachers/index.php" class="btn btn-secondary rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Directory
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Left Card -->
    <div class="col-lg-4 mb-4">
        <div class="card metric-card text-center p-4">
            <div class="card-body">
                <div class="profile-pic-wrapper mb-3" style="width:130px; height:130px;">
                    <img src="<?= $photoPath ?>" alt="Avatar" class="profile-pic" style="width:130px; height:130px;">
                </div>
                <h4 class="fw-bold mb-1"><?= e($teacher['full_name']) ?></h4>
                <p class="text-muted small font-monospace mb-2">Employee ID: <?= e($teacher['employee_id']) ?></p>
                <span class="badge <?= ($teacher['status'] === 'active') ? 'bg-success' : 'bg-danger' ?> px-3 py-2 rounded-pill"><?= ucfirst($teacher['status']) ?></span>
                
                <hr class="my-4">
                
                <div class="text-start">
                    <h6 class="fw-bold text-secondary-color mb-2">EMPLOYMENT METRICS</h6>
                    <p class="mb-1 text-dark fw-semibold"><i class="fas fa-coins text-muted me-2"></i>Salary: <?= Utility::formatCurrency($teacher['salary'], Setting::get('currency','USD')) ?></p>
                    <p class="mb-0 text-muted small"><i class="far fa-calendar-alt text-muted me-2"></i>Joined: <?= date('M d, Y', strtotime($teacher['date_joined'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Details Sheet -->
    <div class="col-lg-8 mb-4">
        <div class="card section-card">
            <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fas fa-user me-2"></i>Personal & Contact Particulars</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-6">
                    <span class="text-muted small d-block">FULL NAME</span>
                    <strong class="text-dark"><?= e($teacher['full_name']) ?></strong>
                </div>
                <div class="col-md-6 col-lg-6">
                    <span class="text-muted small d-block">GENDER</span>
                    <strong class="text-dark text-capitalize"><?= e($teacher['gender']) ?></strong>
                </div>
                <div class="col-md-6 col-lg-6">
                    <span class="text-muted small d-block">QUALIFICATION</span>
                    <strong class="text-dark"><?= e($teacher['qualification']) ?></strong>
                </div>
                <div class="col-md-6 col-lg-6">
                    <span class="text-muted small d-block">CONTACT PHONE</span>
                    <strong class="text-dark"><?= e($teacher['phone'] ?: 'N/A') ?></strong>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">CONTACT EMAIL</span>
                    <strong class="text-dark"><?= e($teacher['email'] ?: 'N/A') ?></strong>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">RESIDENTIAL ADDRESS</span>
                    <strong class="text-dark"><?= nl2br(e($teacher['address'])) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
    body {
        background-color: #fff !important;
        color: #000 !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
