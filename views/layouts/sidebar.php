<?php
/**
 * Global Sidebar Layout (Role-Based Links)
 */
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$role = Auth::role();
?>
<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between py-3">
        <h3 class="mb-0 fw-bold d-flex align-items-center gap-2" style="font-size: 1.15rem;">
            <i class="fas fa-graduation-cap text-info"></i>
            <span style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">Talent Institute</span>
        </h3>
    </div>

    <ul class="list-unstyled components">
        <!-- Dashboard (Common) -->
        <li class="<?= ($currentScript == 'dashboard.php') ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>views/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <?php if ($role === 'admin'): ?>
            <!-- Student Management (Admin) -->
            <li class="<?= ($currentDir == 'students') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/students/index.php">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
            </li>

            <!-- Teacher Management (Admin) -->
            <li class="<?= ($currentDir == 'teachers' && $currentScript == 'index.php') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/teachers/index.php">
                    <i class="fas fa-chalkboard-teacher"></i> Teachers Directory
                </a>
            </li>

            <!-- Teacher Payroll (Admin) -->
            <li class="<?= ($currentDir == 'teachers' && $currentScript == 'payments.php') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/teachers/payments.php">
                    <i class="fas fa-hand-holding-usd"></i> Teacher Payroll
                </a>
            </li>

            <!-- Classes Management (Admin) -->
            <li class="<?= ($currentDir == 'classes') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/classes/index.php">
                    <i class="fas fa-school"></i> Classes
                </a>
            </li>

            <!-- Subjects (Admin) -->
            <li class="<?= ($currentDir == 'subjects') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/subjects/index.php">
                    <i class="fas fa-book"></i> Subjects
                </a>
            </li>

            <!-- Timetable (Admin) -->
            <li class="<?= ($currentDir == 'timetable') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/timetable/index.php">
                    <i class="fas fa-calendar-alt"></i> Timetables
                </a>
            </li>

            <!-- Attendance (Admin) -->
            <li class="<?= ($currentDir == 'attendance') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/attendance/index.php">
                    <i class="fas fa-clipboard-user"></i> Attendance
                </a>
            </li>

            <!-- Exam Management (Admin) -->
            <li class="<?= ($currentDir == 'exams') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/exams/index.php">
                    <i class="fas fa-file-signature"></i> Exams & Marks
                </a>
            </li>

            <!-- Fee Management (Admin) -->
            <li class="<?= ($currentDir == 'fees') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/fees/index.php">
                    <i class="fas fa-file-invoice-dollar"></i> Fees & Billing
                </a>
            </li>

            <!-- Library (Admin) -->
            <li class="<?= ($currentDir == 'library') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/library/index.php">
                    <i class="fas fa-book-open"></i> Library Books
                </a>
            </li>

            <!-- Announcements (Admin) -->
            <li class="<?= ($currentDir == 'announcements') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/announcements/index.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>

            <!-- User Management (Admin) -->
            <li class="<?= ($currentDir == 'users') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/users/index.php">
                    <i class="fas fa-users-cog"></i> User Accounts
                </a>
            </li>

            <!-- Reports (Admin) -->
            <li class="<?= ($currentDir == 'reports') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/reports/index.php">
                    <i class="fas fa-chart-line"></i> Dynamic Reports
                </a>
            </li>

            <!-- Settings (Admin) -->
            <li class="<?= ($currentDir == 'settings') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/settings/index.php">
                    <i class="fas fa-cogs"></i> School Settings
                </a>
            </li>

        <?php elseif ($role === 'teacher'): ?>
            <!-- Teacher specific views -->
            <li class="<?= ($currentDir == 'timetable') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/timetable/view.php">
                    <i class="fas fa-calendar-alt"></i> My Timetable
                </a>
            </li>

            <li class="<?= ($currentDir == 'attendance') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/attendance/mark.php">
                    <i class="fas fa-check-double"></i> Student Attendance
                </a>
            </li>

            <li class="<?= ($currentDir == 'exams') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/exams/marks_entry.php">
                    <i class="fas fa-marker"></i> Record Marks
                </a>
            </li>

            <li class="<?= ($currentDir == 'library') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/library/index.php">
                    <i class="fas fa-book-open"></i> Library Catalog
                </a>
            </li>

            <li class="<?= ($currentDir == 'announcements') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/announcements/index.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>

        <?php elseif ($role === 'student'): ?>
            <!-- Student specific views -->
            <li class="<?= ($currentDir == 'timetable') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/timetable/view.php">
                    <i class="fas fa-calendar-day"></i> My Timetable
                </a>
            </li>

            <li class="<?= ($currentDir == 'attendance') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/attendance/my_attendance.php">
                    <i class="fas fa-calendar-check"></i> My Attendance
                </a>
            </li>

            <li class="<?= ($currentDir == 'exams') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/exams/my_results.php">
                    <i class="fas fa-poll-h"></i> Report Card (GPA)
                </a>
            </li>

            <li class="<?= ($currentDir == 'fees') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/fees/my_fees.php">
                    <i class="fas fa-receipt"></i> Fees & Payments
                </a>
            </li>

            <li class="<?= ($currentDir == 'library') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/library/my_borrows.php">
                    <i class="fas fa-book-reader"></i> Borrowed Books
                </a>
            </li>

            <li class="<?= ($currentDir == 'announcements') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>views/announcements/index.php">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <small class="text-white-50">&copy; 2026 EduPortal</small>
    </div>
</nav>
