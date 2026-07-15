<?php
/**
 * Fee Model
 * Handles fee invoices, payments, and receipt operations
 */
class Fee {

    /**
     * Get all fees with student details, filterable
     * @param array $filters ['student_id', 'status', 'academic_year']
     * @return array
     */
    public static function getAll(array $filters = []) {
        $db = Database::connect();
        $sql = "SELECT f.*, 
                       s.first_name, s.last_name, s.admission_number,
                       c.class_name, c.section,
                       COALESCE(SUM(p.amount_paid), 0) as paid_amount
                FROM fees f
                JOIN students s ON f.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN fee_payments p ON f.id = p.fee_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['student_id'])) {
            $sql .= " AND f.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND f.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['academic_year'])) {
            $sql .= " AND f.academic_year = :academic_year";
            $params['academic_year'] = $filters['academic_year'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.admission_number LIKE :search OR f.fee_type LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " GROUP BY f.id ORDER BY f.due_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single fee invoice by ID
     */
    public static function getById($id) {
        $db = Database::connect();
        $sql = "SELECT f.*, 
                       s.first_name, s.last_name, s.admission_number, s.parent_name, s.parent_phone,
                       c.class_name, c.section,
                       COALESCE(SUM(p.amount_paid), 0) as paid_amount
                FROM fees f
                JOIN students s ON f.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN fee_payments p ON f.id = p.fee_id
                WHERE f.id = :id
                GROUP BY f.id
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create a new fee invoice
     */
    public static function create($studentId, $feeType, $amount, $dueDate, $academicYear) {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO fees (student_id, fee_type, amount, due_date, academic_year, status)
                              VALUES (:student_id, :fee_type, :amount, :due_date, :academic_year, 'unpaid')");
        return $stmt->execute([
            'student_id'    => $studentId,
            'fee_type'      => $feeType,
            'amount'        => $amount,
            'due_date'      => $dueDate,
            'academic_year' => $academicYear
        ]);
    }

    /**
     * Generate bulk fee invoices for an entire class
     */
    public static function generateBulk($classId, $feeType, $amount, $dueDate, $academicYear) {
        $db = Database::connect();
        $stmtStudents = $db->prepare("SELECT id FROM students WHERE class_id = :class_id AND status = 'active'");
        $stmtStudents->execute(['class_id' => $classId]);
        $students = $stmtStudents->fetchAll();

        if (empty($students)) return 0;

        $stmtInsert = $db->prepare(
            "INSERT IGNORE INTO fees (student_id, fee_type, amount, due_date, academic_year, status)
             VALUES (:student_id, :fee_type, :amount, :due_date, :academic_year, 'unpaid')"
        );
        $count = 0;
        try {
            $db->beginTransaction();
            foreach ($students as $s) {
                $stmtInsert->execute([
                    'student_id'    => $s['id'],
                    'fee_type'      => $feeType,
                    'amount'        => $amount,
                    'due_date'      => $dueDate,
                    'academic_year' => $academicYear
                ]);
                $count++;
            }
            $db->commit();
            return $count;
        } catch (\Exception $e) {
            $db->rollBack();
            return 0;
        }
    }

    /**
     * Delete a fee invoice (only if unpaid)
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM fees WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Record a payment for a fee invoice
     */
    public static function recordPayment($feeId, $amountPaid, $method, $reference = '') {
        $db = Database::connect();
        try {
            $db->beginTransaction();

            // Generate unique receipt number
            $receiptNo = 'RCP-' . strtoupper(substr(uniqid(), -8));

            $stmtPay = $db->prepare(
                "INSERT INTO fee_payments (fee_id, amount_paid, payment_method, transaction_reference, receipt_number)
                 VALUES (:fee_id, :amount_paid, :method, :reference, :receipt)"
            );
            $stmtPay->execute([
                'fee_id'      => $feeId,
                'amount_paid' => $amountPaid,
                'method'      => $method,
                'reference'   => $reference ?: null,
                'receipt'     => $receiptNo
            ]);

            // Update fee status based on total paid
            self::updateStatus($feeId, $db);

            $db->commit();
            return $receiptNo;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Recalculate and update fee status (unpaid / partial / paid)
     */
    private static function updateStatus($feeId, $db) {
        $stmtFee = $db->prepare("SELECT amount FROM fees WHERE id = :id LIMIT 1");
        $stmtFee->execute(['id' => $feeId]);
        $fee = $stmtFee->fetch();

        $stmtTotal = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) as total FROM fee_payments WHERE fee_id = :id");
        $stmtTotal->execute(['id' => $feeId]);
        $paid = $stmtTotal->fetch()['total'];

        if ($paid <= 0) {
            $status = 'unpaid';
        } elseif ($paid >= $fee['amount']) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $db->prepare("UPDATE fees SET status = :status WHERE id = :id")->execute(['status' => $status, 'id' => $feeId]);
    }

    /**
     * Get all payments for a fee invoice
     */
    public static function getPayments($feeId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM fee_payments WHERE fee_id = :fee_id ORDER BY payment_date DESC");
        $stmt->execute(['fee_id' => $feeId]);
        return $stmt->fetchAll();
    }

    /**
     * Get payment + fee details by receipt number
     */
    public static function getByReceipt($receiptNo) {
        $db = Database::connect();
        $sql = "SELECT p.*, f.fee_type, f.amount as total_amount, f.academic_year,
                       s.first_name, s.last_name, s.admission_number,
                       c.class_name, c.section
                FROM fee_payments p
                JOIN fees f ON p.fee_id = f.id
                JOIN students s ON f.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE p.receipt_number = :receipt
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['receipt' => $receiptNo]);
        return $stmt->fetch();
    }

    /**
     * Get summary statistics for dashboard or reports
     */
    public static function getSummary($academicYear = null) {
        $db = Database::connect();
        $params = [];
        $yearFilter = '';
        if ($academicYear) {
            $yearFilter = " WHERE f.academic_year = :academic_year";
            $params['academic_year'] = $academicYear;
        }
        $sql = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(f.amount) as total_billed,
                    SUM(COALESCE(paid.total_paid, 0)) as total_collected,
                    SUM(f.amount - COALESCE(paid.total_paid, 0)) as total_outstanding,
                    SUM(CASE WHEN f.status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN f.status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN f.status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count
                FROM fees f
                LEFT JOIN (
                    SELECT fee_id, SUM(amount_paid) as total_paid FROM fee_payments GROUP BY fee_id
                ) paid ON f.id = paid.fee_id
                $yearFilter";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Get fees for a specific student
     */
    public static function getForStudent($studentId) {
        return self::getAll(['student_id' => $studentId]);
    }
}
