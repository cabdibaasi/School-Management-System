<?php
/**
 * Attendance Control Dashboard Page
 */
$pageTitle = "Attendance Control Panel";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher']);

$role = Auth::role();
$classes = SchoolClass::getAll();

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Attendance</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-clipboard-user me-2"></i>Attendance Administration</h2>

<div class="row g-4">
    <!-- Card 1: Student Attendance Sheet -->
    <div class="col-md-6 col-lg-4">
        <div class="card metric-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="metric-icon-wrapper bg-blue-light mb-3">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Student Attendance</h5>
                <p class="text-muted small">Mark daily presence lists for student classrooms. Track present, late, excused, or absent flags.</p>
            </div>
            <div class="d-grid mt-4">
                <a href="<?= BASE_URL ?>views/attendance/mark.php" class="btn btn-primary btn-primary-custom text-white py-2 rounded-3">
                    <i class="fas fa-check-double me-2"></i> Mark Students
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Teacher Attendance Sheet -->
    <?php if ($role === 'admin'): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card metric-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="metric-icon-wrapper bg-cyan-light mb-3">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Teacher Attendance</h5>
                    <p class="text-muted small">Mark daily presence logs for faculty staff. Available exclusively for system administrators.</p>
                </div>
                <div class="d-grid mt-4">
                    <a href="<?= BASE_URL ?>views/attendance/teacher_mark.php" class="btn btn-outline-cyan py-2 rounded-3 text-secondary-color" style="border:1.5px solid var(--secondary-color); font-weight:600;">
                        <i class="fas fa-clipboard-check me-2"></i> Mark Teachers
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Card 3: Monthly Statistics Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card metric-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="metric-icon-wrapper bg-success-light mb-3">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Monthly Summaries</h5>
                <p class="text-muted small">Calculate monthly pivot metrics, percentage logs, and generate printable sheets.</p>
            </div>
            <div class="d-grid mt-4">
                <a href="<?= BASE_URL ?>views/attendance/report.php" class="btn btn-outline-success py-2 rounded-3 fw-bold">
                    <i class="fas fa-file-invoice me-2"></i> View Reports
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
