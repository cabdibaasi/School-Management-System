<?php
/**
 * Global Header Layout
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';

if (!Auth::check()) {
    Utility::setFlash('danger', 'Please log in to access the system.');
    redirect('views/login.php');
}

$pageTitle   = $pageTitle   ?? 'School Management System';
$schoolName  = Setting::get('school_name', 'Talent Institute');
$userRole    = Auth::role();
$displayRole = ucfirst($userRole);
$displayName = Auth::displayName();
$userPhoto   = Auth::photo();

$photoPath = BASE_URL . 'assets/uploads/profiles/' . $userPhoto;
if (empty($userPhoto) || !file_exists(UPLOAD_PATH . 'profiles/' . $userPhoto)) {
    $photoPath = BASE_URL . 'assets/img/default-avatar.png';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <!-- Dark Mode: set before CSS loads to prevent flash -->
    <script>
        (function() {
            var s = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', s);
            document.addEventListener('DOMContentLoaded', function() {
                document.body && document.body.setAttribute('data-theme', s);
            });
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="<?= e($schoolName) ?> — School Management System">
    <title><?= e($pageTitle) ?> — <?= e($schoolName) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- System CSS -->
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <?php include ROOT_PATH . 'views/layouts/sidebar.php'; ?>

    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

    <!-- Page Content -->
    <div id="content">

        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-custom" aria-label="Top navigation">
            <div class="container-fluid d-flex align-items-center px-3 px-md-4">

                <!-- Sidebar Toggle -->
                <button type="button" id="sidebarCollapse" class="sidebar-toggle-btn me-2" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Brand (hidden on xs) -->
                <a class="navbar-brand ms-1 d-none d-sm-flex align-items-center gap-2 text-decoration-none" href="<?= BASE_URL ?>views/dashboard.php">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:0.85rem;flex-shrink:0;">TI</div>
                    <div style="line-height:1.2;">
                        <span class="fw-bold d-block" style="font-size:0.9rem;color:var(--text-heading);">Talent Institute</span>
                        <span style="font-size:0.65rem;color:var(--text-muted);font-weight:500;">معـهـد تالنت</span>
                    </div>
                </a>

                <!-- Right side -->
                <div class="ms-auto d-flex align-items-center gap-2">

                    <!-- Dark/Light Toggle -->
                    <button id="themeToggleBtn"
                            class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                            style="width:38px;height:38px;border:1px solid var(--border-color);"
                            title="Toggle Dark/Light Mode"
                            aria-label="Toggle theme">
                        <i class="fas fa-moon" id="themeIcon" style="color:var(--text-muted);font-size:0.9rem;"></i>
                    </button>

                    <!-- Notifications -->
                    <a href="<?= BASE_URL ?>views/announcements/index.php"
                       class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                       style="width:38px;height:38px;border:1px solid var(--border-color);"
                       title="Announcements" aria-label="Announcements">
                        <i class="fas fa-bell" style="color:var(--text-muted);font-size:0.9rem;"></i>
                    </a>

                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light d-flex align-items-center gap-2 rounded-pill px-2 py-1"
                                style="border:1px solid var(--border-color);min-width:0;"
                                id="dropdownProfile" data-bs-toggle="dropdown"
                                aria-expanded="false" aria-label="User menu">
                            <img src="<?= $photoPath ?>" alt="Avatar"
                                 class="rounded-circle"
                                 style="width:30px;height:30px;object-fit:cover;flex-shrink:0;border:2px solid var(--primary-color);">
                            <div class="d-none d-md-block text-start" style="line-height:1.2;min-width:0;">
                                <p class="mb-0 fw-semibold text-truncate" style="font-size:0.82rem;max-width:110px;color:var(--text-heading);"><?= e($displayName) ?></p>
                                <small style="font-size:0.7rem;color:var(--text-muted);"><?= e($displayRole) ?></small>
                            </div>
                            <i class="fas fa-chevron-down d-none d-md-block" style="font-size:0.65rem;color:var(--text-muted);"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end mt-2 py-2" aria-labelledby="dropdownProfile">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <p class="mb-0 fw-semibold" style="font-size:0.85rem;color:var(--text-heading);"><?= e($displayName) ?></p>
                                <small style="color:var(--text-muted);"><?= e($displayRole) ?> Account</small>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 mx-1" href="<?= BASE_URL ?>views/profile.php">
                                    <i class="fas fa-user-circle me-2" style="color:var(--primary-color);width:16px;"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 mx-1" href="<?= BASE_URL ?>views/profile.php#security">
                                    <i class="fas fa-key me-2" style="color:#d97706;width:16px;"></i> Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider mx-2"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-2 mx-1 text-danger" href="<?= BASE_URL ?>views/logout.php">
                                    <i class="fas fa-sign-out-alt me-2" style="width:16px;"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash messages + page content starts here -->
        <div class="container-fluid px-3 px-md-4 py-3">
            <?= Utility::displayFlash() ?>
