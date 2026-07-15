<?php
/**
 * Mark Teacher Attendance (Admin Only)
 */
$pageTitle = "Mark Teacher Attendance";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard - Admin only
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$msgSuccess = '';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$teachers = Attendance::getTeacherAttendance($date);

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        $postTeachers = $_POST['attendance'] ?? [];
        $records = [];
        
        foreach ($postTeachers as $teacherId => $attData) {
            $records[$teacherId] = [
                'status' => $attData['status'] ?? 'present',
                'remarks' => trim($attData['remarks'] ?? '')
            ];
        }
        
        if (!empty($records)) {
            if (Attendance::saveTeacherAttendance($date, $records)) {
                Utility::setFlash('success', 'Teacher attendance saved successfully.');
                redirect("views/attendance/teacher_mark.php?date={$date}");
            } else {
                $msgError = 'Failed to save teacher attendance.';
            }
        } else {
            $msgError = 'No logs provided.';
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
        <li class="breadcrumb-item active" aria-current="page">Mark Teacher Attendance</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-clipboard-check me-2"></i>Mark Teacher Attendance</h2>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Date Selector -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/attendance/teacher_mark.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-9 col-lg-10">
            <input type="date" class="form-control form-control-custom" name="date" value="<?= e($date) ?>" required>
        </div>
        <div class="col-md-3 col-lg-2 d-grid">
            <button type="submit" class="btn btn-primary btn-primary-custom text-white"><i class="fas fa-calendar-alt me-2"></i> Load Date</button>
        </div>
    </form>
</div>

<!-- Teacher Attendance Sheet -->
<form action="<?= BASE_URL ?>views/attendance/teacher_mark.php?date=<?= $date ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <div class="card section-card">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 150px;">Employee ID</th>
                        <th>Teacher Name</th>
                        <th style="width: 320px;" class="text-center">Attendance Status</th>
                        <th>Remarks / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No active teachers registered in the system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $tch): 
                            $status = $tch['attendance_status'] ?? 'present';
                            ?>
                            <tr>
                                <td class="font-monospace fw-semibold text-primary"><?= e($tch['employee_id']) ?></td>
                                <td><strong><?= e($tch['full_name']) ?></strong></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-3">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input border-success" type="radio" name="attendance[<?= $tch['teacher_id'] ?>][status]" id="tp_<?= $tch['teacher_id'] ?>" value="present" <?= ($status === 'present') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                            <label class="form-check-label text-success fw-semibold small" for="tp_<?= $tch['teacher_id'] ?>" style="cursor:pointer;">Present</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input border-danger" type="radio" name="attendance[<?= $tch['teacher_id'] ?>][status]" id="ta_<?= $tch['teacher_id'] ?>" value="absent" <?= ($status === 'absent') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                            <label class="form-check-label text-danger fw-semibold small" for="ta_<?= $tch['teacher_id'] ?>" style="cursor:pointer;">Absent</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input border-warning" type="radio" name="attendance[<?= $tch['teacher_id'] ?>][status]" id="tl_<?= $tch['teacher_id'] ?>" value="late" <?= ($status === 'late') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                            <label class="form-check-label text-warning fw-semibold small" for="tl_<?= $tch['teacher_id'] ?>" style="cursor:pointer;">Late</label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input border-info" type="radio" name="attendance[<?= $tch['teacher_id'] ?>][status]" id="te_<?= $tch['teacher_id'] ?>" value="excused" <?= ($status === 'excused') ? 'checked' : '' ?> style="cursor:pointer; width:1.1em; height:1.1em;">
                                            <label class="form-check-label text-info fw-semibold small" for="te_<?= $tch['teacher_id'] ?>" style="cursor:pointer;">Excused</label>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="attendance[<?= $tch['teacher_id'] ?>][remarks]" value="<?= e($tch['attendance_remarks']) ?>" placeholder="Add comment">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($teachers)): ?>
            <div class="card-footer bg-white border-0 text-end mt-3">
                <button type="submit" class="btn btn-success px-5 rounded-3 fw-bold">
                    <i class="fas fa-save me-2"></i> Save Teachers Attendance
                </button>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
