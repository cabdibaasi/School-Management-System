<?php
/**
 * Attendance Model
 */
class Attendance {

    /**
     * Get students and their attendance status for a class and date
     * @param int $classId
     * @param string $date (Y-m-d)
     * @return array
     */
    public static function getStudentAttendance($classId, $date) {
        $db = Database::connect();
        $sql = "SELECT s.id as student_id, s.first_name, s.last_name, s.admission_number, s.roll_number,
                       sa.status as attendance_status, sa.remarks as attendance_remarks
                FROM students s
                LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.date = :date
                WHERE s.class_id = :class_id AND s.status = 'active'
                ORDER BY s.first_name ASC, s.last_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['class_id' => $classId, 'date' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * Save student attendance records
     * @param string $date
     * @param array $records [student_id => ['status' => 'present', 'remarks' => '...']]
     * @return bool
     */
    public static function saveStudentAttendance($date, $records) {
        $db = Database::connect();
        try {
            $db->beginTransaction();
            $sql = "INSERT INTO student_attendance (student_id, date, status, remarks) 
                    VALUES (:student_id, :date, :status, :remarks)
                    ON DUPLICATE KEY UPDATE status = :status2, remarks = :remarks2";
            $stmt = $db->prepare($sql);
            
            foreach ($records as $studentId => $data) {
                $stmt->execute([
                    'student_id' => (int)$studentId,
                    'date' => $date,
                    'status' => $data['status'],
                    'remarks' => $data['remarks'] ?? null,
                    'status2' => $data['status'],
                    'remarks2' => $data['remarks'] ?? null
                ]);
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Get teachers and their attendance status for a date
     * @param string $date (Y-m-d)
     * @return array
     */
    public static function getTeacherAttendance($date) {
        $db = Database::connect();
        $sql = "SELECT t.id as teacher_id, t.full_name, t.employee_id,
                       ta.status as attendance_status, ta.remarks as attendance_remarks
                FROM teachers t
                LEFT JOIN teacher_attendance ta ON t.id = ta.teacher_id AND ta.date = :date
                WHERE t.status = 'active'
                ORDER BY t.full_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['date' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * Save teacher attendance records
     * @param string $date
     * @param array $records [teacher_id => ['status' => 'present', 'remarks' => '...']]
     * @return bool
     */
    public static function saveTeacherAttendance($date, $records) {
        $db = Database::connect();
        try {
            $db->beginTransaction();
            $sql = "INSERT INTO teacher_attendance (teacher_id, date, status, remarks) 
                    VALUES (:teacher_id, :date, :status, :remarks)
                    ON DUPLICATE KEY UPDATE status = :status2, remarks = :remarks2";
            $stmt = $db->prepare($sql);
            
            foreach ($records as $teacherId => $data) {
                $stmt->execute([
                    'teacher_id' => (int)$teacherId,
                    'date' => $date,
                    'status' => $data['status'],
                    'remarks' => $data['remarks'] ?? null,
                    'status2' => $data['status'],
                    'remarks2' => $data['remarks'] ?? null
                ]);
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Get student monthly attendance summary metrics
     */
    public static function getStudentMonthlySummary($classId, $year, $month) {
        $db = Database::connect();
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "SELECT s.id as student_id, s.first_name, s.last_name, s.admission_number,
                       SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as presents,
                       SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absents,
                       SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as lates,
                       SUM(CASE WHEN sa.status = 'excused' THEN 1 ELSE 0 END) as excused,
                       COUNT(sa.status) as total_marked
                FROM students s
                LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.date BETWEEN :start AND :end
                WHERE s.class_id = :class_id AND s.status = 'active'
                GROUP BY s.id
                ORDER BY s.first_name ASC";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'class_id' => $classId,
            'start' => $startDate,
            'end' => $endDate
        ]);
        return $stmt->fetchAll();
    }
}
