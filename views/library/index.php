<?php
/**
 * Library Book Inventory Management Page
 */
$pageTitle = "Library Management";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole(['admin', 'teacher', 'student']);

$db = Database::connect();
$msgError = '';

// Handle CRUD actions (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::role() === 'admin') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!Utility::validateCSRFToken($token)) {
        $msgError = 'Invalid security token.';
    } else {
        if ($action === 'create') {
            $data = [
                'title'          => trim($_POST['title'] ?? ''),
                'author'         => trim($_POST['author'] ?? ''),
                'isbn'           => trim($_POST['isbn'] ?? ''),
                'quantity'       => (int)($_POST['quantity'] ?? 1),
                'category'       => trim($_POST['category'] ?? ''),
                'shelf_location' => trim($_POST['shelf_location'] ?? '')
            ];
            if (!$data['title'] || !$data['author'] || !$data['isbn']) {
                $msgError = 'Title, Author and ISBN are required.';
            } else {
                if (Book::create($data)) {
                    Utility::setFlash('success', 'Book added to library.');
                    redirect('views/library/index.php');
                } else {
                    $msgError = 'Failed to add book. ISBN may already exist.';
                }
            }

        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $data = [
                'title'          => trim($_POST['title'] ?? ''),
                'author'         => trim($_POST['author'] ?? ''),
                'isbn'           => trim($_POST['isbn'] ?? ''),
                'quantity'       => (int)($_POST['quantity'] ?? 1),
                'category'       => trim($_POST['category'] ?? ''),
                'shelf_location' => trim($_POST['shelf_location'] ?? '')
            ];
            if (Book::update($id, $data)) {
                Utility::setFlash('success', 'Book updated successfully.');
                redirect('views/library/index.php');
            } else {
                $msgError = 'Failed to update book record.';
            }

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if (Book::delete($id)) {
                Utility::setFlash('success', 'Book removed from library.');
                redirect('views/library/index.php');
            } else {
                $msgError = 'Failed to delete book.';
            }
        }
    }
}

// Refresh overdue statuses
Book::refreshOverdueStatuses();

$search  = $_GET['search'] ?? '';
$books   = Book::getAll($search);
$stats   = Book::getStats();
$csrfToken = Utility::generateCSRFToken();

require_once ROOT_PATH . 'views/layouts/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Library</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0 text-primary"><i class="fas fa-book me-2"></i>Library Management</h2>
    <div>
        <a href="<?= BASE_URL ?>views/library/borrows.php" class="btn btn-outline-primary rounded-3 me-1">
            <i class="fas fa-exchange-alt me-1"></i> Borrow / Return
        </a>
        <?php if (Auth::role() === 'admin'): ?>
        <button class="btn btn-primary btn-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#addBookModal">
            <i class="fas fa-plus me-1"></i> Add Book
        </button>
        <?php endif; ?>
    </div>
</div>

<?php Utility::renderFlash(); ?>
<?php if ($msgError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= e($msgError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <?php $statItems = [
        ['label' => 'TOTAL TITLES', 'value' => $stats['total_books'] ?? 0, 'icon' => 'fas fa-book', 'color' => 'text-primary'],
        ['label' => 'TOTAL COPIES', 'value' => $stats['total_copies'] ?? 0, 'icon' => 'fas fa-copy', 'color' => 'text-info'],
        ['label' => 'AVAILABLE', 'value' => $stats['available_copies'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'text-success'],
        ['label' => 'BORROWED', 'value' => $stats['currently_borrowed'] ?? 0, 'icon' => 'fas fa-book-open', 'color' => 'text-warning'],
        ['label' => 'OVERDUE', 'value' => $stats['overdue_borrows'] ?? 0, 'icon' => 'fas fa-exclamation-triangle', 'color' => 'text-danger'],
        ['label' => 'UNPAID FINES', 'value' => '$' . number_format($stats['unpaid_fines'] ?? 0, 2), 'icon' => 'fas fa-dollar-sign', 'color' => 'text-danger'],
    ];
    foreach ($statItems as $item): ?>
    <div class="col-6 col-md-2">
        <div class="card section-card text-center py-3 px-2">
            <i class="<?= $item['icon'] ?> <?= $item['color'] ?> mb-1"></i>
            <div class="fw-bold fs-6 <?= $item['color'] ?>"><?= $item['value'] ?></div>
            <div class="text-muted" style="font-size:0.7rem;"><?= $item['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search bar -->
<form method="GET" class="mb-4">
    <div class="input-group" style="max-width:400px;">
        <input type="text" name="search" class="form-control form-control-custom" placeholder="Search by title, author, ISBN, category..." value="<?= e($search) ?>">
        <button class="btn btn-primary btn-primary-custom text-white" type="submit"><i class="fas fa-search"></i></button>
    </div>
</form>

<!-- Books Table -->
<div class="card section-card">
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Shelf</th>
                    <th class="text-center">Copies</th>
                    <th class="text-center">Available</th>
                    <?php if (Auth::role() === 'admin'): ?>
                    <th class="text-center" style="width:140px;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No books found in library.</td></tr>
                <?php else: ?>
                    <?php foreach ($books as $b): ?>
                    <tr>
                        <td><strong><?= e($b['title']) ?></strong></td>
                        <td><?= e($b['author']) ?></td>
                        <td class="font-monospace small text-muted"><?= e($b['isbn']) ?></td>
                        <td><?= e($b['category'] ?? '—') ?></td>
                        <td><?= e($b['shelf_location'] ?? '—') ?></td>
                        <td class="text-center"><?= $b['quantity'] ?></td>
                        <td class="text-center">
                            <span class="badge <?= $b['available_quantity'] > 0 ? 'bg-success' : 'bg-danger' ?> rounded-pill"><?= $b['available_quantity'] ?></span>
                        </td>
                        <?php if (Auth::role() === 'admin'): ?>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary rounded-3 me-1 edit-book-btn"
                                    data-id="<?= $b['id'] ?>"
                                    data-title="<?= e($b['title']) ?>"
                                    data-author="<?= e($b['author']) ?>"
                                    data-isbn="<?= e($b['isbn']) ?>"
                                    data-qty="<?= $b['quantity'] ?>"
                                    data-category="<?= e($b['category']) ?>"
                                    data-shelf="<?= e($b['shelf_location']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#editBookModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger rounded-3 delete-book-btn"
                                    data-id="<?= $b['id'] ?>"
                                    data-name="<?= e($b['title']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#deleteBookModal"
                                    title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (Auth::role() === 'admin'): ?>
<!-- ADD BOOK MODAL -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-plus-circle me-2"></i>Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">TITLE *</label><input type="text" name="title" class="form-control form-control-custom" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">AUTHOR *</label><input type="text" name="author" class="form-control form-control-custom" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">ISBN *</label><input type="text" name="isbn" class="form-control form-control-custom" required></div>
                    <div class="row g-2">
                        <div class="col-6 mb-3"><label class="form-label fw-semibold small text-muted">QUANTITY</label><input type="number" name="quantity" class="form-control form-control-custom" value="1" min="1" required></div>
                        <div class="col-6 mb-3"><label class="form-label fw-semibold small text-muted">SHELF LOCATION</label><input type="text" name="shelf_location" class="form-control form-control-custom" placeholder="e.g. A-12"></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">CATEGORY</label><input type="text" name="category" class="form-control form-control-custom" placeholder="e.g. Science, Fiction"></div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 text-white px-4">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT BOOK MODAL -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_book_id" name="id">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-edit me-2"></i>Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">TITLE *</label><input type="text" id="edit_title" name="title" class="form-control form-control-custom" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">AUTHOR *</label><input type="text" id="edit_author" name="author" class="form-control form-control-custom" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">ISBN *</label><input type="text" id="edit_isbn" name="isbn" class="form-control form-control-custom" required></div>
                    <div class="row g-2">
                        <div class="col-6 mb-3"><label class="form-label fw-semibold small text-muted">QUANTITY</label><input type="number" id="edit_qty" name="quantity" class="form-control form-control-custom" min="1" required></div>
                        <div class="col-6 mb-3"><label class="form-label fw-semibold small text-muted">SHELF LOCATION</label><input type="text" id="edit_shelf" name="shelf_location" class="form-control form-control-custom"></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold small text-muted">CATEGORY</label><input type="text" id="edit_category" name="category" class="form-control form-control-custom"></div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 text-white px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE BOOK MODAL -->
<div class="modal fade" id="deleteBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px;border:none;">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_book_id" name="id">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-trash-alt me-2"></i>Delete Book</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size:3rem;"></i>
                    <h5>Delete Book?</h5>
                    <p class="text-muted">Remove "<strong id="delete_book_name"></strong>" from library? This will delete all associated borrow records.</p>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-3 text-white px-4">Delete Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-book-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_book_id').value = this.dataset.id;
        document.getElementById('edit_title').value = this.dataset.title;
        document.getElementById('edit_author').value = this.dataset.author;
        document.getElementById('edit_isbn').value = this.dataset.isbn;
        document.getElementById('edit_qty').value = this.dataset.qty;
        document.getElementById('edit_category').value = this.dataset.category;
        document.getElementById('edit_shelf').value = this.dataset.shelf;
    });
});
document.querySelectorAll('.delete-book-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_book_id').value = this.dataset.id;
        document.getElementById('delete_book_name').textContent = this.dataset.name;
    });
});
</script>
<?php endif; ?>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
