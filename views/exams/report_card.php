<?php
/**
 * Student Report Card Page
 */
$pageTitle = "Academic Report Card";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher', 'student']);

$db = Database::connect();

// Get parameters
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Enforce student scoping
if (Auth::role() === 'student') {
    $studentId = Auth::profileId();
}

$student = Student::getById($studentId);
$exam = Exam::getById($examId);

if (!$student || !$exam) {
    Utility::setFlash('danger', 'Invalid student or exam parameters.');
    redirect('views/dashboard.php');
}

// Fetch marks
$marksList = Exam::getStudentReport($studentId, $examId);

// Calculate overall summary metrics
$totalObtained = 0;
$totalMax = 0;
$totalGpaPoints = 0;
$subjectCount = count($marksList);

foreach ($marksList as $m) {
    $totalObtained += $m['marks_obtained'];
    $totalMax += $m['max_marks'];
    
    // Calculate GPA point for this subject score
    $percentage = ($m['max_marks'] > 0) ? ($m['marks_obtained'] / $m['max_marks']) * 100 : 0;
    $gradeCalc = Exam::calculateGrade($percentage);
    $totalGpaPoints += $gradeCalc['gpa'];
}

$averagePercentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;
$overallGrade = Exam::calculateGrade($averagePercentage);
$cumulativeGpa = ($subjectCount > 0) ? round($totalGpaPoints / $subjectCount, 2) : 0.00;

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <?php if (Auth::role() === 'student'): ?>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/exams/my_results.php">My Results</a></li>
        <?php else: ?>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/exams/index.php">Exams</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page">Report Card</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-poll-h me-2"></i>Academic Report Card</h2>
    <div>
        <button onclick="window.print();" class="btn btn-dark rounded-3 me-1">
            <i class="fas fa-print me-1"></i> Print / PDF
        </button>
        <?php if (Auth::role() !== 'student'): ?>
            <a href="<?= BASE_URL ?>views/students/view.php?id=<?= $studentId ?>" class="btn btn-secondary rounded-3">
                <i class="fas fa-user-circle me-1"></i> View Profile
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- REPORT CARD FRAME -->
<div class="card section-card p-4 p-md-5 border shadow-lg" style="border-radius: 20px;">
    
    <!-- School Letterhead Header -->
    <div class="row border-bottom pb-4 mb-4 text-center text-md-start">
        <div class="col-md-8">
            <h3 class="fw-bold text-primary mb-1"><?= e(Setting::get('school_name', 'St. Andrew Academy')) ?></h3>
            <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt me-1"></i><?= e(Setting::get('school_address', '')) ?></p>
            <p class="text-muted small mb-0"><i class="fas fa-phone me-1"></i><?= e(Setting::get('school_phone', '')) ?> | <?= e(Setting::get('school_email', '')) ?></p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <h4 class="fw-bold text-secondary-color text-uppercase mb-0">OFFICIAL REPORT CARD</h4>
            <span class="badge bg-blue-light text-primary px-3 py-2 rounded-pill mt-2 font-monospace"><?= e($exam['exam_name']) ?></span>
        </div>
    </div>

    <!-- Student details grid -->
    <div class="row g-3 mb-4 text-start">
        <div class="col-6 col-md-3">
            <span class="text-muted small d-block">STUDENT NAME</span>
            <strong class="text-dark fs-6"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></strong>
        </div>
        <div class="col-6 col-md-3">
            <span class="text-muted small d-block">ADMISSION NO.</span>
            <strong class="text-dark font-monospace fs-6"><?= e($student['admission_number']) ?></strong>
        </div>
        <div class="col-6 col-md-3">
            <span class="text-muted small d-block">CLASS GRADE</span>
            <strong class="text-dark fs-6"><?= e($student['class_name']) ?> - <?= e($student['section']) ?></strong>
        </div>
        <div class="col-6 col-md-3">
            <span class="text-muted small d-block">ROLL NUMBER</span>
            <strong class="text-dark fs-6"><?= e($student['roll_number'] ?: 'N/A') ?></strong>
        </div>
    </div>

    <!-- Marks detailed table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th class="text-center">Score (Max 100)</th>
                    <th class="text-center">Letter Grade</th>
                    <th class="text-center">GPA</th>
                    <th class="text-center">Status</th>
                    <th>Teacher Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marksList)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No marks recorded for this exam yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marksList as $m): 
                        $percentage = ($m['max_marks'] > 0) ? ($m['marks_obtained'] / $m['max_marks']) * 100 : 0;
                        $grade = Exam::calculateGrade($percentage);
                        $statusBadge = ($grade['status'] === 'Pass') ? 'text-success' : 'text-danger';
                        ?>
                        <tr>
                            <td><strong><?= e($m['subject_name']) ?></strong></td>
                            <td class="font-monospace text-muted small"><?= e($m['subject_code']) ?></td>
                            <td class="text-center fw-bold"><?= e($m['marks_obtained']) ?></td>
                            <td class="text-center fw-bold text-primary"><?= $grade['grade'] ?></td>
                            <td class="text-center font-monospace"><?= number_format($grade['gpa'], 1) ?></td>
                            <td class="text-center fw-bold <?= $statusBadge ?>"><?= $grade['status'] ?></td>
                            <td class="small text-muted"><?= e($m['remarks'] ?: 'Satisfactory performance') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Overall Summary Badges -->
    <?php if (!empty($marksList)): ?>
        <div class="row g-3 border-top pt-4 text-start">
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 bg-light">
                    <span class="text-muted small d-block">TOTAL MARKS OBTAINED</span>
                    <strong class="fs-4 text-dark"><?= $totalObtained ?> / <?= $totalMax ?></strong>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 bg-light">
                    <span class="text-muted small d-block">AVERAGE PERCENTAGE</span>
                    <strong class="fs-4 text-primary"><?= number_format($averagePercentage, 2) ?>%</strong>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 bg-light">
                    <span class="text-muted small d-block">CUMULATIVE GPA</span>
                    <strong class="fs-4 text-warning"><?= number_format($cumulativeGpa, 2) ?> / 4.0</strong>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <?php 
                $finalStatusColor = ($overallGrade['status'] === 'Pass') ? 'bg-success' : 'bg-danger';
                ?>
                <div class="border rounded-3 p-3 text-white <?= $finalStatusColor ?>">
                    <span class="text-white-50 small d-block">ACADEMIC OUTCOME</span>
                    <strong class="fs-4 text-uppercase"><?= $overallGrade['status'] ?> (<?= $overallGrade['grade'] ?>)</strong>
                </div>
            </div>
        </div>
        
        <!-- Signatures layout -->
        <div class="row mt-5 pt-4 justify-content-between text-center d-none d-print-flex" style="display:none;">
            <div class="col-4 border-top pt-2">
                <p class="small fw-bold mb-0">Class Teacher Signature</p>
            </div>
            <div class="col-4 border-top pt-2">
                <p class="small fw-bold mb-0">Headmaster Signature</p>
            </div>
        </div>
    <?php endif; ?>

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
        padding: 0 !important;
        margin: 0 !important;
    }
    body {
        background-color: #fff !important;
        color: #000 !important;
        padding: 20px !important;
    }
    .d-print-flex {
        display: flex !important;
    }
}
</style>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
