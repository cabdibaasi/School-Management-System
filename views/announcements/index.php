<?php
/**
 * Announcements Notice Board Directory
 */
$pageTitle = "Notice Board";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole(['admin', 'teacher', 'student']);

$db = Database::connect();
$role = Auth::role();
$msgError = '';
$msgSuccess = '';

// Handle announcement deletion (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && $role === 'admin') {
    $token = $_POST['csrf_token'] ?? '';
    if (Utility::validateCSRFToken($token)) {
        $delId = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = :id");
        if ($stmt->execute(['id' => $delId])) {
            Utility::setFlash('success', 'Announcement deleted.');
            redirect('views/announcements/index.php');
        } else {
            $msgError = 'Failed to delete announcement.';
        }
    } else {
        $msgError = 'Invalid request security token.';
    }
}

// Fetch announcements based on target roles
if ($role === 'admin') {
    $announcements = $db->query("SELECT * FROM announcements ORDER BY id DESC")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM announcements WHERE target_role IN ('all', :role) ORDER BY id DESC");
    $stmt->execute(['role' => $role]);
    $announcements = $stmt->fetchAll();
}

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Announcements</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-bullhorn me-2"></i>School Notice Board</h2>
    <?php if ($role === 'admin'): ?>
        <a href="<?= BASE_URL ?>views/announcements/create.php" class="btn btn-primary btn-primary-custom text-white">
            <i class="fas fa-plus me-2"></i> Broadcast Notice
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Announcements Timeline -->
<div class="row">
    <?php if (empty($announcements)): ?>
        <div class="col-12">
            <div class="card section-card p-5 text-center text-muted">
                <i class="far fa-bell-slash fs-1 mb-3 text-secondary-color"></i>
                <h5>No announcements on the board right now.</h5>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $ann): 
            $badgeColor = 'bg-primary';
            if ($ann['target_role'] == 'teacher') $badgeColor = 'bg-cyan-light text-dark';
            if ($ann['target_role'] == 'student') $badgeColor = 'bg-purple-light text-dark';
            ?>
            <div class="col-12 mb-3">
                <div class="card section-card p-4 shadow-sm position-relative border-start border-primary border-4">
                    
                    <?php if ($role === 'admin'): ?>
                        <button class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-3 me-3 delete-ann-btn" 
                                data-id="<?= $ann['id'] ?>" 
                                data-title="<?= e($ann['title']) ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteAnnModal">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    <?php endif; ?>

                    <div class="pe-5">
                        <div class="d-flex align-items-center mb-2 flex-wrap">
                            <h5 class="fw-bold text-dark mb-0 me-3"><?= e($ann['title']) ?></h5>
                            <span class="badge <?= $badgeColor ?> px-3 py-1 rounded-pill text-uppercase mt-1 mt-md-0" style="font-size:0.7rem;">Target: <?= e($ann['target_role']) ?></span>
                        </div>
                        <p class="text-muted" style="line-height:1.6;"><?= nl2br(e($ann['content'])) ?></p>
                        <small class="text-muted d-block mt-3"><i class="far fa-clock me-2"></i>Posted Date: <?= date('F d, Y @ h:i A', strtotime($ann['created_at'])) ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- DELETE MODAL (Admin Only) -->
<?php if ($role === 'admin'): ?>
    <div class="modal fade" id="deleteAnnModal" tabindex="-1" aria-labelledby="deleteAnnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
                <form action="<?= BASE_URL ?>views/announcements/index.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id" name="id">
                    
                    <div class="modal-header bg-danger text-white border-0 py-3">
                        <h5 class="modal-title fw-bold" id="deleteAnnModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Notice</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                        <h5 class="fw-bold mb-2">Are you sure?</h5>
                        <p class="text-muted">You are about to delete announcement: <strong id="delete_ann_display" class="text-danger"></strong>.</p>
                    </div>
                    <div class="modal-footer border-0 bg-light py-2">
                        <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const delBtns = document.querySelectorAll(".delete-ann-btn");
        delBtns.forEach(btn => {
            btn.addEventListener("click", function () {
                document.getElementById("delete_id").value = this.getAttribute("data-id");
                document.getElementById("delete_ann_display").innerText = this.getAttribute("data-title");
            });
        });
    });
    </script>
<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
