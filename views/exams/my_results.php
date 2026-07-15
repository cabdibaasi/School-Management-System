<?php
/**
 * Student Portal Exam Results Directory
 */
$pageTitle = "My Academic Results";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('student');

$db = Database::connect();
$profileId = Auth::profileId();

// Load all exams where student has marks recorded
$sql = "SELECT DISTINCT e.*, c.class_name, c.section 
        FROM exams e
        JOIN marks m ON e.id = m.exam_id
        JOIN classes c ON e.class_id = c.id
        WHERE m.student_id = :student_id
        ORDER BY e.exam_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['student_id' => $profileId]);
$exams = $stmt->fetchAll();

// Add calculations to each exam
$results = [];
foreach ($exams as $ex) {
    $marks = Exam::getStudentReport($profileId, $ex['id']);
    
    $obtained = 0;
    $max = 0;
    $gpaPoints = 0;
    $count = count($marks);
    
    foreach ($marks as $m) {
        $obtained += $m['marks_obtained'];
        $max += $m['max_marks'];
        $percentage = ($m['max_marks'] > 0) ? ($m['marks_obtained'] / $m['max_marks']) * 100 : 0;
        $gradeCalc = Exam::calculateGrade($percentage);
        $gpaPoints += $gradeCalc['gpa'];
    }
    
    $avgPercentage = ($max > 0) ? ($obtained / $max) * 100 : 0;
    $gradeInfo = Exam::calculateGrade($avgPercentage);
    $cgpa = ($count > 0) ? round($gpaPoints / $count, 2) : 0.00;
    
    $results[] = [
        'exam' => $ex,
        'obtained' => $obtained,
        'max' => $max,
        'percentage' => $avgPercentage,
        'grade' => $gradeInfo['grade'],
        'cgpa' => $cgpa,
        'status' => $gradeInfo['status']
    ];
}

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">My Results</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-poll-h me-2"></i>My Exam Outcomes</h2>

<!-- Result Cards Grid -->
<?php if (empty($results)): ?>
    <div class="card section-card p-5 text-center text-muted">
        <i class="fas fa-file-invoice fs-1 mb-3 text-secondary-color"></i>
        <h5>No exam scores recorded for you yet.</h5>
    </div>
<?php else: ?>
    
    <div class="row">
        <?php foreach ($results as $res): 
            $outcomeColor = ($res['status'] === 'Pass') ? 'bg-success' : 'bg-danger';
            ?>
            <div class="col-md-6 mb-4">
                <div class="card section-card h-100 p-4 shadow-sm border-start border-primary border-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><?= e($res['exam']['exam_name']) ?></h5>
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>Date: <?= date('M d, Y', strtotime($res['exam']['exam_date'])) ?></small>
                            </div>
                            <span class="badge <?= $outcomeColor ?> px-3 py-2 rounded-pill text-uppercase"><?= $res['status'] ?></span>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <span class="text-muted small d-block">SCORE</span>
                                <strong class="text-dark fs-6"><?= $res['obtained'] ?> / <?= $res['max'] ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">PERCENTAGE</span>
                                <strong class="text-primary fs-6"><?= number_format($res['percentage'], 2) ?>%</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted small d-block">GRADE</span>
                                <strong class="text-dark fs-6"><?= $res['grade'] ?></strong>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted small d-block">CGPA</span>
                                <strong class="text-warning fs-6"><?= number_format($res['cgpa'], 2) ?> / 4.0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <a href="<?= BASE_URL ?>views/exams/report_card.php?exam_id=<?= $res['exam']['id'] ?>&student_id=<?= $profileId ?>" class="btn btn-outline-primary py-2 rounded-3 fw-bold">
                            <i class="fas fa-print me-2"></i> View Report Card
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
