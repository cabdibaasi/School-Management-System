<?php
/**
 * Student Attendance History Page
 */
$pageTitle = "My Attendance History";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('student');

$db = Database::connect();
$profileId = Auth::profileId();

// Aggregations
$presents = $db->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id = :sid AND status = 'present'");
$presents->execute(['sid' => $profileId]);
$numPresents = (int)$presents->fetchColumn();

$absents = $db->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id = :sid AND status = 'absent'");
$absents->execute(['sid' => $profileId]);
$numAbsents = (int)$absents->fetchColumn();

$lates = $db->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id = :sid AND status = 'late'");
$lates->execute(['sid' => $profileId]);
$numLates = (int)$lates->fetchColumn();

$excused = $db->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id = :sid AND status = 'excused'");
$excused->execute(['sid' => $profileId]);
$numExcused = (int)$excused->fetchColumn();

$totalMarked = $numPresents + $numAbsents + $numLates + $numExcused;
$rate = ($totalMarked > 0) ? round(($numPresents / $totalMarked) * 100, 1) : 100;

// Fetch log
$stmt = $db->prepare("SELECT * FROM student_attendance WHERE student_id = :sid ORDER BY date DESC");
$stmt->execute(['sid' => $profileId]);
$logs = $stmt->fetchAll();

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">My Attendance</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-calendar-check me-2"></i>My Attendance Directory</h2>

<div class="row g-3 mb-4">
    <!-- Rate -->
    <div class="col-6 col-md-3">
        <div class="card metric-card p-3">
            <span class="text-muted small fw-bold">ATTENDANCE RATE</span>
            <h3 class="fw-bold mb-0 mt-1 <?= ($rate < 75) ? 'text-danger' : 'text-success' ?>"><?= $rate ?>%</h3>
        </div>
    </div>
    <!-- Present -->
    <div class="col-6 col-md-2">
        <div class="card metric-card p-3">
            <span class="text-muted small fw-bold">PRESENT DAYS</span>
            <h3 class="fw-bold mb-0 mt-1 text-success"><?= $numPresents ?></h3>
        </div>
    </div>
    <!-- Late -->
    <div class="col-6 col-md-2">
        <div class="card metric-card p-3">
            <span class="text-muted small fw-bold">LATE DAYS</span>
            <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $numLates ?></h3>
        </div>
    </div>
    <!-- Excused -->
    <div class="col-6 col-md-2">
        <div class="card metric-card p-3">
            <span class="text-muted small fw-bold">EXCUSED DAYS</span>
            <h3 class="fw-bold mb-0 mt-1 text-info"><?= $numExcused ?></h3>
        </div>
    </div>
    <!-- Absent -->
    <div class="col-6 col-md-3">
        <div class="card metric-card p-3">
            <span class="text-muted small fw-bold">ABSENT DAYS</span>
            <h3 class="fw-bold mb-0 mt-1 text-danger"><?= $numAbsents ?></h3>
        </div>
    </div>
</div>

<!-- Log Grid Card -->
<div class="card section-card">
    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-history me-2"></i>Attendance Log Timeline</h5>
    
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day of Week</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No attendance marked yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $badge = 'bg-success';
                        if ($log['status'] === 'absent') $badge = 'bg-danger';
                        if ($log['status'] === 'late') $badge = 'bg-warning text-dark';
                        if ($log['status'] === 'excused') $badge = 'bg-info text-dark';
                        
                        $dayStr = date('l', strtotime($log['date']));
                        ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= e($log['date']) ?></td>
                            <td><?= $dayStr ?></td>
                            <td><span class="badge <?= $badge ?> px-3 py-1 rounded-pill"><?= ucfirst($log['status']) ?></span></td>
                            <td class="text-muted small"><?= e($log['remarks'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
