<?php
/**
 * Subject Management CRUD Page
 */
$pageTitle = "Manage Subjects";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Check - Admins only
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
            $name = trim($_POST['subject_name'] ?? '');
            $code = trim($_POST['subject_code'] ?? '');
            $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
            $classId = (int)($_POST['class_id'] ?? 0);
            
            $validator = new Validation();
            $validator->required([
                'subject_name' => 'Subject Name',
                'subject_code' => 'Subject Code',
                'class_id' => 'Class'
            ], $_POST);
            
            if ($validator->passes()) {
                // Check code uniqueness within same class
                $stmt = $db->prepare("SELECT id FROM subjects WHERE subject_code = :code AND class_id = :class_id");
                $stmt->execute(['code' => $code, 'class_id' => $classId]);
                if ($stmt->fetch()) {
                    $msgError = 'A subject with this code already exists for the selected class.';
                } else {
                    if (Subject::create($name, $code, $teacherId, $classId)) {
                        Utility::setFlash('success', 'Subject created successfully.');
                        redirect('views/subjects/index.php');
                    } else {
                        $msgError = 'Failed to create subject.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['subject_name'] ?? '');
            $code = trim($_POST['subject_code'] ?? '');
            $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
            $classId = (int)($_POST['class_id'] ?? 0);
            
            $validator = new Validation();
            $validator->required([
                'subject_name' => 'Subject Name',
                'subject_code' => 'Subject Code',
                'class_id' => 'Class'
            ], $_POST);
            
            if ($validator->passes()) {
                // Check uniqueness excluding current ID
                $stmt = $db->prepare("SELECT id FROM subjects WHERE subject_code = :code AND class_id = :class_id AND id != :id");
                $stmt->execute(['code' => $code, 'class_id' => $classId, 'id' => $id]);
                if ($stmt->fetch()) {
                    $msgError = 'Another subject with this code already exists for the selected class.';
                } else {
                    if (Subject::update($id, $name, $code, $teacherId, $classId)) {
                        Utility::setFlash('success', 'Subject updated successfully.');
                        redirect('views/subjects/index.php');
                    } else {
                        $msgError = 'Failed to update subject.';
                    }
                }
            } else {
                $errors = $validator->getErrors();
                $msgError = reset($errors);
            }
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (Subject::delete($id)) {
                Utility::setFlash('success', 'Subject deleted successfully.');
                redirect('views/subjects/index.php');
            } else {
                $msgError = 'Failed to delete subject. It may have associated marks or timetables.';
            }
        }
    }
}

// Fetch lists to populate options
$subjects = Subject::getAll();
$classes = SchoolClass::getAll();
$teachers = $db->query("SELECT id, full_name FROM teachers ORDER BY full_name ASC")->fetchAll();
$csrfToken = Utility::generateCSRFToken();

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Subjects</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-book me-2"></i>Subject Management</h2>
    <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#createSubjectModal">
        <i class="fas fa-plus me-2"></i> Add Subject
    </button>
</div>

<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Subjects list -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Class / Section</th>
                    <th>Teacher Assigned</th>
                    <th style="width: 200px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No subjects found. Click "Add Subject" to begin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subjects as $sub): ?>
                        <tr>
                            <td><span class="badge bg-blue-light text-primary px-3 py-2 rounded-2 fw-bold font-monospace"><?= e($sub['subject_code']) ?></span></td>
                            <td><strong class="text-dark"><?= e($sub['subject_name']) ?></strong></td>
                            <td><?= e($sub['class_name']) ?> (<?= e($sub['section']) ?>) - <small class="text-muted"><?= e($sub['academic_year']) ?></small></td>
                            <td>
                                <?php if ($sub['teacher_name']): ?>
                                    <i class="fas fa-chalkboard-teacher text-muted me-1"></i> <?= e($sub['teacher_name']) ?>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="fas fa-exclamation-circle me-1"></i> Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning rounded-3 me-1 edit-subject-btn" 
                                        data-id="<?= $sub['id'] ?>"
                                        data-name="<?= e($sub['subject_name']) ?>"
                                        data-code="<?= e($sub['subject_code']) ?>"
                                        data-teacher="<?= e($sub['teacher_id']) ?>"
                                        data-class="<?= e($sub['class_id']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editSubjectModal">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-3 delete-subject-btn" 
                                        data-id="<?= $sub['id'] ?>"
                                        data-name="<?= e($sub['subject_name']) ?> (<?= e($sub['subject_code']) ?>)"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteSubjectModal">
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

<!-- CREATE SUBJECT MODAL -->
<div class="modal fade" id="createSubjectModal" tabindex="-1" aria-labelledby="createSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/subjects/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-primary" id="createSubjectModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="subject_name" class="form-label fw-semibold small text-muted">SUBJECT NAME</label>
                        <input type="text" class="form-control form-control-custom" id="subject_name" name="subject_name" placeholder="e.g. Mathematics, English Lit" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject_code" class="form-label fw-semibold small text-muted">SUBJECT CODE</label>
                        <input type="text" class="form-control form-control-custom" id="subject_code" name="subject_code" placeholder="e.g. MATH101, ENG-B" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label fw-semibold small text-muted">ASSIGN TO CLASS</label>
                        <select class="form-select form-control-custom" id="class_id" name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= $cls['id'] ?>"><?= e($cls['class_name']) ?> - <?= e($cls['section']) ?> (<?= e($cls['academic_year']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label fw-semibold small text-muted">ASSIGN TEACHER (OPTIONAL)</label>
                        <select class="form-select form-control-custom" id="teacher_id" name="teacher_id">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($teachers as $tch): ?>
                                <option value="<?= $tch['id'] ?>"><?= e($tch['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 text-white">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT SUBJECT MODAL -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/subjects/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-warning" id="editSubjectModalLabel"><i class="fas fa-edit me-2"></i>Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_subject_name" class="form-label fw-semibold small text-muted">SUBJECT NAME</label>
                        <input type="text" class="form-control form-control-custom" id="edit_subject_name" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subject_code" class="form-label fw-semibold small text-muted">SUBJECT CODE</label>
                        <input type="text" class="form-control form-control-custom" id="edit_subject_code" name="subject_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_class_id" class="form-label fw-semibold small text-muted">ASSIGN TO CLASS</label>
                        <select class="form-select form-control-custom" id="edit_class_id" name="class_id" required>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= $cls['id'] ?>"><?= e($cls['class_name']) ?> - <?= e($cls['section']) ?> (<?= e($cls['academic_year']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_teacher_id" class="form-label fw-semibold small text-muted">ASSIGN TEACHER (OPTIONAL)</label>
                        <select class="form-select form-control-custom" id="edit_teacher_id" name="teacher_id">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($teachers as $tch): ?>
                                <option value="<?= $tch['id'] ?>"><?= e($tch['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 rounded-3 text-dark fw-bold">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE SUBJECT MODAL -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15)">
            <form action="<?= BASE_URL ?>views/subjects/index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="deleteSubjectModalLabel"><i class="fas fa-trash-alt me-2"></i>Delete Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted">You are about to delete subject <strong id="delete_subject_display" class="text-danger"></strong>.</p>
                    <p class="text-muted small">Warning: This action cannot be undone and will delete related mark logs.</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 rounded-3 text-white">Delete Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Edit details mapping
    const editBtns = document.querySelectorAll(".edit-subject-btn");
    editBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("edit_id").value = this.getAttribute("data-id");
            document.getElementById("edit_subject_name").value = this.getAttribute("data-name");
            document.getElementById("edit_subject_code").value = this.getAttribute("data-code");
            document.getElementById("edit_teacher_id").value = this.getAttribute("data-teacher") || "";
            document.getElementById("edit_class_id").value = this.getAttribute("data-class");
        });
    });

    // Delete details mapping
    const deleteBtns = document.querySelectorAll(".delete-subject-btn");
    deleteBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("delete_id").value = this.getAttribute("data-id");
            document.getElementById("delete_subject_display").innerText = this.getAttribute("data-name");
        });
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
