<?php
/**
 * Timetable Model
 */
class Timetable {

    /**
     * Get weekly timetable for a classroom
     * @param int $classId
     * @return array
     */
    public static function getByClassId($classId) {
        $db = Database::connect();
        $sql = "SELECT tt.*, s.subject_name, s.subject_code, t.full_name as teacher_name 
                FROM timetables tt
                JOIN subjects s ON tt.subject_id = s.id
                LEFT JOIN teachers t ON s.teacher_id = t.id
                WHERE tt.class_id = :class_id
                ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.start_time ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['class_id' => $classId]);
        return $stmt->fetchAll();
    }

    /**
     * Get weekly timetable for a teacher
     * @param int $teacherId
     * @return array
     */
    public static function getByTeacherId($teacherId) {
        $db = Database::connect();
        $sql = "SELECT tt.*, s.subject_name, s.subject_code, c.class_name, c.section 
                FROM timetables tt
                JOIN subjects s ON tt.subject_id = s.id
                JOIN classes c ON tt.class_id = c.id
                WHERE s.teacher_id = :teacher_id
                ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.start_time ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['teacher_id' => $teacherId]);
        return $stmt->fetchAll();
    }

    /**
     * Create weekly schedule slot
     */
    public static function create($classId, $subjectId, $dayOfWeek, $startTime, $endTime, $classroom) {
        $db = Database::connect();
        $sql = "INSERT INTO timetables (class_id, subject_id, day_of_week, start_time, end_time, classroom) 
                VALUES (:class_id, :subject_id, :day_of_week, :start_time, :end_time, :classroom)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'classroom' => $classroom
        ]);
    }

    /**
     * Delete schedule slot
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM timetables WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
