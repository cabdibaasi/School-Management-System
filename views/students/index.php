<?php
/**
 * Student Directory Listing CRUD Page
 */
$pageTitle = "Student Directory";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard - Admin only
Auth::requireRole('admin');

$db = Database::connect();

// Filters & Query Parameters
$search = trim($_GET['search'] ?? '');
$classId = trim($_GET['class_id'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort = $_GET['sort'] ?? 's.first_name';
$order = $_GET['order'] ?? 'ASC';

// Pagination setup
$limit = 10;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch filtered list and counts
$students = Student::getFilteredList($search, $classId, $status, $sort, $order, $limit, $offset);
$totalRecords = Student::countFiltered($search, $classId, $status);
$totalPages = ceil($totalRecords / $limit);

// Fetch classes for options
$classes = SchoolClass::getAll();

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Students</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-user-graduate me-2"></i>Student Management</h2>
    <div class="d-flex gap-2">

        <!-- Export Dropdown -->
        <?php
            $exportBase = BASE_URL . 'views/students/export.php?search=' . urlencode($search) . '&class_id=' . $classId . '&status=' . $status;
        ?>
        <div class="dropdown">
            <button class="btn btn-outline-secondary rounded-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download me-1"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:210px;">
                <li><h6 class="dropdown-header text-muted small"><i class="fas fa-file-export me-1"></i> Export Student List</h6></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2" href="<?= $exportBase ?>&action=excel">
                        <i class="fas fa-file-excel text-success me-2"></i>
                        <strong>Excel</strong> <span class="text-muted small">.xls (styled)</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="<?= $exportBase ?>&action=csv">
                        <i class="fas fa-file-csv text-secondary me-2"></i>
                        <strong>CSV</strong> <span class="text-muted small">.csv (plain)</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2" href="<?= $exportBase ?>&action=pdf" target="_blank">
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <strong>PDF / Print</strong> <span class="text-muted small">opens preview</span>
                    </a>
                </li>
            </ul>
        </div>

        <a href="<?= BASE_URL ?>views/students/import.php" class="btn btn-outline-primary rounded-3">
            <i class="fas fa-file-import me-1"></i> Import Excel
        </a>
        <a href="<?= BASE_URL ?>views/students/create.php" class="btn btn-primary btn-primary-custom text-white">
            <i class="fas fa-user-plus me-2"></i> Enroll Student
        </a>
    </div>
</div>


<!-- Filters Panel Card -->
<div class="card section-card p-3 mb-4">
    <form action="<?= BASE_URL ?>views/students/index.php" method="GET" class="row g-2 align-items-center">
        <!-- Search Input -->
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control form-control-custom bg-light" name="search" value="<?= e($search) ?>" placeholder="Search name, admission #, card #...">
            </div>
        </div>
        
        <!-- Class Filter -->
        <div class="col-md-3">
            <select class="form-select form-control-custom" name="class_id">
                <option value="">-- All Classes --</option>
                <?php foreach ($classes as $cls): ?>
                    <option value="<?= $cls['id'] ?>" <?= ($classId == $cls['id']) ? 'selected' : '' ?>>
                        <?= e($cls['class_name']) ?> - <?= e($cls['section']) ?> (<?= e($cls['academic_year']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Status Filter -->
        <div class="col-md-2">
            <select class="form-select form-control-custom" name="status">
                <option value="">-- All Status --</option>
                <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <!-- Sorting Column -->
        <div class="col-md-2">
            <select class="form-select form-control-custom" name="sort">
                <option value="s.first_name" <?= ($sort === 's.first_name') ? 'selected' : '' ?>>Sort: Name</option>
                <option value="s.admission_number" <?= ($sort === 's.admission_number') ? 'selected' : '' ?>>Sort: Admission #</option>
                <option value="s.roll_number" <?= ($sort === 's.roll_number') ? 'selected' : '' ?>>Sort: Roll #</option>
            </select>
        </div>
        
        <!-- Filter Actions -->
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary px-3 rounded-3 text-white"><i class="fas fa-filter"></i></button>
        </div>
    </form>
</div>

<!-- Students Directory Table Card -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Admission #</th>
                    <th>Roll #</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Parent details</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No students matching the criteria were found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $std): 
                        $statusBadge = ($std['status'] == 'active') ? 'bg-success' : 'bg-danger';
                        ?>
                        <tr>
                            <td class="fw-semibold text-primary font-monospace"><?= e($std['admission_number']) ?></td>
                            <td><?= e($std['roll_number'] ?: '-') ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $photoPath = BASE_URL . 'assets/uploads/profiles/' . $std['photo'];
                                    if (empty($std['photo']) || !file_exists(UPLOAD_PATH . 'profiles/' . $std['photo'])) {
                                        $photoPath = BASE_URL . 'assets/img/default-avatar.png';
                                    }
                                    ?>
                                    <img src="<?= $photoPath ?>" alt="Photo" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    <strong><?= e($std['first_name'] . ' ' . $std['last_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= e($std['class_name']) ?> - <?= e($std['section']) ?></td>
                            <td>
                                <div style="font-size: 0.8rem;">
                                    <span class="d-block text-dark fw-semibold"><?= e($std['parent_name']) ?></span>
                                    <span class="text-muted"><i class="fas fa-phone me-1" style="font-size:0.75rem;"></i><?= e($std['parent_phone']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge <?= $statusBadge ?> px-3 py-1 rounded-pill"><?= ucfirst($std['status']) ?></span></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>views/students/view.php?id=<?= $std['id'] ?>" class="btn btn-sm btn-outline-info rounded-3 me-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?= BASE_URL ?>views/students/edit.php?id=<?= $std['id'] ?>" class="btn btn-sm btn-outline-warning rounded-3 me-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger rounded-3 delete-student-btn" 
                                        data-id="<?= $std['id'] ?>"
                                        data-name="<?= e($std['first_name'] . ' ' . $std['last_name']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteStudentModal">
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
            <span class="small text-muted">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalRecords ?> records)</span>
            <nav aria-label="Student Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&class_id=<?= $classId ?>&status=<?= $status ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&class_id=<?= $classId ?>&status=<?= $status ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&class_id=<?= $classId ?>&status=<?= $status ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- DELETE STUDENT MODAL -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/students/index.php" method="POST">
                <!-- We must implement a POST action deletion script block to handle requests. We can POST to students/index.php with action=delete -->
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteStudentModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Student Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted">You are about to delete student <strong id="delete_student_display" class="text-danger"></strong>.</p>
                    <p class="text-muted small">Warning: Deleting the student will also permanently wipe out all their attendance marks, exam grades, and outstanding fee invoices!</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script to handle inline deletions and POST triggers -->
<?php
// Handle deletions right inside the page if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if (Utility::validateCSRFToken($token)) {
        if (Student::delete($delId)) {
            Utility::setFlash('success', 'Student deleted successfully.');
            redirect('views/students/index.php');
        } else {
            Utility::setFlash('danger', 'Failed to delete student.');
            redirect('views/students/index.php');
        }
    } else {
        Utility::setFlash('danger', 'Invalid security token.');
        redirect('views/students/index.php');
    }
}
?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const deleteBtns = document.querySelectorAll(".delete-student-btn");
    deleteBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_student_display").innerText = this.getAttribute("data-name");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
