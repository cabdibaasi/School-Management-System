<?php
/**
 * Reports & Analytics Dashboard
 */
$pageTitle = "Reports & Analytics";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('admin');

$db = Database::connect();

// ── Aggregate Statistics ─────────────────────────────────────────────────────
$academicYear = $_GET['academic_year'] ?? Setting::get('current_academic_year', '2026-2027');

// Students overview
$stmtStudents = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male_count,
    SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female_count
    FROM students WHERE academic_year = :year");
$stmtStudents->execute(['year' => $academicYear]);
$studentStats = $stmtStudents->fetch();

// Teachers overview
$teacherStats = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_count FROM teachers")->fetch();

// Attendance this month
$currentMonth = date('Y-m');
$stmtAtt = $db->prepare("SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM student_attendance WHERE DATE_FORMAT(date, '%Y-%m') = :month");
$stmtAtt->execute(['month' => $currentMonth]);
$attStats = $stmtAtt->fetch();
$attRate = ($attStats['total_records'] > 0)
    ? round(($attStats['present_count'] / $attStats['total_records']) * 100, 1)
    : 0;

// Fee summary
$feeSummary = Fee::getSummary($academicYear);

// Library stats
$libStats = Book::getStats();

// Classes with student counts
$stmtClasses = $db->prepare("SELECT c.class_name, c.section, COUNT(s.id) as student_count
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.id AND s.status = 'active' AND s.academic_year = :year
    GROUP BY c.id ORDER BY c.class_name ASC");
$stmtClasses->execute(['year' => $academicYear]);
$classCounts = $stmtClasses->fetchAll();

// Attendance breakdown by status over last 30 days (for pie chart)
$stmtPie = $db->prepare("SELECT status, COUNT(*) as cnt FROM student_attendance 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY status");
$stmtPie->execute();
$attBreakdown = $stmtPie->fetchAll(\PDO::FETCH_KEY_PAIR);

// Monthly attendance trend (last 6 months)
$stmtTrend = $db->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as month,
    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent
    FROM student_attendance
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC");
$stmtTrend->execute();
$attTrend = $stmtTrend->fetchAll();

// Fee collection vs billed by month (last 6 months)
$stmtFeeChart = $db->prepare("SELECT DATE_FORMAT(f.created_at, '%Y-%m') as month,
    SUM(f.amount) as billed,
    COALESCE(SUM(p.amount_paid), 0) as collected
    FROM fees f
    LEFT JOIN fee_payments p ON f.id = p.fee_id
    WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND f.academic_year = :year
    GROUP BY month ORDER BY month ASC");
$stmtFeeChart->execute(['year' => $academicYear]);
$feeChart = $stmtFeeChart->fetchAll();

// Top performers (avg % across exams)
$stmtTopPerfomers = $db->prepare("SELECT s.first_name, s.last_name, s.admission_number,
    c.class_name, c.section,
    ROUND(AVG(m.marks_obtained / m.max_marks * 100), 1) as avg_pct,
    COUNT(DISTINCT m.exam_id) as exams_taken
    FROM students s
    JOIN marks m ON s.id = m.student_id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.academic_year = :year AND s.status = 'active'
    GROUP BY s.id
    ORDER BY avg_pct DESC
    LIMIT 10");
$stmtTopPerfomers->execute(['year' => $academicYear]);
$topPerformers = $stmtTopPerfomers->fetchAll();

$currency  = Setting::get('currency', 'USD');

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Reports & Analytics</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="text-muted small mb-0">Academic Year:</label>
        <input type="text" name="academic_year" class="form-control form-control-custom" style="max-width:130px;" value="<?= e($academicYear) ?>">
        <button class="btn btn-primary btn-primary-custom text-white px-3" type="submit"><i class="fas fa-filter"></i></button>
    </form>
</div>

<!-- Summary KPI Cards -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['icon' => 'fas fa-user-graduate', 'label' => 'Total Students', 'value' => $studentStats['total'] ?? 0, 'sub' => 'Active: ' . ($studentStats['active_count'] ?? 0), 'color' => '#4361ee'],
        ['icon' => 'fas fa-chalkboard-teacher', 'label' => 'Total Teachers', 'value' => $teacherStats['total'] ?? 0, 'sub' => 'Active: ' . ($teacherStats['active_count'] ?? 0), 'color' => '#7209b7'],
        ['icon' => 'fas fa-calendar-check', 'label' => 'Monthly Attendance', 'value' => $attRate . '%', 'sub' => 'Present ' . ($attStats['present_count'] ?? 0) . ' / Absent ' . ($attStats['absent_count'] ?? 0), 'color' => '#06d6a0'],
        ['icon' => 'fas fa-file-invoice-dollar', 'label' => 'Total Billed', 'value' => $currency . ' ' . number_format($feeSummary['total_billed'] ?? 0, 2), 'sub' => 'Collected: ' . $currency . ' ' . number_format($feeSummary['total_collected'] ?? 0, 2), 'color' => '#f72585'],
        ['icon' => 'fas fa-book', 'label' => 'Library Books', 'value' => $libStats['total_books'] ?? 0, 'sub' => 'Borrowed: ' . ($libStats['currently_borrowed'] ?? 0), 'color' => '#fb8500'],
        ['icon' => 'fas fa-exclamation-triangle', 'label' => 'Outstanding Fees', 'value' => $currency . ' ' . number_format($feeSummary['total_outstanding'] ?? 0, 2), 'sub' => 'Unpaid invoices: ' . ($feeSummary['unpaid_count'] ?? 0), 'color' => '#ef233c'],
    ];
    foreach ($kpis as $kpi): ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card section-card text-center py-3 px-2 h-100">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;background:<?= $kpi['color'] ?>22;">
                <i class="<?= $kpi['icon'] ?>" style="color:<?= $kpi['color'] ?>;font-size:1.3rem;"></i>
            </div>
            <div class="fw-bold fs-6" style="color:<?= $kpi['color'] ?>"><?= $kpi['value'] ?></div>
            <div class="text-muted small"><?= $kpi['label'] ?></div>
            <div class="text-muted" style="font-size:0.7rem;"><?= $kpi['sub'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Attendance Trend Line Chart -->
    <div class="col-md-7">
        <div class="card section-card h-100 p-4">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-chart-line me-2"></i>Attendance Trend (Last 6 Months)</h5>
            <canvas id="attTrendChart" height="120"></canvas>
        </div>
    </div>
    <!-- Attendance Pie Chart -->
    <div class="col-md-5">
        <div class="card section-card h-100 p-4">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-chart-pie me-2"></i>Attendance Breakdown (30 Days)</h5>
            <canvas id="attPieChart" height="180"></canvas>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Fee Bar Chart -->
    <div class="col-md-7">
        <div class="card section-card h-100 p-4">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-chart-bar me-2"></i>Fee Collection vs Billed (Last 6 Months)</h5>
            <canvas id="feeBarChart" height="120"></canvas>
        </div>
    </div>
    <!-- Students by Gender Pie -->
    <div class="col-md-5">
        <div class="card section-card h-100 p-4">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-venus-mars me-2"></i>Students by Gender</h5>
            <canvas id="genderChart" height="180"></canvas>
        </div>
    </div>
</div>

<!-- Class Enrollment Table -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card section-card h-100 p-4">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-door-open me-2"></i>Class Enrollment</h5>
            <div class="table-responsive">
                <table class="table table-sm table-custom mb-0">
                    <thead><tr><th>Class</th><th>Section</th><th class="text-center">Students</th></tr></thead>
                    <tbody>
                        <?php if (empty($classCounts)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">No class data.</td></tr>
                        <?php else: ?>
                        <?php foreach ($classCounts as $cls): ?>
                        <tr>
                            <td><strong><?= e($cls['class_name']) ?></strong></td>
                            <td><?= e($cls['section']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill"><?= $cls['student_count'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="col-md-6">
        <div class="card section-card h-100 p-4">
            <h5 class="fw-bold mb-3 text-secondary-color"><i class="fas fa-trophy me-2"></i>Top 10 Academic Performers</h5>
            <div class="table-responsive">
                <table class="table table-sm table-custom mb-0">
                    <thead><tr><th>#</th><th>Student</th><th>Class</th><th class="text-center">Avg %</th></tr></thead>
                    <tbody>
                        <?php if (empty($topPerformers)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No exam data available.</td></tr>
                        <?php else: ?>
                        <?php foreach ($topPerformers as $rank => $perf):
                            $grade = Exam::calculateGrade($perf['avg_pct']);
                        ?>
                        <tr>
                            <td><span class="badge bg-<?= $rank < 3 ? 'warning' : 'secondary' ?>"><?= $rank + 1 ?></span></td>
                            <td>
                                <strong><?= e($perf['first_name'] . ' ' . $perf['last_name']) ?></strong>
                                <br><small class="text-muted font-monospace"><?= e($perf['admission_number']) ?></small>
                            </td>
                            <td><?= e($perf['class_name'] . ' ' . $perf['section']) ?></td>
                            <td class="text-center">
                                <strong class="text-primary"><?= $perf['avg_pct'] ?>%</strong>
                                <span class="badge bg-<?= $grade['status'] === 'Pass' ? 'success' : 'danger' ?> ms-1"><?= $grade['grade'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// ── Chart.js Data ─────────────────────────────────────────────────────────────
const attTrendData = {
    labels: [<?= implode(',', array_map(fn($r) => '"' . $r['month'] . '"', $attTrend)) ?>],
    present: [<?= implode(',', array_column($attTrend, 'present')) ?>],
    absent:  [<?= implode(',', array_column($attTrend, 'absent')) ?>]
};
const attPieData = {
    present: <?= $attBreakdown['present'] ?? 0 ?>,
    absent:  <?= $attBreakdown['absent'] ?? 0 ?>,
    late:    <?= $attBreakdown['late'] ?? 0 ?>,
    excused: <?= $attBreakdown['excused'] ?? 0 ?>
};
const feeData = {
    labels:    [<?= implode(',', array_map(fn($r) => '"' . $r['month'] . '"', $feeChart)) ?>],
    billed:    [<?= implode(',', array_column($feeChart, 'billed')) ?>],
    collected: [<?= implode(',', array_column($feeChart, 'collected')) ?>]
};
const genderData = {
    male:   <?= $studentStats['male_count'] ?? 0 ?>,
    female: <?= $studentStats['female_count'] ?? 0 ?>,
    other:  <?= (($studentStats['total'] ?? 0) - ($studentStats['male_count'] ?? 0) - ($studentStats['female_count'] ?? 0)) ?>
};

document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Attendance Trend Line
    new Chart(document.getElementById('attTrendChart'), {
        type: 'line',
        data: {
            labels: attTrendData.labels,
            datasets: [
                { label: 'Present', data: attTrendData.present, borderColor: '#06d6a0', backgroundColor: 'rgba(6,214,160,0.1)', tension: 0.4, fill: true },
                { label: 'Absent', data: attTrendData.absent, borderColor: '#ef233c', backgroundColor: 'rgba(239,35,60,0.1)', tension: 0.4, fill: true }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });

    // Attendance Pie
    new Chart(document.getElementById('attPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Excused'],
            datasets: [{ data: [attPieData.present, attPieData.absent, attPieData.late, attPieData.excused], backgroundColor: ['#06d6a0', '#ef233c', '#fb8500', '#4361ee'], borderWidth: 0 }]
        },
        options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });

    // Fee Bar Chart
    new Chart(document.getElementById('feeBarChart'), {
        type: 'bar',
        data: {
            labels: feeData.labels,
            datasets: [
                { label: 'Billed', data: feeData.billed, backgroundColor: 'rgba(67,97,238,0.7)', borderRadius: 6 },
                { label: 'Collected', data: feeData.collected, backgroundColor: 'rgba(6,214,160,0.7)', borderRadius: 6 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });

    // Gender Pie
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{ data: [genderData.male, genderData.female, genderData.other], backgroundColor: ['#4361ee', '#f72585', '#fb8500'], borderWidth: 0 }]
        },
        options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
