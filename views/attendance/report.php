<?php
/**
 * Monthly Student Attendance Report Page
 */
$pageTitle = "Attendance Reports";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher']);

$db = Database::connect();
$classes = SchoolClass::getAll();

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');

$summary = [];
if ($classId > 0 && !empty($month) && !empty($year)) {
    // Pad month
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $summary = Attendance::getStudentMonthlySummary($classId, $year, $month);
}

// Handle Export triggers
if (isset($_GET['export']) && $classId > 0) {
    $selectedClass = SchoolClass::getById($classId);
    $classNameStr = $selectedClass ? ($selectedClass['class_name'] . '-' . $selectedClass['section']) : 'Class';
    $monthName = date('F', mktime(0, 0, 0, (int)$month, 10));
    $titleStr = "Attendance Report - {$classNameStr} ({$monthName} {$year})";
    
    $headers = ['Admission No', 'Student Name', 'Present Days', 'Absent Days', 'Late Days', 'Excused Days', 'Total Marked', 'Attendance Rate'];
    
    $data = [];
    foreach ($summary as $s) {
        $rate = ($s['total_marked'] > 0) ? round(($s['presents'] / $s['total_marked']) * 100, 1) . '%' : '100%';
        $data[] = [
            $s['admission_number'],
            $s['first_name'] . ' ' . $s['last_name'],
            $s['presents'],
            $s['absents'],
            $s['lates'],
            $s['excused'],
            $s['total_marked'],
            $rate
        ];
    }
    
    if ($_GET['export'] === 'csv') {
        $filename = 'Attendance_' . $classNameStr . '_' . $month . '_' . $year;
        ExportHelper::toCSV($filename, $headers, $data);
    } elseif ($_GET['export'] === 'print') {
        ExportHelper::renderPrintLayout($titleStr, $headers, $data);
    }
}

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/attendance/index.php">Attendance</a></li>
        <li class="breadcrumb-item active" aria-current="page">Attendance Reports</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-chart-bar me-2"></i>Monthly Attendance Reports</h2>
    <?php if ($classId > 0 && !empty($summary)): ?>
        <div>
            <a href="?export=csv&class_id=<?= $classId ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-success me-1 rounded-3">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
            <a href="?export=print&class_id=<?= $classId ?>&month=<?= $month ?>&year=<?= $year ?>" target="_blank" class="btn btn-outline-dark rounded-3">
                <i class="fas fa-print me-1"></i> Print / PDF
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Selector panel -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/attendance/report.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <select class="form-select form-control-custom" name="class_id" required>
                <option value="">-- Choose Class --</option>
                <?php foreach ($classes as $cls): ?>
                    <option value="<?= $cls['id'] ?>" <?= ($classId === $cls['id']) ? 'selected' : '' ?>>
                        <?= e($cls['class_name']) ?> - <?= e($cls['section']) ?> (Academic: <?= e($cls['academic_year']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select form-control-custom" name="month" required>
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $monthVal = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $monthName = date('F', mktime(0, 0, 0, $m, 10));
                    $selected = ($month === $monthVal) ? 'selected' : '';
                    echo "<option value='{$monthVal}' {$selected}>{$monthName}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select form-control-custom" name="year" required>
                <?php
                $currYear = (int)date('Y');
                for ($y = $currYear - 3; $y <= $currYear + 1; $y++) {
                    $selected = ($year === $y) ? 'selected' : '';
                    echo "<option value='{$y}' {$selected}>{$y}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary btn-primary-custom text-white"><i class="fas fa-file-invoice me-1"></i> Get Report</button>
        </div>
    </form>
</div>

<!-- Report Summary Sheet -->
<?php if ($classId > 0): ?>
    <div class="card section-card">
        <h5 class="fw-bold mb-3 text-secondary-color">
            <i class="far fa-clipboard me-2"></i>Summary Sheet
        </h5>
        
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Admission #</th>
                        <th>Student Name</th>
                        <th class="text-center text-success">Present (P)</th>
                        <th class="text-center text-danger">Absent (A)</th>
                        <th class="text-center text-warning">Late (L)</th>
                        <th class="text-center text-info">Excused (E)</th>
                        <th class="text-center">Total Classes</th>
                        <th class="text-end">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No attendance marked for this period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summary as $s): 
                            $rate = ($s['total_marked'] > 0) ? round(($s['presents'] / $s['total_marked']) * 100, 1) : 100;
                            $rateColor = ($rate < 75) ? 'text-danger fw-bold' : 'text-success';
                            ?>
                            <tr>
                                <td class="font-monospace text-muted small"><?= e($s['admission_number']) ?></td>
                                <td><strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong></td>
                                <td class="text-center text-success fw-bold"><?= $s['presents'] ?></td>
                                <td class="text-center text-danger fw-bold"><?= $s['absents'] ?></td>
                                <td class="text-center text-warning fw-bold"><?= $s['lates'] ?></td>
                                <td class="text-center text-info fw-bold"><?= $s['excused'] ?></td>
                                <td class="text-center"><?= $s['total_marked'] ?></td>
                                <td class="text-end <?= $rateColor ?>"><?= $rate ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
