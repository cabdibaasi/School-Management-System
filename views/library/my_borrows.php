<?php
/**
 * Student Library Portal — View own borrowed books
 */
$pageTitle = "My Borrowed Books";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('student');

Book::refreshOverdueStatuses();

$profileId = Auth::profileId();
$borrows   = Book::getAllBorrows(['student_id' => $profileId]);

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">My Borrowed Books</li>
    </ol>
</nav>

<h2 class="fw-bold mb-4 text-primary"><i class="fas fa-book-reader me-2"></i>My Borrowed Books</h2>

<!-- Browse Library Link -->
<div class="alert border-0 rounded-3 mb-4" style="background:rgba(67,97,238,0.08);">
    <i class="fas fa-info-circle text-primary me-2"></i>
    To borrow a book, visit the <a href="<?= BASE_URL ?>views/library/index.php" class="fw-bold">Library Catalog</a> and ask a teacher or librarian to issue one for you.
</div>

<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Borrowed On</th>
                    <th>Due Date</th>
                    <th>Returned On</th>
                    <th>Status</th>
                    <th class="text-center">Fine</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($borrows)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">You have no borrow records.</td></tr>
                <?php else: ?>
                    <?php foreach ($borrows as $b):
                        $today = new \DateTime();
                        $due   = new \DateTime($b['due_date']);
                        $isOverdue = ($b['status'] !== 'returned' && $due < $today);
                        $statusBadge = [
                            'borrowed' => 'primary',
                            'overdue'  => 'danger',
                            'returned' => 'success'
                        ][$b['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td><strong><?= e($b['title']) ?></strong></td>
                        <td><?= e($b['author']) ?></td>
                        <td class="font-monospace small text-muted"><?= e($b['isbn']) ?></td>
                        <td><?= date('M d, Y', strtotime($b['borrow_date'])) ?></td>
                        <td class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                            <?= $isOverdue ? '<i class="fas fa-exclamation-triangle me-1"></i>' : '' ?>
                            <?= date('M d, Y', strtotime($b['due_date'])) ?>
                        </td>
                        <td><?= $b['return_date'] ? date('M d, Y', strtotime($b['return_date'])) : '—' ?></td>
                        <td><span class="badge bg-<?= $statusBadge ?> rounded-pill text-uppercase"><?= $b['status'] ?></span></td>
                        <td class="text-center">
                            <?php if ($b['fine_amount'] > 0): ?>
                                <span class="<?= $b['fine_paid'] ? 'text-success' : 'text-danger fw-bold' ?> small">
                                    $<?= number_format($b['fine_amount'], 2) ?>
                                    <?= $b['fine_paid'] ? '<br><span class="badge bg-success small">Paid</span>' : '<br><span class="badge bg-danger small">Unpaid</span>' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-success small"><i class="fas fa-check-circle"></i> No fine</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Library Browse Card -->
<div class="mt-4">
    <a href="<?= BASE_URL ?>views/library/index.php" class="btn btn-outline-primary rounded-3">
        <i class="fas fa-book me-2"></i> Browse Full Library Catalog
    </a>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
