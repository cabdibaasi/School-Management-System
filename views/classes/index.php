<?php
/**
 * Classes Management CRUD Page
 */
$pageTitle = "Manage Classes";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Authorization Guard - Only Admin can manage classes
Auth::requireRole('admin');

$db = Database::connect();
$msgError = '';
$msgSuccess = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        if ($action === 'create') {
            $className = trim($_POST['class_name'] ?? '');
            $section = trim($_POST['section'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');
            
            $validator = new Validation();
            $validator->required([
                'class_name' => 'Class Name',
                'section' => 'Section',
                'academic_year' => 'Academic Year'
            ], $_POST);
            
            if ($validator->passes()) {
                // Check if class section year already exists
                $stmt = $db->prepare("SELECT id FROM classes WHERE class_name = :name AND section = :sec AND academic_year = :year");
                $stmt->execute(['name' => $className, 'sec' => $section, 'year' => $academicYear]);
                if ($stmt->fetch()) {
                    $msgError = 'A class with this section and academic year already exists.';
                } else {
                    if (SchoolClass::create($className, $section, $academicYear)) {
                        Utility::setFlash('success', 'Class created successfully.');
                        redirect('views/classes/index.php');
                    } else {
                        $msgError = 'Failed to create class.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $className = trim($_POST['class_name'] ?? '');
            $section = trim($_POST['section'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');
            
            $validator = new Validation();
            $validator->required([
                'class_name' => 'Class Name',
                'section' => 'Section',
                'academic_year' => 'Academic Year'
            ], $_POST);
            
            if ($validator->passes()) {
                // Check uniqueness excluding current ID
                $stmt = $db->prepare("SELECT id FROM classes WHERE class_name = :name AND section = :sec AND academic_year = :year AND id != :id");
                $stmt->execute(['name' => $className, 'sec' => $section, 'year' => $academicYear, 'id' => $id]);
                if ($stmt->fetch()) {
                    $msgError = 'Another class with this section and academic year already exists.';
                } else {
                    if (SchoolClass::update($id, $className, $section, $academicYear)) {
                        Utility::setFlash('success', 'Class updated successfully.');
                        redirect('views/classes/index.php');
                    } else {
                        $msgError = 'Failed to update class.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (SchoolClass::delete($id)) {
                Utility::setFlash('success', 'Class deleted successfully.');
                redirect('views/classes/index.php');
            } else {
                $msgError = 'Failed to delete class. It may have associated records.';
            }
        }
    }
}

// Fetch all classes
$classes = SchoolClass::getAll();
$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Classes</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-school me-2"></i>Class & Section Management</h2>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>views/classes/import.php" class="btn btn-outline-primary rounded-3">
            <i class="fas fa-file-import me-1"></i> Import Excel
        </a>
        <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#createClassModal">
            <i class="fas fa-plus me-2"></i> Add Class
        </button>
    </div>
</div>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Classes List Card -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Academic Year</th>
                    <th style="width: 200px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No classes recorded yet. Click "Add Class" to insert one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $cls): ?>
                        <tr>
                            <td><?= e($cls['id']) ?></td>
                            <td><strong class="text-primary"><?= e($cls['class_name']) ?></strong></td>
                            <td><span class="badge bg-secondary-light text-dark px-3 py-2 rounded-pill"><?= e($cls['section']) ?></span></td>
                            <td><?= e($cls['academic_year']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning rounded-3 me-1 edit-class-btn" 
                                        data-id="<?= $cls['id'] ?>"
                                        data-name="<?= e($cls['class_name']) ?>"
                                        data-section="<?= e($cls['section']) ?>"
                                        data-year="<?= e($cls['academic_year']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editClassModal">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-3 delete-class-btn" 
                                        data-id="<?= $cls['id'] ?>"
                                        data-name="<?= e($cls['class_name']) ?>-<?= e($cls['section']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteClassModal">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CREATE CLASS MODAL -->
<div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/classes/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-primary" id="createClassModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="class_name" class="form-label fw-semibold small text-muted">CLASS NAME / GRADE</label>
                        <input type="text" class="form-control form-control-custom" id="class_name" name="class_name" placeholder="e.g. Grade 10, Class A" required>
                    </div>
                    <div class="mb-3">
                        <label for="section" class="form-label fw-semibold small text-muted">SECTION</label>
                        <input type="text" class="form-control form-control-custom" id="section" name="section" placeholder="e.g. Alpha, B, North" required>
                    </div>
                    <div class="mb-3">
                        <label for="academic_year" class="form-label fw-semibold small text-muted">ACADEMIC YEAR</label>
                        <input type="text" class="form-control form-control-custom" id="academic_year" name="academic_year" value="<?= e(Setting::get('current_academic_year', '2026-2027')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 text-white">Save Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CLASS MODAL -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/classes/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-warning" id="editClassModalLabel"><i class="fas fa-edit me-2"></i>Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_class_name" class="form-label fw-semibold small text-muted">CLASS NAME / GRADE</label>
                        <input type="text" class="form-control form-control-custom" id="edit_class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_section" class="form-label fw-semibold small text-muted">SECTION</label>
                        <input type="text" class="form-control form-control-custom" id="edit_section" name="section" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_academic_year" class="form-label fw-semibold small text-muted">ACADEMIC YEAR</label>
                        <input type="text" class="form-control form-control-custom" id="edit_academic_year" name="academic_year" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 rounded-3 text-dark fw-bold">Update Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE CLASS MODAL -->
<div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/classes/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteClassModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Class</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted">You are about to delete class <strong id="delete_class_display" class="text-danger"></strong>.</p>
                    <p class="text-muted small">Warning: Deleting this class will disconnect all students linked to it!</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS to trigger details copy to edit/delete modals -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Edit trigger
    const editBtns = document.querySelectorAll(".edit-class-btn");
    editBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("edit_id").value = this.getAttribute("data-id");
            document.getElementById("edit_class_name").value = this.getAttribute("data-name");
            document.getElementById("edit_section").value = this.getAttribute("data-section");
            document.getElementById("edit_academic_year").value = this.getAttribute("data-year");
        });
    });

    // Delete trigger
    const deleteBtns = document.querySelectorAll(".delete-class-btn");
    deleteBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_class_display").innerText = this.getAttribute("data-name");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
