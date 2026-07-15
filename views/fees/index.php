<?php
/**
 * Fee Management Main Page
 */
$pageTitle = "Fee Management";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole(['admin']);

$db = Database::connect();
$msgError = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        if ($action === 'create_single') {
            $studentId    = (int)($_POST['student_id'] ?? 0);
            $feeType      = trim($_POST['fee_type'] ?? '');
            $amount       = (float)($_POST['amount'] ?? 0);
            $dueDate      = $_POST['due_date'] ?? '';
            $academicYear = trim($_POST['academic_year'] ?? '');

            if (!$studentId || !$feeType || $amount <= 0 || !$dueDate) {
                $msgError = 'All fields are required and amount must be greater than zero.';
            } else {
                if (Fee::create($studentId, $feeType, $amount, $dueDate, $academicYear)) {
                    Utility::setFlash('success', 'Fee invoice created successfully.');
                    redirect('views/fees/index.php');
                } else {
                    $msgError = 'Failed to create fee invoice.';
                }
            }

        } elseif ($action === 'create_bulk') {
            $classId      = (int)($_POST['class_id'] ?? 0);
            $feeType      = trim($_POST['bulk_fee_type'] ?? '');
            $amount       = (float)($_POST['bulk_amount'] ?? 0);
            $dueDate      = $_POST['bulk_due_date'] ?? '';
            $academicYear = trim($_POST['bulk_academic_year'] ?? '');

            if (!$classId || !$feeType || $amount <= 0 || !$dueDate) {
                $msgError = 'All bulk generation fields are required.';
            } else {
                $count = Fee::generateBulk($classId, $feeType, $amount, $dueDate, $academicYear);
                if ($count > 0) {
                    Utility::setFlash('success', "Bulk fee invoice generated for {$count} students.");
                    redirect('views/fees/index.php');
                } else {
                    $msgError = 'No active students found in the selected class, or invoices already exist.';
                }
            }

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (Fee::delete($id)) {
                Utility::setFlash('success', 'Fee invoice deleted.');
                redirect('views/fees/index.php');
            } else {
                $msgError = 'Failed to delete fee invoice.';
            }
        }
    }
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterYear   = $_GET['academic_year'] ?? Setting::get('current_academic_year', '2026-2027');
$filterSearch = $_GET['search'] ?? '';

$filters = [
    'status'        => $filterStatus,
    'academic_year' => $filterYear,
    'search'        => $filterSearch
];

$fees    = Fee::getAll($filters);
$summary = Fee::getSummary($filterYear);
$classes = SchoolClass::getAll();

// Load active students for single invoice form
$stmtStudents = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name, ' (', admission_number, ')') as label FROM students WHERE status = 'active' ORDER BY first_name ASC");
$stmtStudents->execute();
$students = $stmtStudents->fetchAll();

$csrfToken    = Utility::generateCSRFToken();
$currency     = Setting::get('currency', 'USD');

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Fee Management</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-file-invoice-dollar me-2"></i>Fee Management</h2>
    <div>
        <button class="btn btn-outline-primary rounded-3 me-1" data-bs-toggle="modal" data-bs-target="#bulkFeeModal">
            <i class="fas fa-layer-group me-1"></i> Bulk Generate
        </button>
        <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#createFeeModal">
            <i class="fas fa-plus me-1"></i> New Invoice
        </button>
    </div>
</div>

<?php Utility::renderFlash(); ?>
<?php if ($msgError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= e($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">TOTAL BILLED</div>
            <div class="fw-bold fs-5 text-primary"><?= $currency ?> <?= number_format($summary['total_billed'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">COLLECTED</div>
            <div class="fw-bold fs-5 text-success"><?= $currency ?> <?= number_format($summary['total_collected'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">OUTSTANDING</div>
            <div class="fw-bold fs-5 text-danger"><?= $currency ?> <?= number_format($summary['total_outstanding'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">PAID / PARTIAL / UNPAID</div>
            <div class="fw-bold fs-5">
                <span class="text-success"><?= $summary['paid_count'] ?? 0 ?></span> /
                <span class="text-warning"><?= $summary['partial_count'] ?? 0 ?></span> /
                <span class="text-danger"><?= $summary['unpaid_count'] ?? 0 ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card section-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-custom" placeholder="&#xf002; Search student, fee type..." value="<?= e($filterSearch) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-control-custom">
                <option value="">All Statuses</option>
                <option value="unpaid" <?= $filterStatus === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="partial" <?= $filterStatus === 'partial' ? 'selected' : '' ?>>Partial</option>
                <option value="paid" <?= $filterStatus === 'paid' ? 'selected' : '' ?>>Paid</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="academic_year" class="form-control form-control-custom" placeholder="Academic Year" value="<?= e($filterYear) ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-primary-custom text-white" type="submit"><i class="fas fa-filter me-1"></i> Filter</button>
        </div>
    </form>
</div>

<!-- Fee Table -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Fee Type</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-center" style="width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No fee invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $fee):
                        $balance = $fee['amount'] - $fee['paid_amount'];
                        $statusClasses = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
                        $statusClass = $statusClasses[$fee['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($fee['first_name'] . ' ' . $fee['last_name']) ?></strong>
                            <br><small class="text-muted font-monospace"><?= e($fee['admission_number']) ?></small>
                        </td>
                        <td><?= e($fee['fee_type']) ?></td>
                        <td class="fw-bold"><?= $currency ?> <?= number_format($fee['amount'], 2) ?></td>
                        <td class="text-success fw-bold"><?= $currency ?> <?= number_format($fee['paid_amount'], 2) ?></td>
                        <td class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?> fw-bold"><?= $currency ?> <?= number_format(max(0, $balance), 2) ?></td>
                        <td>
                            <?php
                                $dueDate = new \DateTime($fee['due_date']);
                                $today = new \DateTime();
                                $isOverdue = ($fee['status'] !== 'paid' && $dueDate < $today);
                            ?>
                            <span class="<?= $isOverdue ? 'text-danger' : '' ?>">
                                <?= $isOverdue ? '<i class="fas fa-exclamation-circle me-1"></i>' : '' ?>
                                <?= date('M d, Y', strtotime($fee['due_date'])) ?>
                            </span>
                        </td>
                        <td><span class="badge bg-<?= $statusClass ?> text-uppercase rounded-pill"><?= $fee['status'] ?></span></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>views/fees/invoice.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-outline-primary rounded-3 me-1" title="View Invoice">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            <?php if ($fee['status'] !== 'paid'): ?>
                            <a href="<?= BASE_URL ?>views/fees/collect.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-success rounded-3 me-1" title="Record Payment">
                                <i class="fas fa-hand-holding-usd"></i>
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger rounded-3 delete-fee-btn"
                                    data-id="<?= $fee['id'] ?>"
                                    data-name="<?= e($fee['fee_type']) ?> for <?= e($fee['first_name'] . ' ' . $fee['last_name']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#deleteFeeModal"
                                    title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CREATE SINGLE FEE MODAL -->
<div class="modal fade" id="createFeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create_single">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-file-invoice-dollar me-2"></i>New Fee Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">STUDENT</label>
                        <select name="student_id" class="form-select form-control-custom" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">FEE TYPE</label>
                        <input type="text" name="fee_type" class="form-control form-control-custom" placeholder="e.g. Tuition Fee, Activity Fee" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold small text-muted">AMOUNT (<?= $currency ?>)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-custom" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold small text-muted">DUE DATE</label>
                            <input type="date" name="due_date" class="form-control form-control-custom" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">ACADEMIC YEAR</label>
                        <input type="text" name="academic_year" class="form-control form-control-custom" value="<?= e($filterYear) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 text-white px-4">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- BULK FEE MODAL -->
<div class="modal fade" id="bulkFeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create_bulk">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-layer-group me-2"></i>Bulk Fee Generation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 mb-3 small"><i class="fas fa-info-circle me-2"></i>This will generate one fee invoice for every active student in the selected class.</div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">CLASS</label>
                        <select name="class_id" class="form-select form-control-custom" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= $cls['id'] ?>"><?= e($cls['class_name']) ?> - <?= e($cls['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">FEE TYPE</label>
                        <input type="text" name="bulk_fee_type" class="form-control form-control-custom" placeholder="e.g. Monthly Tuition" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold small text-muted">AMOUNT (<?= $currency ?>)</label>
                            <input type="number" step="0.01" min="0.01" name="bulk_amount" class="form-control form-control-custom" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold small text-muted">DUE DATE</label>
                            <input type="date" name="bulk_due_date" class="form-control form-control-custom" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">ACADEMIC YEAR</label>
                        <input type="text" name="bulk_academic_year" class="form-control form-control-custom" value="<?= e($filterYear) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 text-white px-4">Generate Bulk Invoices</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE FEE MODAL -->
<div class="modal fade" id="deleteFeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_fee_id" name="id">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-trash-alt me-2"></i>Delete Fee Invoice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5>Are you sure?</h5>
                    <p class="text-muted">Delete fee: <strong id="delete_fee_name" class="text-danger"></strong><br><small>This will also delete all associated payment records.</small></p>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-3 text-white px-4">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.delete-fee-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_fee_id').value = this.dataset.id;
        document.getElementById('delete_fee_name').textContent = this.dataset.name;
    });
});
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
