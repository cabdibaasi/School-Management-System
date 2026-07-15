<?php
/**
 * Teacher Salary Payments / Payroll Management
 */
$pageTitle = "Teacher Payroll & Financials";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('admin');

$db = Database::connect();
$msgSuccess = '';
$msgError = '';

// Handle payment addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid request security token.';
    } else {
        $teacherId     = (int)($_POST['teacher_id'] ?? 0);
        $amount        = (float)($_POST['amount'] ?? 0);
        $paymentDate   = trim($_POST['payment_date'] ?? '');
        $monthPaid     = trim($_POST['month_paid'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $remarks       = trim($_POST['remarks'] ?? '');
        
        $validator = new Validation();
        $validator->required([
            'teacher_id' => 'Teacher',
            'amount' => 'Payment Amount',
            'payment_date' => 'Payment Date',
            'month_paid' => 'Month Paid',
            'payment_method' => 'Payment Method'
        ], $_POST);
        
        if ($validator->passes()) {
            if ($amount <= 0) {
                $msgError = 'Payment amount must be greater than 0.';
            } else {
                if (TeacherPayment::create($teacherId, $amount, $paymentDate, $monthPaid, $paymentMethod, $remarks)) {
                    Utility::setFlash('success', 'Salary payment recorded successfully.');
                    redirect('views/teachers/payments.php');
                } else {
                    $msgError = 'Failed to record payment.';
                }
            }
        } else {
            $errors = $validator->getErrors();
            $msgError = reset($errors);
        }
    }
}

// Handle payment deletion
if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    if (TeacherPayment::delete($deleteId)) {
        Utility::setFlash('success', 'Payroll record deleted successfully.');
    } else {
        Utility::setFlash('danger', 'Failed to delete payroll record.');
    }
    redirect('views/teachers/payments.php');
}

// Fetch all active teachers for the select dropdown
$teachers = $db->query("SELECT id, full_name, employee_id, salary FROM teachers WHERE status = 'active' ORDER BY full_name ASC")->fetchAll();

// Fetch payroll history
$history = TeacherPayment::getHistory();

// Financial Calculations
$totalFeesCollected = (float)$db->query("SELECT IFNULL(SUM(amount_paid), 0) FROM fee_payments")->fetchColumn();
$totalSalariesPaid   = TeacherPayment::getTotalPaid();
$netFunds            = $totalFeesCollected - $totalSalariesPaid;

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/teachers/index.php">Teachers</a></li>
        <li class="breadcrumb-item active" aria-current="page">Payroll</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-file-invoice-dollar me-2"></i>Teacher Payroll & Financials</h2>
    <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#paySalaryModal">
        <i class="fas fa-hand-holding-usd me-2"></i> Pay Salary / Payroll
    </button>
</div>

<!-- Alert messages -->
<?php if (!empty($msgError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msgError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Financial Summary Cards -->
<div class="row mb-4">
    <!-- Card: Cash Received (Student Fees) -->
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card metric-card p-3" style="border-left: 5px solid var(--bs-success);">
            <div class="d-flex align-items-center">
                <div class="metric-icon-wrapper bg-success-light me-3">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1">Total Money Received (Fees)</h6>
                    <h3 class="fw-bold text-success mb-0">$<?= number_format($totalFeesCollected, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card: Cash Paid Out (Teacher Salary) -->
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card metric-card p-3" style="border-left: 5px solid var(--bs-danger);">
            <div class="d-flex align-items-center">
                <div class="metric-icon-wrapper bg-danger-light me-3">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1">Total Money Paid (Salary)</h6>
                    <h3 class="fw-bold text-danger mb-0">$<?= number_format($totalSalariesPaid, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Net Cash Balance -->
    <div class="col-md-4">
        <div class="card metric-card p-3" style="border-left: 5px solid var(--bs-primary);">
            <div class="d-flex align-items-center">
                <div class="metric-icon-wrapper bg-blue-light me-3">
                    <i class="fas fa-university"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-1">Total Net Funds (Available)</h6>
                    <h3 class="fw-bold text-primary mb-0">$<?= number_format($netFunds, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payroll History Table Card -->
<div class="card section-card">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history me-2 text-primary"></i>Salary Payout History</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Teacher ID</th>
                    <th>Teacher Name</th>
                    <th>Salary Month</th>
                    <th>Base Salary</th>
                    <th>Amount Paid</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Remarks</th>
                    <th style="width: 100px;" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No salary payment records found in the database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="font-monospace text-primary small"><?= e($h['employee_id']) ?></td>
                            <td><strong><?= e($h['full_name']) ?></strong></td>
                            <td class="fw-semibold text-dark"><?= date('F Y', strtotime($h['month_paid'] . '-01')) ?></td>
                            <td class="text-muted">$<?= number_format($h['base_salary'], 2) ?></td>
                            <td class="fw-bold text-danger">$<?= number_format($h['amount'], 2) ?></td>
                            <td><?= e($h['payment_date']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary rounded-pill px-3"><?= e($h['payment_method']) ?></span></td>
                            <td><span class="small text-muted"><?= e($h['remarks'] ?: '-') ?></span></td>
                            <td class="text-center">
                                <a href="?delete_id=<?= $h['id'] ?>" class="btn btn-outline-danger btn-sm rounded-3" onclick="return confirm('Are you sure you want to remove this payroll entry?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Pay Salary -->
<div class="modal fade" id="paySalaryModal" tabindex="-1" aria-labelledby="paySalaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="paySalaryModalLabel">
                    <i class="fas fa-money-check-alt me-2"></i>Record Salary Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="pay">
                
                <div class="modal-body py-4">
                    <!-- Teacher Select -->
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label fw-bold">Select Active Teacher</label>
                        <select class="form-select form-control-custom" id="teacher_id" name="teacher_id" required onchange="updateSalaryField()">
                            <option value="" data-salary="0">-- Choose Teacher --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" data-salary="<?= $t['salary'] ?>">
                                    <?= e($t['full_name']) ?> (<?= e($t['employee_id']) ?> - Base: $<?= number_format($t['salary'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <!-- Salary Month -->
                        <div class="col-6 mb-3">
                            <label for="month_paid" class="form-label fw-bold">Salary Month</label>
                            <input type="month" class="form-control form-control-custom" id="month_paid" name="month_paid" value="<?= date('Y-m') ?>" required>
                        </div>
                        <!-- Payout Date -->
                        <div class="col-6 mb-3">
                            <label for="payment_date" class="form-label fw-bold">Payment Date</label>
                            <input type="date" class="form-control form-control-custom" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Amount Paid -->
                        <div class="col-6 mb-3">
                            <label for="amount" class="form-label fw-bold">Amount Paid ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-custom" id="amount" name="amount" required placeholder="0.00">
                        </div>
                        <!-- Method -->
                        <div class="col-6 mb-3">
                            <label for="payment_method" class="form-label fw-bold">Payment Method</label>
                            <select class="form-select form-control-custom" id="payment_method" name="payment_method" required>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Card">Card</option>
                            </select>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="mb-0">
                        <label for="remarks" class="form-label fw-bold">Remarks / Notes</label>
                        <textarea class="form-control form-control-custom" id="remarks" name="remarks" rows="2" placeholder="e.g. Regular monthly salary payout"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-primary-custom text-white px-4">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Automatically update amount paid to match the teacher's base salary
 */
function updateSalaryField() {
    const select = document.getElementById('teacher_id');
    const selectedOption = select.options[select.selectedIndex];
    const baseSalary = selectedOption.getAttribute('data-salary');
    
    if (baseSalary) {
        document.getElementById('amount').value = parseFloat(baseSalary).toFixed(2);
    }
}
</script>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
