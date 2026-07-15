<?php
/**
 * Fee Invoice / Receipt View (Printable)
 */
$pageTitle = "Fee Invoice";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole(['admin', 'student']);

$id = (int)($_GET['id'] ?? 0);

// Students can only see their own invoices
if (Auth::role() === 'student') {
    $profileId = Auth::profileId();
    $fee = Fee::getById($id);
    if (!$fee || $fee['student_id'] != $profileId) {
        Utility::setFlash('danger', 'Access denied.');
        redirect('views/dashboard.php');
    }
} else {
    $fee = Fee::getById($id);
}

if (!$fee) {
    Utility::setFlash('danger', 'Invoice not found.');
    redirect('views/fees/index.php');
}

$payments  = Fee::getPayments($id);
$balance   = max(0, $fee['amount'] - $fee['paid_amount']);
$currency  = Setting::get('currency', 'USD');
$statusClasses = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
$statusClass = $statusClasses[$fee['status']] ?? 'secondary';

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <?php if (Auth::role() !== 'student'): ?>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/fees/index.php">Fee Management</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active">Invoice #<?= str_pad($fee['id'], 6, '0', STR_PAD_LEFT) ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-file-invoice me-2"></i>Fee Invoice</h2>
    <div>
        <button onclick="window.print()" class="btn btn-dark rounded-3 me-1"><i class="fas fa-print me-1"></i> Print / PDF</button>
        <?php if ($fee['status'] !== 'paid' && Auth::role() === 'admin'): ?>
        <a href="<?= BASE_URL ?>views/fees/collect.php?id=<?= $id ?>" class="btn btn-success rounded-3"><i class="fas fa-hand-holding-usd me-1"></i> Record Payment</a>
        <?php endif; ?>
    </div>
</div>

<!-- Invoice Card -->
<div class="card section-card p-4 p-md-5 shadow-lg" style="border-radius:20px; max-width:800px; margin:auto;">

    <!-- Header -->
    <div class="row border-bottom pb-4 mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1"><?= e(Setting::get('school_name', 'St. Andrew Academy')) ?></h3>
            <p class="text-muted small mb-0"><?= e(Setting::get('school_address', '')) ?></p>
            <p class="text-muted small mb-0">Tel: <?= e(Setting::get('school_phone', '')) ?></p>
        </div>
        <div class="col-4 text-end">
            <h4 class="fw-bold text-uppercase text-secondary-color mb-1">INVOICE</h4>
            <span class="badge bg-<?= $statusClass ?> px-3 py-2 rounded-pill text-uppercase fs-6"><?= $fee['status'] ?></span>
            <p class="text-muted small mt-2 mb-0">Invoice #: <strong class="font-monospace"><?= str_pad($fee['id'], 6, '0', STR_PAD_LEFT) ?></strong></p>
        </div>
    </div>

    <!-- Student Details -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <h6 class="text-muted small fw-semibold mb-2">BILLED TO</h6>
            <p class="mb-0 fw-bold"><?= e($fee['first_name'] . ' ' . $fee['last_name']) ?></p>
            <p class="mb-0 small text-muted">Admission #: <?= e($fee['admission_number']) ?></p>
            <p class="mb-0 small text-muted">Class: <?= e($fee['class_name'] . ' - ' . $fee['section']) ?></p>
            <p class="mb-0 small text-muted">Parent: <?= e($fee['parent_name']) ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <h6 class="text-muted small fw-semibold mb-2">INVOICE DETAILS</h6>
            <p class="mb-0 small">Academic Year: <strong><?= e($fee['academic_year']) ?></strong></p>
            <p class="mb-0 small">Due Date: <strong><?= date('M d, Y', strtotime($fee['due_date'])) ?></strong></p>
            <p class="mb-0 small">Generated: <strong><?= date('M d, Y', strtotime($fee['created_at'])) ?></strong></p>
        </div>
    </div>

    <!-- Fee Line Item -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?= e($fee['fee_type']) ?></td>
                    <td class="text-end fw-bold"><?= $currency ?> <?= number_format($fee['amount'], 2) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end fw-semibold">Total Billed:</td>
                    <td class="text-end fw-bold"><?= $currency ?> <?= number_format($fee['amount'], 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end fw-semibold text-success">Total Paid:</td>
                    <td class="text-end fw-bold text-success"><?= $currency ?> <?= number_format($fee['paid_amount'], 2) ?></td>
                </tr>
                <tr class="<?= $balance > 0 ? 'table-danger' : 'table-success' ?>">
                    <td colspan="2" class="text-end fw-bold fs-5">BALANCE DUE:</td>
                    <td class="text-end fw-bold fs-5"><?= $currency ?> <?= number_format($balance, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payment History -->
    <?php if (!empty($payments)): ?>
    <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">PAYMENT HISTORY</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="text-end">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>views/fees/receipt.php?receipt=<?= urlencode($p['receipt_number']) ?>" class="font-monospace small text-primary"><?= e($p['receipt_number']) ?></a></td>
                    <td><?= date('M d, Y H:i', strtotime($p['payment_date'])) ?></td>
                    <td><span class="badge bg-secondary"><?= strtoupper(str_replace('_', ' ', $p['payment_method'])) ?></span></td>
                    <td class="font-monospace small text-muted"><?= e($p['transaction_reference'] ?? '—') ?></td>
                    <td class="text-end fw-bold text-success"><?= $currency ?> <?= number_format($p['amount_paid'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Footer Note -->
    <div class="border-top pt-3 text-center text-muted small">
        <p class="mb-0">Thank you for your prompt payment. For inquiries, contact: <?= e(Setting::get('school_email', '')) ?></p>
    </div>
</div>

<style>
@media print {
    .no-print, nav, #sidebar, .navbar-custom, .btn { display: none !important; }
    #content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    body { background-color: #fff !important; }
    .card { border: 1px solid #ccc !important; box-shadow: none !important; }
}
</style>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
