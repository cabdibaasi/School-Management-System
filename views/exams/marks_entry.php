<?php
/**
 * Record Student Subject Marks Page
 */
$pageTitle = "Record Exam Marks";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher']);

$db = Database::connect();
$msgError = '';
$msgSuccess = '';

// Load query parameters
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$exams = Exam::getAll();
$subjects = [];
$students = [];

if ($examId > 0) {
    $examInfo = Exam::getById($examId);
    if ($examInfo) {
        // Load subjects assigned to the class of this exam
        $stmtSub = $db->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE class_id = :class_id ORDER BY subject_name ASC");
        $stmtSub->execute(['class_id' => $examInfo['class_id']]);
        $subjects = $stmtSub->fetchAll();
    }
}

if ($examId > 0 && $subjectId > 0) {
    $students = Exam::getSubjectMarks($examId, $subjectId);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        $postMarks = $_POST['marks'] ?? [];
        $records = [];
        $valid = true;
        
        foreach ($postMarks as $studentId => $markData) {
            $obtained = trim($markData['obtained']);
            
            if ($obtained === '') {
                continue; // Skip empty rows
            }
            
            if (!is_numeric($obtained) || $obtained < 0 || $obtained > 100) {
                $msgError = 'Marks obtained must be a numeric score between 0 and 100.';
                $valid = false;
                break;
            }
            
            $records[$studentId] = [
                'obtained' => (float)$obtained,
                'remarks' => trim($markData['remarks'] ?? '')
            ];
        }
        
        if ($valid && !empty($records)) {
            if (Exam::saveSubjectMarks($examId, $subjectId, $records)) {
                Utility::setFlash('success', 'Exam marks recorded successfully.');
                redirect("views/exams/marks_entry.php?exam_id={$examId}&subject_id={$subjectId}");
            } else {
                $msgError = 'Failed to record marks.';
            }
        } elseif ($valid && empty($records)) {
            $msgError = 'No marks records were submitted.';
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
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/exams/index.php">Exams</a></li>
        <li class="breadcrumb-item active" aria-current="page">Record Marks</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-marker me-2"></i>Record Exam Marks</h2>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Selector Panel -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/exams/marks_entry.php" method="GET" class="row g-2 align-items-center">
        <!-- Exam selector -->
        <div class="col-md-5">
            <select class="form-select form-control-custom" name="exam_id" onchange="this.form.submit()" required>
                <option value="">-- Choose Scheduled Exam --</option>
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= $ex['id'] ?>" <?= ($examId === $ex['id']) ? 'selected' : '' ?>>
                        <?= e($ex['exam_name']) ?> (<?= e($ex['class_name']) ?> - <?= e($ex['section']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Subject selector -->
        <div class="col-md-5">
            <select class="form-select form-control-custom" name="subject_id" required>
                <option value="">-- Choose Subject --</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?= $sub['id'] ?>" <?= ($subjectId === $sub['id']) ? 'selected' : '' ?>>
                        <?= e($sub['subject_name']) ?> (<?= e($sub['subject_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Action -->
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary btn-primary-custom text-white"><i class="fas fa-search me-1"></i> Load Students</button>
        </div>
    </form>
</div>

<!-- Score sheet -->
<?php if ($examId > 0 && $subjectId > 0): ?>
    <form action="<?= BASE_URL ?>views/exams/marks_entry.php?exam_id=<?= $examId ?>&subject_id=<?= $subjectId ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="card section-card">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-list-ol me-2"></i>Subject Marks Entry Sheet</h5>
            
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll #</th>
                            <th>Student Name</th>
                            <th>Admission #</th>
                            <th style="width: 150px;">Score (Max 100)</th>
                            <th>Remarks / Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No active students found in this class.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $std): ?>
                                <tr>
                                    <td><?= e($std['roll_number'] ?: '-') ?></td>
                                    <td><strong><?= e($std['first_name'] . ' ' . $std['last_name']) ?></strong></td>
                                    <td class="font-monospace small text-muted"><?= e($std['admission_number']) ?></td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm text-center fw-bold" 
                                               name="marks[<?= $std['student_id'] ?>][obtained]" 
                                               value="<?= ($std['marks_obtained'] !== null) ? e($std['marks_obtained']) : '' ?>" 
                                               placeholder="0.0" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="marks[<?= $std['student_id'] ?>][remarks]" 
                                               value="<?= e($std['remarks']) ?>" 
                                               placeholder="Add performance comment">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($students)): ?>
                <div class="card-footer bg-white border-0 text-end mt-3">
                    <button type="submit" class="btn btn-success px-5 rounded-3 fw-bold">
                        <i class="fas fa-save me-2"></i> Save Marks Records
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
