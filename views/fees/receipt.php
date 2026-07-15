<?php
/**
 * Payment Receipt (Printable)
 */
$pageTitle = "Payment Receipt";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole(['admin', 'student']);

$receiptNo = $_GET['receipt'] ?? '';
if (!$receiptNo) {
    redirect('views/fees/index.php');
}

$receipt = Fee::getByReceipt($receiptNo);
if (!$receipt) {
    Utility::setFlash('danger', 'Receipt not found.');
    redirect('views/fees/index.php');
}

// Students can only see their own receipts
if (Auth::role() === 'student') {
    // Verify ownership via admission number
    $profileId = Auth::profileId();
    $stmt = Database::connect()->prepare("SELECT id FROM students WHERE id = :id AND user_id = (SELECT id FROM users WHERE id = :uid) LIMIT 1");
    // Simple: get student by user_id
    $stmtCheck = Database::connect()->prepare("SELECT s.id FROM students s JOIN fees f ON s.id = f.student_id JOIN fee_payments fp ON f.id = fp.fee_id WHERE fp.receipt_number = :receipt AND s.id = :sid LIMIT 1");
    $stmtCheck->execute(['receipt' => $receiptNo, 'sid' => $profileId]);
    if (!$stmtCheck->fetch()) {
        Utility::setFlash('danger', 'Access denied.');
        redirect('views/dashboard.php');
    }
}

$currency = Setting::get('currency', 'USD');
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <?php if (Auth::role() !== 'student'): ?>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/fees/index.php">Fee Management</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active">Receipt</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-receipt me-2"></i>Payment Receipt</h2>
    <button onclick="window.print()" class="btn btn-dark rounded-3"><i class="fas fa-print me-1"></i> Print / PDF</button>
</div>

<!-- Receipt Card -->
<div class="card section-card p-4 p-md-5 shadow-lg" style="border-radius:20px; max-width:700px; margin:auto;">
    
    <!-- Receipt Header with green tick -->
    <div class="text-center border-bottom pb-4 mb-4">
        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;font-size:2rem;">
            <i class="fas fa-check"></i>
        </div>
        <h3 class="fw-bold text-primary"><?= e(Setting::get('school_name', 'St. Andrew Academy')) ?></h3>
        <p class="text-muted small mb-1"><?= e(Setting::get('school_address', '')) ?></p>
        <h5 class="fw-bold text-uppercase text-muted mt-2 mb-0">Official Payment Receipt</h5>
    </div>

    <!-- Receipt Details -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <span class="text-muted small d-block">RECEIPT NUMBER</span>
            <strong class="font-monospace text-primary"><?= e($receipt['receipt_number']) ?></strong>
        </div>
        <div class="col-6 text-end">
            <span class="text-muted small d-block">PAYMENT DATE</span>
            <strong><?= date('M d, Y H:i', strtotime($receipt['payment_date'])) ?></strong>
        </div>
        <div class="col-6">
            <span class="text-muted small d-block">STUDENT NAME</span>
            <strong><?= e($receipt['first_name'] . ' ' . $receipt['last_name']) ?></strong>
        </div>
        <div class="col-6 text-end">
            <span class="text-muted small d-block">ADMISSION NO.</span>
            <strong class="font-monospace"><?= e($receipt['admission_number']) ?></strong>
        </div>
        <div class="col-6">
            <span class="text-muted small d-block">CLASS</span>
            <strong><?= e($receipt['class_name'] . ' - ' . $receipt['section']) ?></strong>
        </div>
        <div class="col-6 text-end">
            <span class="text-muted small d-block">ACADEMIC YEAR</span>
            <strong><?= e($receipt['academic_year']) ?></strong>
        </div>
    </div>

    <!-- Payment Line -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th>Method</th>
                    <th class="text-end">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= e($receipt['fee_type']) ?></td>
                    <td><span class="badge bg-secondary"><?= strtoupper(str_replace('_', ' ', $receipt['payment_method'])) ?></span>
                        <?php if ($receipt['transaction_reference']): ?>
                        <br><span class="font-monospace small text-muted"><?= e($receipt['transaction_reference']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold text-success fs-5"><?= $currency ?> <?= number_format($receipt['amount_paid'], 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="border-top pt-3 text-center text-muted small">
        <p class="mb-1">This is an official receipt. Please retain it for your records.</p>
        <p class="mb-0"><?= e(Setting::get('school_email', '')) ?> | <?= e(Setting::get('school_phone', '')) ?></p>
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
