<?php
/**
 * Fee Payment Collection Page
 */
$pageTitle = "Collect Payment";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('admin');

$id  = (int)($_GET['id'] ?? 0);
$fee = Fee::getById($id);

if (!$fee) {
    Utility::setFlash('danger', 'Fee invoice not found.');
    redirect('views/fees/index.php');
}

$balance  = max(0, $fee['amount'] - $fee['paid_amount']);
$currency = Setting::get('currency', 'USD');
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid security token.';
    } else {
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $method     = $_POST['payment_method'] ?? '';
        $reference  = trim($_POST['transaction_reference'] ?? '');

        if ($amountPaid <= 0) {
            $msgError = 'Payment amount must be greater than zero.';
        } elseif ($amountPaid > $balance) {
            $msgError = "Payment amount ({$currency} " . number_format($amountPaid, 2) . ") exceeds the outstanding balance ({$currency} " . number_format($balance, 2) . ").";
        } elseif (!in_array($method, ['cash', 'card', 'bank_transfer', 'cheque'])) {
            $msgError = 'Please select a valid payment method.';
        } else {
            $receiptNo = Fee::recordPayment($id, $amountPaid, $method, $reference);
            if ($receiptNo) {
                Utility::setFlash('success', "Payment recorded! Receipt: <strong>{$receiptNo}</strong>.");
                redirect("views/fees/receipt.php?receipt=" . urlencode($receiptNo));
            } else {
                $msgError = 'Failed to record payment.';
            }
        }
    }
}

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/fees/index.php">Fee Management</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/fees/invoice.php?id=<?= $id ?>">Invoice</a></li>
        <li class="breadcrumb-item active">Collect Payment</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-hand-holding-usd me-2"></i>Collect Fee Payment</h2>

<?php if ($msgError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= e($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Payment Form -->
    <div class="col-md-7">
        <div class="card section-card p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-money-bill-wave me-2 text-success"></i>Payment Details</h5>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">AMOUNT TO PAY (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0.01" max="<?= $balance ?>" name="amount_paid" 
                           class="form-control form-control-custom form-control-lg fw-bold" 
                           value="<?= $balance ?>" required>
                    <div class="form-text">Outstanding balance: <strong class="text-danger"><?= $currency ?> <?= number_format($balance, 2) ?></strong></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">PAYMENT METHOD</label>
                    <div class="row g-2">
                        <?php foreach (['cash' => 'fas fa-money-bill', 'card' => 'fas fa-credit-card', 'bank_transfer' => 'fas fa-university', 'cheque' => 'fas fa-file-alt'] as $val => $icon): ?>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="payment_method" id="method_<?= $val ?>" value="<?= $val ?>" required>
                            <label class="btn btn-outline-secondary w-100 text-start rounded-3" for="method_<?= $val ?>">
                                <i class="<?= $icon ?> me-2"></i><?= ucwords(str_replace('_', ' ', $val)) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-muted">TRANSACTION REFERENCE <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="text" name="transaction_reference" class="form-control form-control-custom" placeholder="Bank reference, cheque no., etc.">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg rounded-3 fw-bold">
                        <i class="fas fa-check-circle me-2"></i>Confirm & Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Invoice Summary -->
    <div class="col-md-5">
        <div class="card section-card p-4">
            <h5 class="fw-bold mb-4"><i class="fas fa-receipt me-2 text-primary"></i>Invoice Summary</h5>
            <div class="mb-3 pb-3 border-bottom">
                <span class="text-muted small">STUDENT</span>
                <p class="mb-0 fw-bold mt-1"><?= e($fee['first_name'] . ' ' . $fee['last_name']) ?></p>
                <p class="mb-0 small text-muted"><?= e($fee['admission_number']) ?> — <?= e($fee['class_name'] . ' ' . $fee['section']) ?></p>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Fee Type</span>
                <strong><?= e($fee['fee_type']) ?></strong>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Total Billed</span>
                <strong><?= $currency ?> <?= number_format($fee['amount'], 2) ?></strong>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Total Paid</span>
                <strong class="text-success"><?= $currency ?> <?= number_format($fee['paid_amount'], 2) ?></strong>
            </div>
            <div class="mb-3 d-flex justify-content-between border-top pt-3">
                <span class="fw-bold">Balance Due</span>
                <strong class="text-danger fs-5"><?= $currency ?> <?= number_format($balance, 2) ?></strong>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Due Date</span>
                <strong><?= date('M d, Y', strtotime($fee['due_date'])) ?></strong>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Status</span>
                <span class="badge bg-<?= ['unpaid'=>'danger','partial'=>'warning','paid'=>'success'][$fee['status']] ?? 'secondary' ?> text-uppercase rounded-pill"><?= $fee['status'] ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
