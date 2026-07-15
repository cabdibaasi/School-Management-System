<?php
/**
 * TeacherPayment Model to track teacher payroll / salary payouts
 */
class TeacherPayment {

    /**
     * Record a new teacher salary payment
     * @return bool
     */
    public static function create($teacherId, $amount, $paymentDate, $monthPaid, $paymentMethod, $remarks = '') {
        $db = Database::connect();
        $sql = "INSERT INTO teacher_payments (teacher_id, amount, payment_date, month_paid, payment_method, remarks) 
                VALUES (:teacher_id, :amount, :payment_date, :month_paid, :payment_method, :remarks)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'teacher_id' => (int)$teacherId,
            'amount' => (float)$amount,
            'payment_date' => $paymentDate,
            'month_paid' => $monthPaid,
            'payment_method' => $paymentMethod,
            'remarks' => $remarks
        ]);
    }

    /**
     * Get all payroll history with teacher names
     * @return array
     */
    public static function getHistory($teacherId = null) {
        $db = Database::connect();
        $where = "1 = 1";
        $params = [];
        
        if ($teacherId !== null) {
            $where = "tp.teacher_id = :teacher_id";
            $params['teacher_id'] = (int)$teacherId;
        }
        
        $sql = "SELECT tp.*, t.full_name, t.employee_id, t.salary as base_salary 
                FROM teacher_payments tp
                JOIN teachers t ON tp.teacher_id = t.id
                WHERE {$where}
                ORDER BY tp.payment_date DESC, tp.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Delete a payment record
     * @return bool
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM teacher_payments WHERE id = :id");
        return $stmt->execute(['id' => (int)$id]);
    }

    /**
     * Calculate total teacher salaries paid
     * @return float
     */
    public static function getTotalPaid() {
        $db = Database::connect();
        return (float)$db->query("SELECT IFNULL(SUM(amount), 0) FROM teacher_payments")->fetchColumn();
    }
}
