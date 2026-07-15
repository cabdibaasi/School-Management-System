<?php
/**
 * Timetable Builder Page
 */
$pageTitle = "Timetable Builder";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$msgSuccess = '';

// Get class selection
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$classes = SchoolClass::getAll();

// Fetch subjects for select if class is loaded
$subjects = [];
$timetable = [];
if ($classId > 0) {
    $stmtSub = $db->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE class_id = :class_id ORDER BY subject_name ASC");
    $stmtSub->execute(['class_id' => $classId]);
    $subjects = $stmtSub->fetchAll();
    
    $timetable = Timetable::getByClassId($classId);
}

// Handle creations and deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        if ($action === 'create_slot') {
            $subId = (int)($_POST['subject_id'] ?? 0);
            $day = $_POST['day_of_week'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            $room = trim($_POST['classroom'] ?? '');
            
            $validator = new Validation();
            $validator->required([
                'subject_id' => 'Subject',
                'day_of_week' => 'Day of Week',
                'start_time' => 'Start Time',
                'end_time' => 'End Time',
                'classroom' => 'Classroom'
            ], $_POST);
            
            if ($validator->passes()) {
                if ($start >= $end) {
                    $msgError = 'Start Time must be before End Time.';
                } else {
                    if (Timetable::create($classId, $subId, $day, $start, $end, $room)) {
                        Utility::setFlash('success', 'Timetable slot scheduled successfully.');
                        redirect('views/timetable/index.php?class_id=' . $classId);
                    } else {
                        $msgError = 'Failed to schedule slot.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'delete_slot') {
            $slotId = (int)($_POST['id'] ?? 0);
            if (Timetable::delete($slotId)) {
                Utility::setFlash('success', 'Schedule slot deleted.');
                redirect('views/timetable/index.php?class_id=' . $classId);
            } else {
                $msgError = 'Failed to delete slot.';
            }
        }
    }
}

// Group timetable by day of week for layout display
$groupedTimetable = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => []
];
foreach ($timetable as $slot) {
    $groupedTimetable[$slot['day_of_week']][] = $slot;
}

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Timetables</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-calendar-alt me-2"></i>Weekly Timetable Builder</h2>
    <?php if ($classId > 0): ?>
        <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#createSlotModal">
            <i class="fas fa-plus me-2"></i> Schedule Slot
        </button>
    <?php endif; ?>
</div>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Class Selection Card -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/timetable/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-9 col-lg-10">
            <select class="form-select form-control-custom" name="class_id" required>
                <option value="">-- Choose Classroom to Load Timetable --</option>
                <?php foreach ($classes as $cls): ?>
                    <option value="<?= $cls['id'] ?>" <?= ($classId === $cls['id']) ? 'selected' : '' ?>>
                        <?= e($cls['class_name']) ?> - <?= e($cls['section']) ?> (Academic Year: <?= e($cls['academic_year']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 col-lg-2 d-grid">
            <button type="submit" class="btn btn-primary btn-primary-custom text-white"><i class="fas fa-folder-open me-2"></i> Load Timetable</button>
        </div>
    </form>
</div>

<!-- Timetable Grid Cards -->
<?php if ($classId === 0): ?>
    <div class="card section-card p-5 text-center text-muted">
        <i class="fas fa-calendar-week fs-1 mb-3 text-secondary-color"></i>
        <h5>Select a Grade Class above to inspect and edit weekly timetable schedules.</h5>
    </div>
<?php else: ?>
    
    <div class="row">
        <?php foreach ($groupedTimetable as $day => $slots): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card section-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h5 class="fw-bold mb-0 text-primary"><i class="far fa-calendar-day text-secondary-color me-2"></i><?= $day ?></h5>
                        <span class="badge bg-blue-light text-primary"><?= count($slots) ?> Sessions</span>
                    </div>
                    
                    <div class="timeline-wrapper">
                        <?php if (empty($slots)): ?>
                            <p class="text-muted small text-center py-4 mb-0">No classes scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($slots as $sl): ?>
                                <div class="bg-light p-3 rounded-3 mb-2 position-relative border-start border-primary border-4 shadow-sm">
                                    <button class="btn btn-sm text-danger position-absolute top-0 end-0 mt-2 me-2 p-1 delete-slot-btn" 
                                            data-id="<?= $sl['id'] ?>" 
                                            data-desc="<?= e($sl['subject_name']) ?> (<?= date('h:i A', strtotime($sl['start_time'])) ?>)"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteSlotModal">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <div class="pe-3">
                                        <strong class="d-block text-dark" style="font-size:0.9rem;"><?= e($sl['subject_name']) ?></strong>
                                        <small class="text-muted font-monospace d-block" style="font-size:0.75rem;"><i class="far fa-clock me-1"></i><?= date('h:i A', strtotime($sl['start_time'])) ?> - <?= date('h:i A', strtotime($sl['end_time'])) ?></small>
                                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                            <span><i class="fas fa-chalkboard-teacher me-1"></i><?= e($sl['teacher_name'] ?: 'Unassigned') ?></span>
                                            <span class="badge bg-secondary-light text-dark"><i class="fas fa-door-open me-1"></i><?= e($sl['classroom']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- CREATE SLOT MODAL -->
    <div class="modal fade" id="createSlotModal" tabindex="-1" aria-labelledby="createSlotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
                <form action="<?= BASE_URL ?>views/timetable/index.php?class_id=<?= $classId ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="create_slot">
                    
                    <div class="modal-header bg-light border-0 py-3">
                        <h5 class="modal-title fw-bold text-primary" id="createSlotModalLabel"><i class="fas fa-calendar-plus me-2"></i>Schedule Timetable Slot</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label fw-semibold small text-muted">SUBJECT</label>
                            <select class="form-select form-control-custom" id="subject_id" name="subject_id" required>
                                <option value="">-- Choose Subject --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= e($sub['subject_name']) ?> (<?= e($sub['subject_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="day_of_week" class="form-label fw-semibold small text-muted">DAY OF WEEK</label>
                            <select class="form-select form-control-custom" id="day_of_week" name="day_of_week" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="start_time" class="form-label fw-semibold small text-muted">START TIME</label>
                                <input type="time" class="form-control form-control-custom" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-6">
                                <label for="end_time" class="form-label fw-semibold small text-muted">END TIME</label>
                                <input type="time" class="form-control form-control-custom" id="end_time" name="end_time" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="classroom" class="form-label fw-semibold small text-muted">CLASSROOM / ROOM NO.</label>
                            <input type="text" class="form-control form-control-custom" id="classroom" name="classroom" placeholder="e.g. Lab 2, Room 304" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light py-2">
                        <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-3 text-white">Save Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE SLOT MODAL -->
    <div class="modal fade" id="deleteSlotModal" tabindex="-1" aria-labelledby="deleteSlotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
                <form action="<?= BASE_URL ?>views/timetable/index.php?class_id=<?= $classId ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="delete_slot">
                    <input type="hidden" id="delete_id" name="id">
                    
                    <div class="modal-header bg-danger text-white border-0 py-3">
                        <h5 class="modal-title fw-bold" id="deleteSlotModalLabel"><i class="fas fa-trash-alt me-2"></i>Remove Scheduled Slot</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                        <h5 class="fw-bold mb-2">Are you sure?</h5>
                        <p class="text-muted">You are about to remove slot: <strong id="delete_slot_display" class="text-danger"></strong> from the schedule.</p>
                    </div>
                    <div class="modal-footer border-0 bg-light py-2">
                        <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const delBtns = document.querySelectorAll(".delete-slot-btn");
    delBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_slot_display").innerText = this.getAttribute("data-desc");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
