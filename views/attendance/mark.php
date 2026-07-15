<?php
/**
 * Mark Student Attendance Page
 */
$pageTitle = "Mark Student Attendance";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher']);

$db = Database::connect();
$msgError = '';
$msgSuccess = '';

// Load inputs
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$classes = SchoolClass::getAll();

$students = [];
if ($classId > 0 && !empty($date)) {
    $students = Attendance::getStudentAttendance($classId, $date);
}

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        $postStudents = $_POST['attendance'] ?? [];
        $records = [];
        
        foreach ($postStudents as $studentId => $attData) {
            $records[$studentId] = [
                'status' => $attData['status'] ?? 'present',
                'remarks' => trim($attData['remarks'] ?? '')
            ];
        }
        
        if (!empty($records)) {
            if (Attendance::saveStudentAttendance($date, $records)) {
                Utility::setFlash('success', 'Student attendance saved successfully.');
                redirect("views/attendance/mark.php?class_id={$classId}&date={$date}");
            } else {
                $msgError = 'Failed to save student attendance.';
            }
        } else {
            $msgError = 'No attendance logs provided.';
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
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/attendance/index.php">Attendance</a></li>
        <li class="breadcrumb-item active" aria-current="page">Mark Student Attendance</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-check-double me-2"></i>Mark Daily Attendance Sheet</h2>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Class Selector -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/attendance/mark.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <select class="form-select form-control-custom" name="class_id" required>
                <option value="">-- Choose Class --</option>
                <?php foreach ($classes as $cls): ?>
                    <option value="<?= $cls['id'] ?>" <?= ($classId === $cls['id']) ? 'selected' : '' ?>>
                        <?= e($cls['class_name']) ?> - <?= e($cls['section']) ?> (Academic Year: <?= e($cls['academic_year']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control form-control-custom" name="date" value="<?= e($date) ?>" required>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary btn-primary-custom text-white"><i class="fas fa-users me-2"></i> Load Class</button>
        </div>
    </form>
</div>

<!-- Attendance Form Sheet -->
<?php if ($classId > 0): ?>
    <form action="<?= BASE_URL ?>views/attendance/mark.php?class_id=<?= $classId ?>&date=<?= $date ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="card section-card">
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll #</th>
                            <th>Student Name</th>
                            <th>Admission #</th>
                            <th style="width: 320px;" class="text-center">Attendance Status</th>
                            <th>Remarks / Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No active students enrolled in this class.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $std): 
                                $status = $std['attendance_status'] ?? 'present';
                                ?>
                                <tr>
                                    <td><?= e($std['roll_number'] ?: '-') ?></td>
                                    <td><strong><?= e($std['first_name'] . ' ' . $std['last_name']) ?></strong></td>
                                    <td class="font-monospace small"><?= e($std['admission_number']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-3">
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input border-success" type="radio" name="attendance[<?= $std['student_id'] ?>][status]" id="p_<?= $std['student_id'] ?>" value="present" <?= ($status === 'present') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                                <label class="form-check-label text-success fw-semibold small" for="p_<?= $std['student_id'] ?>" style="cursor:pointer;">Present</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input border-danger" type="radio" name="attendance[<?= $std['student_id'] ?>][status]" id="a_<?= $std['student_id'] ?>" value="absent" <?= ($status === 'absent') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                                <label class="form-check-label text-danger fw-semibold small" for="a_<?= $std['student_id'] ?>" style="cursor:pointer;">Absent</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input border-warning" type="radio" name="attendance[<?= $std['student_id'] ?>][status]" id="l_<?= $std['student_id'] ?>" value="late" <?= ($status === 'late') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                                <label class="form-check-label text-warning fw-semibold small" for="l_<?= $std['student_id'] ?>" style="cursor:pointer;">Late</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input border-info" type="radio" name="attendance[<?= $std['student_id'] ?>][status]" id="e_<?= $std['student_id'] ?>" value="excused" <?= ($status === 'excused') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                                <label class="form-check-label text-info fw-semibold small" for="e_<?= $std['student_id'] ?>" style="cursor:pointer;">Excused</label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="attendance[<?= $std['student_id'] ?>][remarks]" value="<?= e($std['attendance_remarks']) ?>" placeholder="Add comment (optional)">
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
                        <i class="fas fa-save me-2"></i> Save Daily Attendance
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
