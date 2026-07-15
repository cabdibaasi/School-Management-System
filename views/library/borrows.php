<?php
/**
 * Book Borrow & Return Management
 */
$pageTitle = "Book Borrows & Returns";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole(['admin', 'teacher', 'student']);

$db = Database::connect();
$msgError   = '';
$msgSuccess = '';

// Handle borrow/return actions (admin/teacher only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(Auth::role(), ['admin', 'teacher'])) {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid security token.';
    } else {
        if ($action === 'issue') {
            $bookId    = (int)($_POST['book_id'] ?? 0);
            $studentId = (int)($_POST['student_id'] ?? 0);
            $dueDays   = (int)($_POST['due_days'] ?? 14);

            if (!$bookId || !$studentId) {
                $msgError = 'Please select a book and student.';
            } else {
                $result = Book::issueBook($bookId, $studentId, $dueDays);
                if ($result) {
                    Utility::setFlash('success', 'Book issued successfully.');
                    redirect('views/library/borrows.php');
                } else {
                    $msgError = 'Failed to issue book. It may not be available.';
                }
            }

        } elseif ($action === 'return') {
            $borrowId = (int)($_POST['borrow_id'] ?? 0);
            $result   = Book::returnBook($borrowId);
            if ($result !== false) {
                if ($result['fine'] > 0) {
                    Utility::setFlash('warning', "Book returned! Overdue by {$result['overdue_days']} days. Fine: \${$result['fine']}.");
                } else {
                    Utility::setFlash('success', 'Book returned successfully. No fine.');
                }
                redirect('views/library/borrows.php');
            } else {
                $msgError = 'Failed to process book return.';
            }

        } elseif ($action === 'fine_paid') {
            $borrowId = (int)($_POST['borrow_id'] ?? 0);
            if (Book::markFinePaid($borrowId)) {
                Utility::setFlash('success', 'Fine marked as paid.');
                redirect('views/library/borrows.php');
            } else {
                $msgError = 'Failed to update fine status.';
            }
        }
    }
}

// Filters
Book::refreshOverdueStatuses();
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['search'] ?? '';

$borrows = Book::getAllBorrows([
    'status' => $filterStatus,
    'student_id' => (Auth::role() === 'student') ? Auth::profileId() : '',
    'search' => $filterSearch
]);

// For issue form (admin/teacher only)
$books    = [];
$students = [];
if (in_array(Auth::role(), ['admin', 'teacher'])) {
    $books = Book::getAll();
    $stmtS = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name, ' (', admission_number, ')') as label FROM students WHERE status = 'active' ORDER BY first_name ASC");
    $stmtS->execute();
    $students = $stmtS->fetchAll();
}

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/library/index.php">Library</a></li>
        <li class="breadcrumb-item active">Borrows & Returns</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-exchange-alt me-2"></i>Book Borrows & Returns</h2>
    <?php if (in_array(Auth::role(), ['admin', 'teacher'])): ?>
    <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#issueBookModal">
        <i class="fas fa-book-open me-1"></i> Issue Book
    </button>
    <?php endif; ?>
</div>

<?php Utility::renderFlash(); ?>
<?php if ($msgError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= e($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filters -->
<div class="card section-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control form-control-custom" placeholder="Search by book title, student name, admission #..." value="<?= e($filterSearch) ?>">
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select form-control-custom">
                <option value="">All Statuses</option>
                <option value="borrowed" <?= $filterStatus === 'borrowed' ? 'selected' : '' ?>>Currently Borrowed</option>
                <option value="overdue" <?= $filterStatus === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                <option value="returned" <?= $filterStatus === 'returned' ? 'selected' : '' ?>>Returned</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary btn-primary-custom text-white" type="submit"><i class="fas fa-filter me-1"></i> Filter</button>
        </div>
    </form>
</div>

<!-- Borrows Table -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Student</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th class="text-center">Fine</th>
                    <?php if (in_array(Auth::role(), ['admin', 'teacher'])): ?>
                    <th class="text-center" style="width:160px;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($borrows)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No borrow records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($borrows as $b):
                        $today = new \DateTime();
                        $due   = new \DateTime($b['due_date']);
                        $statusBadge = [
                            'borrowed' => 'primary',
                            'overdue'  => 'danger',
                            'returned' => 'success'
                        ][$b['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($b['title']) ?></strong>
                            <br><small class="text-muted font-monospace"><?= e($b['isbn']) ?></small>
                        </td>
                        <td>
                            <?= e($b['first_name'] . ' ' . $b['last_name']) ?>
                            <br><small class="text-muted"><?= e($b['admission_number']) ?></small>
                        </td>
                        <td><?= date('M d, Y', strtotime($b['borrow_date'])) ?></td>
                        <td class="<?= ($b['status'] !== 'returned' && $due < $today) ? 'text-danger fw-bold' : '' ?>">
                            <?= date('M d, Y', strtotime($b['due_date'])) ?>
                        </td>
                        <td><?= $b['return_date'] ? date('M d, Y', strtotime($b['return_date'])) : '—' ?></td>
                        <td><span class="badge bg-<?= $statusBadge ?> rounded-pill text-uppercase"><?= $b['status'] ?></span></td>
                        <td class="text-center">
                            <?php if ($b['fine_amount'] > 0): ?>
                                <span class="<?= $b['fine_paid'] ? 'text-success' : 'text-danger' ?> fw-bold small">
                                    $<?= number_format($b['fine_amount'], 2) ?>
                                    <?= $b['fine_paid'] ? '<i class="fas fa-check-circle" title="Paid"></i>' : '' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if (in_array(Auth::role(), ['admin', 'teacher'])): ?>
                        <td class="text-center">
                            <?php if ($b['status'] !== 'returned'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="borrow_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success rounded-3 me-1" title="Mark Returned"
                                        onclick="return confirm('Mark this book as returned?')">
                                    <i class="fas fa-undo-alt"></i> Return
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($b['fine_amount'] > 0 && !$b['fine_paid']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="fine_paid">
                                <input type="hidden" name="borrow_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-warning rounded-3" title="Mark Fine Paid">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (in_array(Auth::role(), ['admin', 'teacher'])): ?>
<!-- ISSUE BOOK MODAL -->
<div class="modal fade" id="issueBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="issue">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-book-open me-2"></i>Issue Book to Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">BOOK (Available copies shown)</label>
                        <select name="book_id" class="form-select form-control-custom" required>
                            <option value="">-- Select Book --</option>
                            <?php foreach ($books as $book): ?>
                            <option value="<?= $book['id'] ?>" <?= $book['available_quantity'] < 1 ? 'disabled' : '' ?>>
                                <?= e($book['title']) ?> — <?= e($book['author']) ?> 
                                (Available: <?= $book['available_quantity'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                        <label class="form-label fw-semibold small text-muted">RETURN PERIOD (DAYS)</label>
                        <input type="number" name="due_days" class="form-control form-control-custom" value="14" min="1" max="90">
                        <div class="form-text">Fine for overdue: $<?= Book::FINE_PER_DAY ?> per day.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 text-white px-4">Issue Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
