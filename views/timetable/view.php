<?php
/**
 * View Personal Timetable Page
 */
$pageTitle = "My Weekly Schedule";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['teacher', 'student']);

$role = Auth::role();
$profileId = Auth::profileId();

$timetable = [];
if ($role === 'student') {
    $classId = $_SESSION['class_id'] ?? 0;
    if ($classId > 0) {
        $timetable = Timetable::getByClassId($classId);
    }
} elseif ($role === 'teacher') {
    if ($profileId > 0) {
        $timetable = Timetable::getByTeacherId($profileId);
    }
}

// Group by day of week
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

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">My Schedule</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-calendar-day me-2"></i>My Weekly Schedule</h2>
    <button onclick="window.print();" class="btn btn-outline-dark rounded-3">
        <i class="fas fa-print me-1"></i> Print Schedule
    </button>
</div>

<?php if (empty($timetable)): ?>
    <div class="card section-card p-5 text-center text-muted">
        <i class="fas fa-calendar-times fs-1 mb-3 text-secondary-color"></i>
        <h5>No class schedules registered for you yet.</h5>
    </div>
<?php else: ?>
    
    <div class="row">
        <?php foreach ($groupedTimetable as $day => $slots): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card section-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h5 class="fw-bold mb-0 text-primary"><i class="far fa-calendar-day text-secondary-color me-2"></i><?= $day ?></h5>
                        <span class="badge bg-blue-light text-primary"><?= count($slots) ?> Slots</span>
                    </div>
                    
                    <div class="timeline-wrapper">
                        <?php if (empty($slots)): ?>
                            <p class="text-muted small text-center py-4 mb-0">No classes scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($slots as $sl): ?>
                                <div class="bg-light p-3 rounded-3 mb-2 border-start border-primary border-4 shadow-sm">
                                    <strong class="d-block text-dark" style="font-size:0.9rem;"><?= e($sl['subject_name']) ?></strong>
                                    <small class="text-muted font-monospace d-block" style="font-size:0.75rem;"><i class="far fa-clock me-1"></i><?= date('h:i A', strtotime($sl['start_time'])) ?> - <?= date('h:i A', strtotime($sl['end_time'])) ?></small>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                        <?php if ($role === 'student'): ?>
                                            <span><i class="fas fa-chalkboard-teacher me-1"></i><?= e($sl['teacher_name'] ?: 'TBA') ?></span>
                                        <?php else: ?>
                                            <span><i class="fas fa-school me-1"></i><?= e($sl['class_name']) ?>-<?= e($sl['section']) ?></span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary-light text-dark"><i class="fas fa-door-open me-1"></i><?= e($sl['classroom']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

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
}
</style>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
