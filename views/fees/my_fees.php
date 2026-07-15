<?php
/**
 * Student Fee Portal — View own invoices and payments
 */
$pageTitle = "My Fees & Payments";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('student');

$profileId = Auth::profileId();
$fees      = Fee::getForStudent($profileId);
$currency  = Setting::get('currency', 'USD');

// Summary
$totalBilled = array_sum(array_column($fees, 'amount'));
$totalPaid   = array_sum(array_column($fees, 'paid_amount'));
$outstanding = max(0, $totalBilled - $totalPaid);

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">My Fees & Payments</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-receipt me-2"></i>My Fees & Payments</h2>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">TOTAL BILLED</div>
            <div class="fw-bold fs-5 text-primary"><?= $currency ?> <?= number_format($totalBilled, 2) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">TOTAL PAID</div>
            <div class="fw-bold fs-5 text-success"><?= $currency ?> <?= number_format($totalPaid, 2) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card section-card text-center py-3">
            <div class="text-muted small mb-1">OUTSTANDING BALANCE</div>
            <div class="fw-bold fs-5 <?= $outstanding > 0 ? 'text-danger' : 'text-success' ?>"><?= $currency ?> <?= number_format($outstanding, 2) ?></div>
        </div>
    </div>
</div>

<!-- Fee Invoices -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Fee Type</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Due Date</th>
                    <th>Academic Year</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No fee invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $fee):
                        $balance = max(0, $fee['amount'] - $fee['paid_amount']);
                        $statusClasses = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
                        $statusClass = $statusClasses[$fee['status']] ?? 'secondary';
                        $dueDate = new \DateTime($fee['due_date']);
                        $today = new \DateTime();
                        $isOverdue = ($fee['status'] !== 'paid' && $dueDate < $today);
                    ?>
                    <tr>
                        <td><strong><?= e($fee['fee_type']) ?></strong></td>
                        <td><?= $currency ?> <?= number_format($fee['amount'], 2) ?></td>
                        <td class="text-success fw-bold"><?= $currency ?> <?= number_format($fee['paid_amount'], 2) ?></td>
                        <td class="<?= $balance > 0 ? 'text-danger fw-bold' : 'text-success' ?>"><?= $currency ?> <?= number_format($balance, 2) ?></td>
                        <td class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                            <?= $isOverdue ? '<i class="fas fa-exclamation-circle me-1"></i>' : '' ?>
                            <?= date('M d, Y', strtotime($fee['due_date'])) ?>
                        </td>
                        <td><?= e($fee['academic_year']) ?></td>
                        <td><span class="badge bg-<?= $statusClass ?> text-uppercase rounded-pill"><?= $fee['status'] ?></span></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>views/fees/invoice.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-outline-primary rounded-3" title="View Invoice">
                                <i class="fas fa-file-invoice"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
