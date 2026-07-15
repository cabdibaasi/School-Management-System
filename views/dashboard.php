<?php
/**
 * Role-Based User Dashboard
 */
$pageTitle = "Dashboard";
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . 'views/layouts/header.php';

$db        = Database::connect();
$role      = Auth::role();
$userId    = Auth::id();
$profileId = Auth::profileId();

// ── Calendar helper ──────────────────────────────────────────
function buildCalendar() {
    $date      = new DateTime();
    $year      = $date->format('Y');
    $month     = $date->format('m');
    $monthName = $date->format('F');
    $firstDay  = new DateTime("$year-$month-01");
    $daysInMonth = (int)$date->format('t');
    $dayOfWeek   = (int)$firstDay->format('w');
    $today       = (int)$date->format('d');

    $html  = '<div class="cal-wrap">';
    $html .= '<div class="cal-header"><span class="cal-title"><i class="far fa-calendar-alt me-2"></i>' . $monthName . ' ' . $year . '</span></div>';
    $html .= '<table class="cal-table"><thead><tr>';
    foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d) {
        $html .= '<th>' . $d . '</th>';
    }
    $html .= '</tr></thead><tbody><tr>';

    for ($i = 0; $i < $dayOfWeek; $i++) $html .= '<td></td>';

    $col = $dayOfWeek;
    for ($n = 1; $n <= $daysInMonth; $n++) {
        if ($col === 7) { $html .= '</tr><tr>'; $col = 0; }
        $cls = ($n === $today) ? ' cal-today' : '';
        $html .= '<td><span class="cal-day' . $cls . '">' . $n . '</span></td>';
        $col++;
    }
    while ($col < 7) { $html .= '<td></td>'; $col++; }
    $html .= '</tr></tbody></table></div>';
    return $html;
}

// ── Data Aggregation ─────────────────────────────────────────
if ($role === 'admin') {
    $countStudents   = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $countTeachers   = $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $countClasses    = $db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    $countSubjects   = $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $countParents    = $db->query("SELECT COUNT(DISTINCT parent_phone) FROM students")->fetchColumn();
    $studentAtt      = $db->query("SELECT COUNT(*) FROM student_attendance")->fetchColumn();
    $teacherAtt      = $db->query("SELECT COUNT(*) FROM teacher_attendance")->fetchColumn();
    $countAttendance = $studentAtt + $teacherAtt;
    $countExams      = $db->query("SELECT COUNT(*) FROM exams")->fetchColumn();
    $countBooks      = $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
    $totalPayments      = $db->query("SELECT IFNULL(SUM(amount_paid),0) FROM fee_payments")->fetchColumn();
    $totalSalariesPaid  = $db->query("SELECT IFNULL(SUM(amount),0) FROM teacher_payments")->fetchColumn();
    $netFunds           = $totalPayments - $totalSalariesPaid;
    $totalFeesInvoiced  = $db->query("SELECT IFNULL(SUM(amount),0) FROM fees")->fetchColumn();
    $totalOutstanding   = max(0, $totalFeesInvoiced - $totalPayments);
    $recentStudents  = $db->query("SELECT s.*,c.class_name,c.section FROM students s LEFT JOIN classes c ON s.class_id=c.id ORDER BY s.id DESC LIMIT 5")->fetchAll();
    $recentPayments  = $db->query("SELECT fp.*,f.fee_type,s.first_name,s.last_name FROM fee_payments fp JOIN fees f ON fp.fee_id=f.id JOIN students s ON f.student_id=s.id ORDER BY fp.id DESC LIMIT 5")->fetchAll();
    $recentAttendance= $db->query("SELECT sa.*,s.first_name,s.last_name,c.class_name,c.section FROM student_attendance sa JOIN students s ON sa.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id ORDER BY sa.id DESC LIMIT 5")->fetchAll();
    $announcements   = $db->query("SELECT * FROM announcements ORDER BY id DESC LIMIT 5")->fetchAll();

} elseif ($role === 'teacher') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE teacher_id=:tid");
    $stmt->execute(['tid' => $profileId]);
    $countSubjects = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(DISTINCT class_id) FROM subjects WHERE teacher_id=:tid");
    $stmt->execute(['tid' => $profileId]);
    $countClasses = $stmt->fetchColumn();

    $todayDate = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) FROM teacher_attendance WHERE teacher_id=:tid AND date=:date AND status='present'");
    $stmt->execute(['tid' => $profileId, 'date' => $todayDate]);
    $isPresenter   = $stmt->fetchColumn() > 0 ? 'Present' : 'Not Marked';
    $announcements = $db->query("SELECT * FROM announcements WHERE target_role IN ('all','teacher') ORDER BY id DESC LIMIT 5")->fetchAll();

} elseif ($role === 'student') {
    $stmt = $db->prepare("SELECT s.*,c.class_name,c.section FROM students s LEFT JOIN classes c ON s.class_id=c.id WHERE s.id=:sid");
    $stmt->execute(['sid' => $profileId]);
    $studentInfo = $stmt->fetch();

    $stmt = $db->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id=:sid");
    $stmt->execute(['sid' => $profileId]);
    $totalAttDays = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM student_attendance WHERE student_id=:sid AND status='present'");
    $stmt->execute(['sid' => $profileId]);
    $presentAttDays = $stmt->fetchColumn();
    $attPercentage  = $totalAttDays > 0 ? round(($presentAttDays / $totalAttDays) * 100, 1) : 100;

    $stmt = $db->prepare("SELECT IFNULL(SUM(amount),0) FROM fees WHERE student_id=:sid");
    $stmt->execute(['sid' => $profileId]);
    $studentInvoiced = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT IFNULL(SUM(fp.amount_paid),0) FROM fee_payments fp JOIN fees f ON fp.fee_id=f.id WHERE f.student_id=:sid");
    $stmt->execute(['sid' => $profileId]);
    $studentPaid    = $stmt->fetchColumn();
    $studentBalance = $studentInvoiced - $studentPaid;

    $stmt = $db->prepare("SELECT COUNT(*) FROM book_borrows WHERE student_id=:sid AND status='borrowed'");
    $stmt->execute(['sid' => $profileId]);
    $borrowedBooks = $stmt->fetchColumn();
    $announcements = $db->query("SELECT * FROM announcements WHERE target_role IN ('all','student') ORDER BY id DESC LIMIT 5")->fetchAll();
}
?>

<style>
/* ── Dashboard Design System (matches index.php / login.php) ── */
:root {
    --d-blue-900: #1e3a8a;
    --d-blue-700: #1d4ed8;
    --d-blue-600: #2563eb;
    --d-blue-500: #3b82f6;
    --d-blue-100: #dbeafe;
    --d-blue-50:  #eff6ff;
    --d-green:    #16a34a;
    --d-green-bg: #f0fdf4;
    --d-red:      #dc2626;
    --d-red-bg:   #fef2f2;
    --d-amber:    #d97706;
    --d-amber-bg: #fffbeb;
    --d-purple:   #7c3aed;
    --d-purple-bg:#f5f3ff;
    --d-cyan:     #0891b2;
    --d-cyan-bg:  #ecfeff;
}
[data-theme="dark"] {
    --d-blue-50:  #1e293b;
    --d-blue-100: #1e293b;
    --d-green-bg: #052e16;
    --d-red-bg:   #2d0f0f;
    --d-amber-bg: #1c1400;
    --d-purple-bg:#1e0a3c;
    --d-cyan-bg:  #071c22;
}

/* ── Welcome Banner ── */
.dash-banner {
    background: linear-gradient(135deg, var(--d-blue-900) 0%, var(--d-blue-700) 60%, #1e40af 100%);
    border-radius: 16px;
    padding: clamp(20px,3vw,32px) clamp(20px,3vw,32px);
    color: #fff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(30,58,138,0.18);
}
.dash-banner::before {
    content: '';
    position: absolute; top: -60%; right: -10%;
    width: 320px; height: 320px; border-radius: 50%;
    background: rgba(255,255,255,0.05); pointer-events: none;
}
.dash-banner::after {
    content: '';
    position: absolute; bottom: -40%; left: -5%;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,0.04); pointer-events: none;
}
.dash-banner-label {
    font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px;
    color: rgba(255,255,255,0.6); margin-bottom: 6px;
}
.dash-banner-title {
    font-size: clamp(1.2rem,2.5vw,1.75rem);
    font-weight: 800; color: #fff; margin-bottom: 4px;
}
.dash-banner-sub {
    font-size: 0.85rem; color: rgba(255,255,255,0.65);
}
.dash-banner-date {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 100px;
    padding: 7px 18px;
    font-size: 0.82rem; font-weight: 600;
    color: rgba(255,255,255,0.9);
    backdrop-filter: blur(8px);
    white-space: nowrap;
}

/* ── Section heading ── */
.dash-section-label {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: var(--text-muted);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.dash-section-label::after {
    content: ''; flex: 1; height: 1px; background: var(--border-color);
}

/* ── Metric Cards ── */
.metric-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.08); }
.metric-card::before {
    content: ''; position: absolute;
    bottom: 0; left: 0; right: 0; height: 3px;
    border-radius: 0 0 14px 14px;
}
.mc-blue::before   { background: var(--d-blue-600); }
.mc-green::before  { background: var(--d-green); }
.mc-red::before    { background: var(--d-red); }
.mc-amber::before  { background: var(--d-amber); }
.mc-purple::before { background: var(--d-purple); }
.mc-cyan::before   { background: var(--d-cyan); }

.metric-label {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.8px;
    text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;
}
.metric-value {
    font-size: clamp(1.5rem,3vw,2rem);
    font-weight: 800; color: var(--text-heading); line-height: 1;
}
.metric-value.sm { font-size: clamp(1rem,2vw,1.3rem); }
.metric-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.mi-blue   { background: var(--d-blue-50);   color: var(--d-blue-600); }
.mi-green  { background: var(--d-green-bg);  color: var(--d-green); }
.mi-red    { background: var(--d-red-bg);    color: var(--d-red); }
.mi-amber  { background: var(--d-amber-bg);  color: var(--d-amber); }
.mi-purple { background: var(--d-purple-bg); color: var(--d-purple); }
.mi-cyan   { background: var(--d-cyan-bg);   color: var(--d-cyan); }

/* ── Section Cards (charts, calendar, lists) ── */
.section-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: clamp(16px,2vw,24px);
    height: 100%;
}
.section-card-title {
    font-size: 0.88rem; font-weight: 700;
    color: var(--text-heading);
    display: flex; align-items: center; gap: 8px;
    padding-bottom: 14px; margin-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}
.section-card-title i { color: var(--d-blue-600); }

/* ── Calendar ── */
.cal-wrap { width: 100%; }
.cal-header { margin-bottom: 12px; }
.cal-title { font-size: 0.88rem; font-weight: 700; color: var(--text-heading); }
.cal-table { width: 100%; border-collapse: separate; border-spacing: 3px; }
.cal-table th {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px;
    text-transform: uppercase; text-align: center;
    color: var(--text-muted); padding: 4px 0;
}
.cal-table td { text-align: center; padding: 2px; }
.cal-day {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 50%;
    font-size: 0.8rem; color: var(--text-body);
    cursor: pointer; transition: background 0.15s;
}
.cal-day:hover { background: var(--d-blue-50); color: var(--d-blue-600); }
.cal-today {
    background: linear-gradient(135deg, var(--d-blue-900), var(--d-blue-600));
    color: #fff !important; font-weight: 700;
    box-shadow: 0 3px 10px rgba(37,99,235,0.3);
}

/* ── Quick actions ── */
.quick-action-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 14px; border-radius: 10px;
    border: 1.5px solid var(--border-color);
    background: var(--card-bg);
    color: var(--text-body); text-decoration: none;
    font-size: 0.83rem; font-weight: 600;
    transition: all 0.2s;
}
.quick-action-btn:hover {
    border-color: var(--d-blue-500);
    background: var(--d-blue-50);
    color: var(--d-blue-700);
    transform: translateX(4px);
}
.qa-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}

/* ── List rows ── */
.dash-list-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
    gap: 8px;
}
.dash-list-item:last-child { border-bottom: none; padding-bottom: 0; }
.dash-list-name { font-size: 0.85rem; font-weight: 600; color: var(--text-heading); }
.dash-list-sub  { font-size: 0.75rem; color: var(--text-muted); margin-top: 1px; }

/* ── Status badges ── */
.status-badge {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.3px;
    padding: 3px 10px; border-radius: 100px; flex-shrink: 0;
    text-transform: capitalize;
}
.sb-present  { background: var(--d-green-bg);  color: var(--d-green); }
.sb-absent   { background: var(--d-red-bg);    color: var(--d-red); }
.sb-late     { background: var(--d-amber-bg);  color: var(--d-amber); }
.sb-excused  { background: var(--d-cyan-bg);   color: var(--d-cyan); }
.sb-paid     { background: var(--d-green-bg);  color: var(--d-green); }

/* ── Responsive ── */
@media (max-width: 575px) {
    .dash-banner { flex-direction: column !important; gap: 14px; }
    .metric-value { font-size: 1.5rem; }
}
</style>

<!-- ── Welcome Banner ─────────────────────────────────── -->
<div class="dash-banner d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div style="position:relative;z-index:2;">
        <div class="dash-banner-label">Dashboard Overview</div>
        <div class="dash-banner-title">Welcome back, <?= e(Auth::displayName()) ?>! 👋</div>
        <div class="dash-banner-sub">
            <?php if ($role === 'admin'): ?>
                Manage your school classes, students and finances all in one place.
            <?php elseif ($role === 'teacher'): ?>
                Track your subjects, classes and attendance from here.
            <?php else: ?>
                View your class info, fees and school notices here.
            <?php endif; ?>
        </div>
    </div>
    <div class="dash-banner-date" style="position:relative;z-index:2;">
        <i class="far fa-clock me-2"></i><?= date('l, F d, Y') ?>
    </div>
</div>

<?php /* ═══════════════════════════════════════════════════════
   ADMIN DASHBOARD
═══════════════════════════════════════════════════════ */ ?>
<?php if ($role === 'admin'): ?>

<!-- People metrics -->
<div class="dash-section-label"><i class="fas fa-users text-primary"></i> People & Classes</div>
<div class="row g-3 mb-4">
    <!-- Students -->
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-blue">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Students</div>
                    <div class="metric-value"><?= $countStudents ?></div>
                </div>
                <div class="metric-icon mi-blue"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>
    </div>
    <!-- Teachers -->
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-cyan">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Teachers</div>
                    <div class="metric-value"><?= $countTeachers ?></div>
                </div>
                <div class="metric-icon mi-cyan"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
        </div>
    </div>
    <!-- Classes -->
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-green">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Classes</div>
                    <div class="metric-value"><?= $countClasses ?></div>
                </div>
                <div class="metric-icon mi-green"><i class="fas fa-school"></i></div>
            </div>
        </div>
    </div>
    <!-- Subjects -->
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-amber">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Subjects</div>
                    <div class="metric-value"><?= $countSubjects ?></div>
                </div>
                <div class="metric-icon mi-amber"><i class="fas fa-book"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Activity metrics -->
<div class="dash-section-label"><i class="fas fa-chart-bar text-primary"></i> Activity</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-purple">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Parents</div>
                    <div class="metric-value"><?= $countParents ?></div>
                </div>
                <div class="metric-icon mi-purple"><i class="fas fa-user-friends"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <a href="<?= BASE_URL ?>views/attendance/report.php" style="text-decoration:none;">
        <div class="metric-card mc-blue">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Attendance Log</div>
                    <div class="metric-value"><?= $countAttendance ?></div>
                </div>
                <div class="metric-icon mi-blue"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-purple">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Exams Held</div>
                    <div class="metric-value"><?= $countExams ?></div>
                </div>
                <div class="metric-icon mi-purple"><i class="fas fa-file-signature"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-cyan">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Library Books</div>
                    <div class="metric-value"><?= $countBooks ?></div>
                </div>
                <div class="metric-icon mi-cyan"><i class="fas fa-book-open"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Finance metrics -->
<div class="dash-section-label"><i class="fas fa-coins text-primary"></i> Financial Status</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-green">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Fees Collected</div>
                    <div class="metric-value sm"><?= Utility::formatCurrency($totalPayments, Setting::get('currency','USD')) ?></div>
                </div>
                <div class="metric-icon mi-green"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-red">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Salaries Paid</div>
                    <div class="metric-value sm"><?= Utility::formatCurrency($totalSalariesPaid, Setting::get('currency','USD')) ?></div>
                </div>
                <div class="metric-icon mi-red"><i class="fas fa-university"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-blue">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Net Cash Balance</div>
                    <div class="metric-value sm"><?= Utility::formatCurrency($netFunds, Setting::get('currency','USD')) ?></div>
                </div>
                <div class="metric-icon mi-blue"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card mc-amber">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Outstanding Fees</div>
                    <div class="metric-value sm"><?= Utility::formatCurrency($totalOutstanding, Setting::get('currency','USD')) ?></div>
                </div>
                <div class="metric-icon mi-amber"><i class="fas fa-comments-dollar"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart + Calendar + Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="section-card">
            <div class="section-card-title">
                <i class="fas fa-chart-bar"></i> Enrollment & Financial Overview
            </div>
            <div style="height:280px;position:relative;">
                <canvas id="dashboardChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-calendar-alt"></i> School Calendar</div>
            <?= buildCalendar() ?>
            <div style="margin-top:18px;border-top:1px solid var(--border-color);padding-top:16px;">
                <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px;">Quick Actions</div>
                <div class="d-flex flex-column gap-2">
                    <a href="<?= BASE_URL ?>views/students/create.php" class="quick-action-btn">
                        <span class="qa-icon mi-blue"><i class="fas fa-user-plus"></i></span> Enroll New Student
                    </a>
                    <a href="<?= BASE_URL ?>views/fees/collect.php" class="quick-action-btn">
                        <span class="qa-icon mi-green"><i class="fas fa-file-invoice-dollar"></i></span> Collect Fee
                    </a>
                    <a href="<?= BASE_URL ?>views/announcements/create.php" class="quick-action-btn">
                        <span class="qa-icon mi-amber"><i class="fas fa-bullhorn"></i></span> Post Announcement
                    </a>
                    <a href="<?= BASE_URL ?>views/attendance/report.php" class="quick-action-btn">
                        <span class="qa-icon mi-cyan"><i class="fas fa-chart-bar"></i></span> Attendance Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent lists -->
<div class="dash-section-label"><i class="fas fa-history text-primary"></i> Recent Activity</div>
<div class="row g-3">
    <!-- Recent Students -->
    <div class="col-md-6 col-xl-3">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-user-graduate"></i> Recent Enrolled</div>
            <?php if (empty($recentStudents)): ?>
                <p class="text-muted small text-center py-3">No students enrolled yet.</p>
            <?php else: ?>
                <?php foreach ($recentStudents as $std): ?>
                    <div class="dash-list-item">
                        <div>
                            <div class="dash-list-name"><?= e($std['first_name'].' '.$std['last_name']) ?></div>
                            <div class="dash-list-sub">Class <?= e($std['class_name'].'-'.$std['section']) ?> · <?= e($std['admission_number']) ?></div>
                        </div>
                        <span class="status-badge sb-present">New</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6 col-xl-3">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-file-invoice-dollar"></i> Recent Payments</div>
            <?php if (empty($recentPayments)): ?>
                <p class="text-muted small text-center py-3">No payment records yet.</p>
            <?php else: ?>
                <?php foreach ($recentPayments as $pay): ?>
                    <div class="dash-list-item">
                        <div>
                            <div class="dash-list-name"><?= e($pay['first_name'].' '.$pay['last_name']) ?></div>
                            <div class="dash-list-sub"><?= e($pay['fee_type']) ?> · <?= e($pay['payment_method']) ?></div>
                        </div>
                        <span class="status-badge sb-paid"><?= Utility::formatCurrency($pay['amount_paid'], Setting::get('currency','USD')) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="col-md-6 col-xl-3">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-clipboard-check"></i> Recent Attendance</div>
            <?php if (empty($recentAttendance)): ?>
                <p class="text-muted small text-center py-3">No attendance logs yet.</p>
            <?php else: ?>
                <?php foreach ($recentAttendance as $att):
                    $sbClass = 'sb-' . $att['status'];
                ?>
                    <div class="dash-list-item">
                        <div>
                            <div class="dash-list-name"><?= e($att['first_name'].' '.$att['last_name']) ?></div>
                            <div class="dash-list-sub">Class <?= e($att['class_name'].'-'.$att['section']) ?></div>
                        </div>
                        <span class="status-badge <?= $sbClass ?>"><?= ucfirst($att['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notice Board -->
    <div class="col-md-6 col-xl-3">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-bullhorn"></i> Notice Board</div>
            <?php if (empty($announcements)): ?>
                <p class="text-muted small text-center py-3">No notices currently.</p>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="dash-list-item" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <div class="dash-list-name"><?= e($ann['title']) ?></div>
                        <div class="dash-list-sub"><i class="far fa-clock me-1"></i><?= date('M d, Y', strtotime($ann['created_at'])) ?> · <?= ucfirst($ann['target_role']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php /* ═══════════════════════════════════════════════════════
   TEACHER DASHBOARD
═══════════════════════════════════════════════════════ */ ?>
<?php elseif ($role === 'teacher'): ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card mc-blue">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Subjects Taught</div>
                    <div class="metric-value"><?= $countSubjects ?></div>
                </div>
                <div class="metric-icon mi-blue"><i class="fas fa-book"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card mc-cyan">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">My Classes</div>
                    <div class="metric-value"><?= $countClasses ?></div>
                </div>
                <div class="metric-icon mi-cyan"><i class="fas fa-school"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card mc-green">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Today Attendance</div>
                    <div class="metric-value" style="font-size:1.3rem;"><?= $isPresenter ?></div>
                </div>
                <div class="metric-icon mi-green"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-calendar-day"></i> My Schedule & Calendar</div>
            <?= buildCalendar() ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-bullhorn"></i> Staff Notifications</div>
            <?php if (empty($announcements)): ?>
                <p class="text-muted small text-center py-4">No announcements at this time.</p>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="dash-list-item" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <div class="dash-list-name"><?= e($ann['title']) ?></div>
                        <div class="dash-list-sub" style="line-height:1.5;"><?= e($ann['content']) ?></div>
                        <div class="dash-list-sub"><i class="far fa-clock me-1"></i><?= date('M d, Y H:i', strtotime($ann['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php /* ═══════════════════════════════════════════════════════
   STUDENT DASHBOARD
═══════════════════════════════════════════════════════ */ ?>
<?php elseif ($role === 'student'): ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card mc-blue">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">My Grade Class</div>
                    <div class="metric-value sm"><?= e(($studentInfo['class_name'] ?? 'N/A').' - '.($studentInfo['section'] ?? '')) ?></div>
                </div>
                <div class="metric-icon mi-blue"><i class="fas fa-graduation-cap"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card <?= $attPercentage < 75 ? 'mc-red' : 'mc-green' ?>">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Attendance Level</div>
                    <div class="metric-value"><?= $attPercentage ?>%</div>
                </div>
                <div class="metric-icon <?= $attPercentage < 75 ? 'mi-red' : 'mi-green' ?>"><i class="fas fa-percent"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card mc-purple">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="metric-label">Borrowed Books</div>
                    <div class="metric-value"><?= $borrowedBooks ?></div>
                </div>
                <div class="metric-icon mi-purple"><i class="fas fa-book-open"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Fees summary -->
    <div class="col-lg-4">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-file-invoice-dollar"></i> Fees Invoice Summary</div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;">Total Invoiced</div>
                    <div style="font-size:1.25rem;font-weight:800;color:var(--text-heading);"><?= Utility::formatCurrency($studentInvoiced, Setting::get('currency','USD')) ?></div>
                </div>
                <div>
                    <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;">Total Paid</div>
                    <div style="font-size:1.25rem;font-weight:800;color:var(--d-green);"><?= Utility::formatCurrency($studentPaid, Setting::get('currency','USD')) ?></div>
                </div>
                <div style="border-top:1px solid var(--border-color);padding-top:14px;">
                    <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;">Outstanding Balance</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--d-red);"><?= Utility::formatCurrency($studentBalance, Setting::get('currency','USD')) ?></div>
                </div>
                <a href="<?= BASE_URL ?>views/fees/my_fees.php" class="quick-action-btn">
                    <span class="qa-icon mi-blue"><i class="fas fa-eye"></i></span> View Billing Details
                </a>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="col-lg-4">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-calendar-day"></i> Calendar</div>
            <?= buildCalendar() ?>
        </div>
    </div>

    <!-- Notice Board -->
    <div class="col-lg-4">
        <div class="section-card">
            <div class="section-card-title"><i class="fas fa-bullhorn"></i> Student Notice Board</div>
            <?php if (empty($announcements)): ?>
                <p class="text-muted small text-center py-4">No notices posted.</p>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="dash-list-item" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <div class="dash-list-name"><?= e($ann['title']) ?></div>
                        <div class="dash-list-sub" style="line-height:1.5;"><?= e(substr($ann['content'],0,100)) ?>...</div>
                        <div class="dash-list-sub"><i class="far fa-clock me-1"></i><?= date('M d, Y', strtotime($ann['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($role === 'admin'): ?>
    const ctx = document.getElementById('dashboardChart');
    if (ctx) {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Students', 'Teachers', 'Classes', 'Subjects', 'Exams', 'Books'],
                datasets: [{
                    label: 'Count',
                    data: [<?= $countStudents ?>, <?= $countTeachers ?>, <?= $countClasses ?>, <?= $countSubjects ?>, <?= $countExams ?>, <?= $countBooks ?>],
                    backgroundColor: [
                        'rgba(37,99,235,0.85)',
                        'rgba(8,145,178,0.85)',
                        'rgba(22,163,74,0.85)',
                        'rgba(217,119,6,0.85)',
                        'rgba(124,58,237,0.85)',
                        'rgba(14,165,233,0.85)'
                    ],
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Inter', size: 11 } } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0, color: textColor, font: { family: 'Inter', size: 11 } } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#fff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#94a3b8' : '#64748b',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
