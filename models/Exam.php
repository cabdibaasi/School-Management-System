<?php
/**
 * Exam Model
 */
class Exam {

    /**
     * Get all exams with class details
     * @return array
     */
    public static function getAll() {
        $db = Database::connect();
        $sql = "SELECT e.*, c.class_name, c.section, c.academic_year as class_year 
                FROM exams e 
                JOIN classes c ON e.class_id = c.id 
                ORDER BY e.exam_date DESC, c.class_name ASC";
        return $db->query($sql)->fetchAll();
    }

    /**
     * Get exam by ID
     * @param int $id
     * @return array|false
     */
    public static function getById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT e.*, c.class_name, c.section FROM exams e JOIN classes c ON e.class_id = c.id WHERE e.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create exam schedule
     */
    public static function create($examName, $classId, $examDate, $academicYear) {
        $db = Database::connect();
        $sql = "INSERT INTO exams (exam_name, class_id, exam_date, academic_year) VALUES (:name, :class_id, :date, :year)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'name' => $examName,
            'class_id' => $classId,
            'date' => $examDate,
            'year' => $academicYear
        ]);
    }

    /**
     * Delete exam
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM exams WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get students and their subject marks in an exam
     */
    public static function getSubjectMarks($examId, $subjectId) {
        $db = Database::connect();
        $sql = "SELECT s.id as student_id, s.first_name, s.last_name, s.roll_number, s.admission_number,
                       m.marks_obtained, m.remarks
                FROM students s
                JOIN exams e ON s.class_id = e.class_id
                LEFT JOIN marks m ON s.id = m.student_id AND m.exam_id = :exam_id AND m.subject_id = :subject_id
                WHERE e.id = :exam_id AND s.status = 'active'
                ORDER BY s.first_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['exam_id' => $examId, 'subject_id' => $subjectId]);
        return $stmt->fetchAll();
    }

    /**
     * Save subject marks for multiple students in transaction
     * @param int $examId
     * @param int $subjectId
     * @param array $marks [student_id => ['obtained' => 85.0, 'remarks' => '...']]
     */
    public static function saveSubjectMarks($examId, $subjectId, $marks) {
        $db = Database::connect();
        try {
            $db->beginTransaction();
            $sql = "INSERT INTO marks (exam_id, student_id, subject_id, marks_obtained, remarks) 
                    VALUES (:exam_id, :student_id, :subject_id, :obtained, :remarks)
                    ON DUPLICATE KEY UPDATE marks_obtained = :obtained2, remarks = :remarks2";
            $stmt = $db->prepare($sql);
            
            foreach ($marks as $studentId => $data) {
                $stmt->execute([
                    'exam_id' => $examId,
                    'student_id' => (int)$studentId,
                    'subject_id' => $subjectId,
                    'obtained' => (float)$data['obtained'],
                    'remarks' => $data['remarks'] ?: null,
                    'obtained2' => (float)$data['obtained'],
                    'remarks2' => $data['remarks'] ?: null
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
     * Get report card marks for a student
     */
    public static function getStudentReport($studentId, $examId) {
        $db = Database::connect();
        $sql = "SELECT m.*, s.subject_name, s.subject_code, t.full_name as teacher_name 
                FROM marks m 
                JOIN subjects s ON m.subject_id = s.id 
                LEFT JOIN teachers t ON s.teacher_id = t.id
                WHERE m.student_id = :student_id AND m.exam_id = :exam_id
                ORDER BY s.subject_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['student_id' => $studentId, 'exam_id' => $examId]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate letter grade, GPA, and pass status based on score
     * @param float $percentage
     * @return array [grade => string, gpa => float, status => string]
     */
    public static function calculateGrade($percentage) {
        if ($percentage >= 90) {
            return ['grade' => 'A+', 'gpa' => 4.0, 'status' => 'Pass'];
        } elseif ($percentage >= 80) {
            return ['grade' => 'A', 'gpa' => 4.0, 'status' => 'Pass'];
        } elseif ($percentage >= 70) {
            return ['grade' => 'B', 'gpa' => 3.0, 'status' => 'Pass'];
        } elseif ($percentage >= 60) {
            return ['grade' => 'C', 'gpa' => 2.0, 'status' => 'Pass'];
        } elseif ($percentage >= 50) {
            return ['grade' => 'D', 'gpa' => 1.0, 'status' => 'Pass'];
        } else {
            return ['grade' => 'F', 'gpa' => 0.0, 'status' => 'Fail'];
        }
    }
}
