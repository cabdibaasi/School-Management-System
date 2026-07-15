<?php
/**
 * Library Book Model
 * Handles book inventory, borrowing, returns, and fine calculations
 */
class Book {

    /** Fine per overdue day in USD */
    const FINE_PER_DAY = 0.50;

    /**
     * Get all books with optional search filter
     */
    public static function getAll($search = '') {
        $db = Database::connect();
        $sql = "SELECT * FROM books";
        $params = [];
        if ($search) {
            $sql .= " WHERE title LIKE :s OR author LIKE :s OR isbn LIKE :s OR category LIKE :s";
            $params['s'] = '%' . $search . '%';
        }
        $sql .= " ORDER BY title ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single book by ID
     */
    public static function getById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM books WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create a new book record
     */
    public static function create($data) {
        $db = Database::connect();
        $stmt = $db->prepare(
            "INSERT INTO books (title, author, isbn, quantity, available_quantity, category, shelf_location)
             VALUES (:title, :author, :isbn, :qty, :avail, :category, :shelf)"
        );
        return $stmt->execute([
            'title'    => $data['title'],
            'author'   => $data['author'],
            'isbn'     => $data['isbn'],
            'qty'      => $data['quantity'],
            'avail'    => $data['quantity'],    // Initially all are available
            'category' => $data['category'] ?? null,
            'shelf'    => $data['shelf_location'] ?? null
        ]);
    }

    /**
     * Update a book record
     */
    public static function update($id, $data) {
        $db = Database::connect();
        // Recalculate available based on updated total - currently borrowed
        $borrowed = self::getBorrowedCount($id);
        $available = max(0, (int)$data['quantity'] - $borrowed);

        $stmt = $db->prepare(
            "UPDATE books SET title = :title, author = :author, isbn = :isbn,
             quantity = :qty, available_quantity = :avail, category = :category, shelf_location = :shelf
             WHERE id = :id"
        );
        return $stmt->execute([
            'title'    => $data['title'],
            'author'   => $data['author'],
            'isbn'     => $data['isbn'],
            'qty'      => $data['quantity'],
            'avail'    => $available,
            'category' => $data['category'] ?? null,
            'shelf'    => $data['shelf_location'] ?? null,
            'id'       => $id
        ]);
    }

    /**
     * Delete a book record
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM books WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get count of currently borrowed copies
     */
    public static function getBorrowedCount($bookId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT COUNT(*) FROM book_borrows WHERE book_id = :id AND status = 'borrowed'");
        $stmt->execute(['id' => $bookId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Issue a book to a student
     * @return array|false ['borrow_id' => int] or false
     */
    public static function issueBook($bookId, $studentId, $dueDays = 14) {
        $db = Database::connect();

        // Check availability
        $book = self::getById($bookId);
        if (!$book || $book['available_quantity'] < 1) return false;

        try {
            $db->beginTransaction();

            $borrowDate = date('Y-m-d');
            $dueDate    = date('Y-m-d', strtotime("+{$dueDays} days"));

            $stmt = $db->prepare(
                "INSERT INTO book_borrows (book_id, student_id, borrow_date, due_date, status)
                 VALUES (:book_id, :student_id, :borrow_date, :due_date, 'borrowed')"
            );
            $stmt->execute([
                'book_id'     => $bookId,
                'student_id'  => $studentId,
                'borrow_date' => $borrowDate,
                'due_date'    => $dueDate
            ]);
            $borrowId = $db->lastInsertId();

            // Decrement available quantity
            $db->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :id")
               ->execute(['id' => $bookId]);

            $db->commit();
            return $borrowId;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Return a borrowed book and calculate fine if overdue
     * @return array ['fine' => float, 'overdue_days' => int]
     */
    public static function returnBook($borrowId) {
        $db = Database::connect();

        $stmt = $db->prepare("SELECT * FROM book_borrows WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $borrowId]);
        $borrow = $stmt->fetch();

        if (!$borrow || $borrow['status'] === 'returned') return false;

        $today      = new \DateTime();
        $due        = new \DateTime($borrow['due_date']);
        $overdueDays = max(0, $today->diff($due)->days * ($today > $due ? 1 : -1));
        $fine       = $overdueDays > 0 ? round($overdueDays * self::FINE_PER_DAY, 2) : 0.00;
        $status     = $overdueDays > 0 ? 'overdue' : 'returned';

        try {
            $db->beginTransaction();

            $db->prepare(
                "UPDATE book_borrows SET return_date = CURDATE(), status = 'returned', fine_amount = :fine WHERE id = :id"
            )->execute(['fine' => $fine, 'id' => $borrowId]);

            // Restore available quantity
            $db->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = :id")
               ->execute(['id' => $borrow['book_id']]);

            $db->commit();
            return ['fine' => $fine, 'overdue_days' => $overdueDays];
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Mark fine as paid for a borrow record
     */
    public static function markFinePaid($borrowId) {
        $db = Database::connect();
        return $db->prepare("UPDATE book_borrows SET fine_paid = TRUE WHERE id = :id")
                  ->execute(['id' => $borrowId]);
    }

    /**
     * Get all borrow records with details
     */
    public static function getAllBorrows(array $filters = []) {
        $db = Database::connect();
        $sql = "SELECT bb.*, b.title, b.author, b.isbn, 
                       s.first_name, s.last_name, s.admission_number
                FROM book_borrows bb
                JOIN books b ON bb.book_id = b.id
                JOIN students s ON bb.student_id = s.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND bb.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['student_id'])) {
            $sql .= " AND bb.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE :s OR s.first_name LIKE :s OR s.last_name LIKE :s OR s.admission_number LIKE :s)";
            $params['s'] = '%' . $filters['search'] . '%';
        }
        $sql .= " ORDER BY bb.borrow_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Refresh overdue statuses — call periodically
     */
    public static function refreshOverdueStatuses() {
        $db = Database::connect();
        $db->exec("UPDATE book_borrows SET status = 'overdue' 
                   WHERE status = 'borrowed' AND due_date < CURDATE()");
    }

    /**
     * Get library statistics
     */
    public static function getStats() {
        $db = Database::connect();
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM books) as total_books,
                    (SELECT SUM(quantity) FROM books) as total_copies,
                    (SELECT SUM(available_quantity) FROM books) as available_copies,
                    (SELECT COUNT(*) FROM book_borrows WHERE status = 'borrowed') as currently_borrowed,
                    (SELECT COUNT(*) FROM book_borrows WHERE status = 'overdue') as overdue_borrows,
                    (SELECT SUM(fine_amount) FROM book_borrows WHERE fine_paid = FALSE AND status IN ('returned','overdue')) as unpaid_fines";
        return $db->query($sql)->fetch();
    }
}
