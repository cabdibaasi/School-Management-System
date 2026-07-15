<?php
/**
 * Teacher Directory Listing Page
 */
$pageTitle = "Teacher Directory";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('admin');

$db = Database::connect();

// Filters & Query Parameters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort = $_GET['sort'] ?? 't.full_name';
$order = $_GET['order'] ?? 'ASC';

// Pagination setup
$limit = 10;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch lists & count
$teachers = Teacher::getFilteredList($search, $status, $sort, $order, $limit, $offset);
$totalRecords = Teacher::countFiltered($search, $status);
$totalPages = ceil($totalRecords / $limit);

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Teachers</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Management</h2>
    <div>
        <a href="<?= BASE_URL ?>views/teachers/export.php?action=csv&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="btn btn-outline-success me-1 rounded-3">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
        <a href="<?= BASE_URL ?>views/teachers/export.php?action=print&search=<?= urlencode($search) ?>&status=<?= $status ?>" target="_blank" class="btn btn-outline-dark me-1 rounded-3">
            <i class="fas fa-print me-1"></i> Print / PDF
        </a>
        <a href="<?= BASE_URL ?>views/teachers/create.php" class="btn btn-primary btn-primary-custom text-white">
            <i class="fas fa-user-plus me-2"></i> Register Teacher
        </a>
    </div>
</div>

<!-- Filters Panel Card -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/teachers/index.php" method="GET" class="row g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control form-control-custom bg-light" name="search" value="<?= e($search) ?>" placeholder="Search name, employee #, email...">
            </div>
        </div>
        
        <!-- Status Filter -->
        <div class="col-md-3">
            <select class="form-select form-control-custom" name="status">
                <option value="">-- All Status --</option>
                <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <!-- Sorting -->
        <div class="col-md-3">
            <select class="form-select form-control-custom" name="sort">
                <option value="t.full_name" <?= ($sort === 't.full_name') ? 'selected' : '' ?>>Sort: Full Name</option>
                <option value="t.employee_id" <?= ($sort === 't.employee_id') ? 'selected' : '' ?>>Sort: Employee #</option>
                <option value="t.salary" <?= ($sort === 't.salary') ? 'selected' : '' ?>>Sort: Salary</option>
                <option value="t.date_joined" <?= ($sort === 't.date_joined') ? 'selected' : '' ?>>Sort: Date Joined</option>
            </select>
        </div>
        
        <!-- Actions -->
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary px-3 rounded-3 text-white"><i class="fas fa-filter"></i></button>
        </div>
    </form>
</div>

<!-- Teachers Table -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Teacher Name</th>
                    <th>Qualification</th>
                    <th>Email</th>
                    <th>Salary</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No teachers registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($teachers as $tch): 
                        $statusBadge = ($tch['status'] == 'active') ? 'bg-success' : 'bg-danger';
                        ?>
                        <tr>
                            <td class="fw-semibold text-primary font-monospace"><?= e($tch['employee_id']) ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $photoPath = BASE_URL . 'assets/uploads/profiles/' . $tch['photo'];
                                    if (empty($tch['photo']) || !file_exists(UPLOAD_PATH . 'profiles/' . $tch['photo'])) {
                                        $photoPath = BASE_URL . 'assets/img/default-avatar.png';
                                    }
                                    ?>
                                    <img src="<?= $photoPath ?>" alt="Photo" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    <strong><?= e($tch['full_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= e($tch['qualification']) ?></td>
                            <td><?= e($tch['email'] ?: '-') ?></td>
                            <td><strong class="text-success"><?= Utility::formatCurrency($tch['salary'], Setting::get('currency','USD')) ?></strong></td>
                            <td><span class="badge <?= $statusBadge ?> px-3 py-1 rounded-pill"><?= ucfirst($tch['status']) ?></span></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>views/teachers/view.php?id=<?= $tch['id'] ?>" class="btn btn-sm btn-outline-info rounded-3 me-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?= BASE_URL ?>views/teachers/edit.php?id=<?= $tch['id'] ?>" class="btn btn-sm btn-outline-warning rounded-3 me-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger rounded-3 delete-teacher-btn" 
                                        data-id="<?= $tch['id'] ?>"
                                        data-name="<?= e($tch['full_name']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteTeacherModal">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Footer -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center mt-3">
            <span class="small text-muted">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalRecords ?> teachers)</span>
            <nav aria-label="Teacher Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- DELETE TEACHER MODAL -->
<div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/teachers/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteTeacherModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Teacher Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted">You are about to delete teacher <strong id="delete_teacher_display" class="text-danger"></strong>.</p>
                    <p class="text-muted small">Warning: Deleting the teacher account will also delete all associated subjects allocations and weekly schedules!</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Handle delete POST request inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if (Utility::validateCSRFToken($token)) {
        if (Teacher::delete($delId)) {
            Utility::setFlash('success', 'Teacher profile deleted successfully.');
            redirect('views/teachers/index.php');
        } else {
            Utility::setFlash('danger', 'Failed to delete teacher.');
            redirect('views/teachers/index.php');
        }
    } else {
        Utility::setFlash('danger', 'Invalid security token.');
        redirect('views/teachers/index.php');
    }
}
?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const deleteBtns = document.querySelectorAll(".delete-teacher-btn");
    deleteBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_teacher_display").innerText = this.getAttribute("data-name");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
