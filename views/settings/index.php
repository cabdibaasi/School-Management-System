<?php
/**
 * System Settings Panel
 */
$pageTitle = "System Settings";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('admin');

$msgError   = '';
$msgSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? 'save_general';

        if ($action === 'save_general') {
            $settings = [
                'school_name'           => trim($_POST['school_name'] ?? ''),
                'school_email'          => trim($_POST['school_email'] ?? ''),
                'school_phone'          => trim($_POST['school_phone'] ?? ''),
                'school_address'        => trim($_POST['school_address'] ?? ''),
                'current_academic_year' => trim($_POST['current_academic_year'] ?? ''),
                'currency'              => trim($_POST['currency'] ?? 'USD'),
                'language'              => trim($_POST['language'] ?? 'English'),
            ];

            $allOk = true;
            foreach ($settings as $key => $value) {
                if (!Setting::set($key, $value)) {
                    $allOk = false;
                    break;
                }
            }

            // Handle logo upload
            if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = Utility::uploadImage($_FILES['school_logo'], 'assets/uploads/logos/');
                if ($uploadResult['success']) {
                    Setting::set('school_logo', $uploadResult['path']);
                } else {
                    $msgError = 'Logo upload failed: ' . $uploadResult['error'];
                }
            }

            if ($allOk && empty($msgError)) {
                // Invalidate setting cache
                Setting::clearCache();
                Utility::setFlash('success', 'Settings saved successfully.');
                redirect('views/settings/index.php');
            } elseif (empty($msgError)) {
                $msgError = 'Failed to save some settings.';
            }
        }
    }
}

$csrfToken = Utility::generateCSRFToken();

// Load current settings
$s = [
    'school_name'           => Setting::get('school_name', ''),
    'school_email'          => Setting::get('school_email', ''),
    'school_phone'          => Setting::get('school_phone', ''),
    'school_address'        => Setting::get('school_address', ''),
    'current_academic_year' => Setting::get('current_academic_year', ''),
    'school_logo'           => Setting::get('school_logo', ''),
    'currency'              => Setting::get('currency', 'USD'),
    'language'              => Setting::get('language', 'English'),
];

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">System Settings</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-cog me-2"></i>System Settings</h2>

<?php Utility::renderFlash(); ?>
<?php if ($msgError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= e($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Settings Form -->
    <div class="col-md-8">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="save_general">

            <!-- School Information -->
            <div class="card section-card mb-4">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="fw-bold text-primary mb-0"><i class="fas fa-school me-2"></i>School Information</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-muted">SCHOOL NAME *</label>
                            <input type="text" name="school_name" class="form-control form-control-custom" value="<?= e($s['school_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">SCHOOL EMAIL</label>
                            <input type="email" name="school_email" class="form-control form-control-custom" value="<?= e($s['school_email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">SCHOOL PHONE</label>
                            <input type="text" name="school_phone" class="form-control form-control-custom" value="<?= e($s['school_phone']) ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-muted">SCHOOL ADDRESS</label>
                            <textarea name="school_address" class="form-control form-control-custom" rows="2"><?= e($s['school_address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic & System Config -->
            <div class="card section-card mb-4">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="fw-bold text-primary mb-0"><i class="fas fa-sliders-h me-2"></i>Academic & System Config</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">CURRENT ACADEMIC YEAR</label>
                            <input type="text" name="current_academic_year" class="form-control form-control-custom" value="<?= e($s['current_academic_year']) ?>" placeholder="e.g. 2026-2027">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted">CURRENCY CODE</label>
                            <select name="currency" class="form-select form-control-custom">
                                <?php foreach (['USD', 'EUR', 'GBP', 'KES', 'NGN', 'ZAR', 'GHS', 'INR', 'CAD', 'AUD'] as $cur): ?>
                                <option value="<?= $cur ?>" <?= ($s['currency'] === $cur) ? 'selected' : '' ?>><?= $cur ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-muted">LANGUAGE</label>
                            <select name="language" class="form-select form-control-custom">
                                <?php foreach (['English', 'French', 'Spanish', 'Arabic', 'Portuguese'] as $lang): ?>
                                <option value="<?= $lang ?>" <?= ($s['language'] === $lang) ? 'selected' : '' ?>><?= $lang ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo Upload -->
            <div class="card section-card mb-4">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="fw-bold text-primary mb-0"><i class="fas fa-image me-2"></i>School Logo</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <?php if (!empty($s['school_logo']) && file_exists(ROOT_PATH . $s['school_logo'])): ?>
                                <img src="<?= BASE_URL . e($s['school_logo']) ?>" alt="School Logo" class="rounded-3 border" style="height:80px; width:80px; object-fit:contain; background:#f8f9fa;">
                            <?php else: ?>
                                <div class="rounded-3 border d-flex align-items-center justify-content-center text-muted" style="height:80px;width:80px;background:#f8f9fa;">
                                    <i class="fas fa-school fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold small text-muted">UPLOAD NEW LOGO</label>
                            <input type="file" name="school_logo" class="form-control form-control-custom" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">Accepted: JPG, PNG, WEBP. Max: 2MB. Recommended: Square 256×256px.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-primary-custom text-white px-5 py-2 fw-bold rounded-3">
                    <i class="fas fa-save me-2"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Info Panel -->
    <div class="col-md-4">
        <!-- System Info -->
        <div class="card section-card mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-2"></i>System Info</h5>
            </div>
            <div class="card-body pt-3">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2">
                        <span class="text-muted small flex-shrink-0">PHP Version</span>
                        <strong class="text-end" style="font-size:0.875rem;"><?= phpversion() ?></strong>
                    </li>
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2">
                        <span class="text-muted small flex-shrink-0">Server</span>
                        <strong class="text-end text-truncate ms-2" style="font-size:0.875rem;max-width:160px;" title="<?= e($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?>">
                            <?= e(explode(' ', $_SERVER['SERVER_SOFTWARE'] ?? 'Apache')[0]) ?>
                        </strong>
                    </li>
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2">
                        <span class="text-muted small flex-shrink-0">Database</span>
                        <strong style="font-size:0.875rem;">MySQL</strong>
                    </li>
                    <li class="d-flex justify-content-between align-items-center py-2 gap-2">
                        <span class="text-muted small flex-shrink-0">Timezone</span>
                        <strong class="text-end text-truncate ms-2" style="font-size:0.875rem;max-width:160px;"><?= date_default_timezone_get() ?></strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card section-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="fw-bold text-primary mb-0"><i class="fas fa-link me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body pt-3">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>views/reports/index.php" class="btn btn-outline-primary rounded-3 text-start"><i class="fas fa-chart-bar me-2"></i>Academic Reports</a>
                    <a href="<?= BASE_URL ?>views/users/index.php" class="btn btn-outline-secondary rounded-3 text-start"><i class="fas fa-users-cog me-2"></i>User Accounts</a>
                    <a href="<?= BASE_URL ?>views/fees/index.php" class="btn btn-outline-success rounded-3 text-start"><i class="fas fa-file-invoice-dollar me-2"></i>Fee Management</a>
                    <a href="<?= BASE_URL ?>database/schema.sql" class="btn btn-outline-dark rounded-3 text-start" download><i class="fas fa-database me-2"></i>Download Schema SQL</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
