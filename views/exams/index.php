<?php
/**
 * Exam Schedules & Marks Panel Page
 */
$pageTitle = "Exam Management";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard - Admins only
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$msgSuccess = '';

// Handle creations and deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        if ($action === 'create') {
            $name = trim($_POST['exam_name'] ?? '');
            $classId = (int)($_POST['class_id'] ?? 0);
            $date = $_POST['exam_date'] ?? '';
            $year = trim($_POST['academic_year'] ?? '');
            
            $validator = new Validation();
            $validator->required([
                'exam_name' => 'Exam Name',
                'class_id' => 'Class',
                'exam_date' => 'Exam Date',
                'academic_year' => 'Academic Year'
            ], $_POST);
            
            if ($validator->passes()) {
                if (Exam::create($name, $classId, $date, $year)) {
                    Utility::setFlash('success', 'Exam scheduled successfully.');
                    redirect('views/exams/index.php');
                } else {
                    $msgError = 'Failed to schedule exam.';
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (Exam::delete($id)) {
                Utility::setFlash('success', 'Exam deleted successfully.');
                redirect('views/exams/index.php');
            } else {
                $msgError = 'Failed to delete exam.';
            }
        }
    }
}

// Fetch lists
$exams = Exam::getAll();
$classes = SchoolClass::getAll();
$csrfToken = Utility::generateCSRFToken();

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Exams</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-file-signature me-2"></i>Exam Schedules</h2>
    <div>
        <a href="<?= BASE_URL ?>views/exams/marks_entry.php" class="btn btn-outline-primary rounded-3 me-1fw-bold">
            <i class="fas fa-marker me-1"></i> Enter Marks
        </a>
        <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#createExamModal">
            <i class="fas fa-plus me-2"></i> Schedule Exam
        </button>
    </div>
</div>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Exams Table Card -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Class Grade</th>
                    <th>Exam Date</th>
                    <th>Academic Year</th>
                    <th class="text-center" style="width: 280px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No exams scheduled yet. Click "Schedule Exam" to begin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $ex): ?>
                        <tr>
                            <td><strong class="text-dark"><?= e($ex['exam_name']) ?></strong></td>
                            <td><?= e($ex['class_name']) ?> - <?= e($ex['section']) ?></td>
                            <td><i class="far fa-calendar-alt text-muted me-1"></i><?= date('M d, Y', strtotime($ex['exam_date'])) ?></td>
                            <td><?= e($ex['academic_year']) ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>views/exams/marks_entry.php?exam_id=<?= $ex['id'] ?>" class="btn btn-sm btn-outline-primary rounded-3 me-1">
                                    <i class="fas fa-marker"></i> Record Marks
                                </a>
                                <button class="btn btn-sm btn-outline-danger rounded-3 delete-exam-btn" 
                                        data-id="<?= $ex['id'] ?>"
                                        data-name="<?= e($ex['exam_name']) ?> (<?= e($ex['class_name']) ?>)"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteExamModal">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CREATE EXAM MODAL -->
<div class="modal fade" id="createExamModal" tabindex="-1" aria-labelledby="createExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/exams/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-primary" id="createExamModalLabel"><i class="fas fa-calendar-plus me-2"></i>Schedule Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="exam_name" class="form-label fw-semibold small text-muted">EXAM NAME</label>
                        <input type="text" class="form-control form-control-custom" id="exam_name" name="exam_name" placeholder="e.g. Midterm Exams, Final Exams" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label fw-semibold small text-muted">GRADE CLASS</label>
                        <select class="form-select form-control-custom" id="class_id" name="class_id" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= $cls['id'] ?>"><?= e($cls['class_name']) ?> - <?= e($cls['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exam_date" class="form-label fw-semibold small text-muted">EXAM DATE</label>
                        <input type="date" class="form-control form-control-custom" id="exam_date" name="exam_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="academic_year" class="form-label fw-semibold small text-muted">ACADEMIC YEAR</label>
                        <input type="text" class="form-control form-control-custom" id="academic_year" name="academic_year" value="<?= e(Setting::get('current_academic_year', '2026-2027')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 text-white">Save Exam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE EXAM MODAL -->
<div class="modal fade" id="deleteExamModal" tabindex="-1" aria-labelledby="deleteExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/exams/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteExamModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Exam Schedule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted">You are about to delete exam: <strong id="delete_exam_display" class="text-danger"></strong>.</p>
                    <p class="text-muted small">Warning: This will delete all student mark scores recorded for this exam!</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Exam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const delBtns = document.querySelectorAll(".delete-exam-btn");
    delBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_exam_display").innerText = this.getAttribute("data-name");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
