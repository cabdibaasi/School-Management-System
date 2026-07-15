<?php
/**
 * Create Announcement Notice (Admin Only)
 */
$pageTitle = "Broadcast Announcement";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $targetRole = $_POST['target_role'] ?? 'all';
        
        $validator = new Validation();
        $validator->required([
            'title' => 'Notice Title',
            'content' => 'Notice Content',
            'target_role' => 'Target Audience'
        ], $_POST);
        
        if ($validator->passes()) {
            $stmt = $db->prepare("INSERT INTO announcements (title, content, target_role) VALUES (:title, :content, :target_role)");
            if ($stmt->execute([
                'title' => $title,
                'content' => $content,
                'target_role' => $targetRole
            ])) {
                // Simulate SMS/Email notifications alerts logger
                $logMessage = "[" . date('Y-m-d H:i:s') . "] NOTICE ALERT BROADCASTED: Title: '{$title}', Target: '{$targetRole}'\n";
                $logPath = ROOT_PATH . 'assets/uploads/notifications_log.txt';
                file_put_contents($logPath, $logMessage, FILE_APPEND);
                
                Utility::setFlash('success', 'Announcement notice broadcasted successfully. Notifications logged.');
                redirect('views/announcements/index.php');
            } else {
                $msgError = 'Failed to broadcast announcement.';
            }
        } else {
            $errors = $validator->getErrors();
            $msgError = reset($errors);
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
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/announcements/index.php">Announcements</a></li>
        <li class="breadcrumb-item active" aria-current="page">Broadcast</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-bullhorn me-2"></i>Broadcast Announcement Notice</h2>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card section-card">
            <form action="<?= BASE_URL ?>views/announcements/create.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold small text-muted">NOTICE TITLE</label>
                    <input type="text" class="form-control form-control-custom" id="title" name="title" placeholder="Enter headline summary" required>
                </div>

                <div class="mb-3">
                    <label for="target_role" class="form-label fw-semibold small text-muted">TARGET AUDIENCE</label>
                    <select class="form-select form-control-custom" id="target_role" name="target_role" required>
                        <option value="all">All Roles (Admins, Teachers, Students)</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="student">Students Only</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label fw-semibold small text-muted">NOTICE DETAIL MESSAGE</label>
                    <textarea class="form-control form-control-custom" id="content" name="content" rows="6" placeholder="Type announcement contents..." required></textarea>
                </div>

                <div class="alert alert-warning border-0 rounded-3 small text-muted" role="alert">
                    <i class="fas fa-info-circle me-2"></i> Broadcasting this announcement will trigger mock SMS/Email notifications. Logs will be recorded in the notifications database.
                </div>

                <div class="text-end">
                    <a href="<?= BASE_URL ?>views/announcements/index.php" class="btn btn-secondary px-4 rounded-3 me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-primary-custom text-white px-5">
                        <i class="fas fa-save me-2"></i> Broadcast Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
