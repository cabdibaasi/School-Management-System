<?php
/**
 * Subject Model
 */
class Subject {
    
    /**
     * Get all subjects with teacher and class details
     * @return array
     */
    public static function getAll() {
        $db = Database::connect();
        $sql = "SELECT s.*, t.full_name as teacher_name, c.class_name, c.section, c.academic_year 
                FROM subjects s 
                LEFT JOIN teachers t ON s.teacher_id = t.id 
                JOIN classes c ON s.class_id = c.id 
                ORDER BY c.class_name ASC, s.subject_name ASC";
        return $db->query($sql)->fetchAll();
    }

    /**
     * Get subject by ID
     * @param int $id
     * @return array|false
     */
    public static function getById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM subjects WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create Subject
     * @param string $name
     * @param string $code
     * @param int|null $teacherId
     * @param int $classId
     * @return bool
     */
    public static function create($name, $code, $teacherId, $classId) {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO subjects (subject_name, subject_code, teacher_id, class_id) VALUES (:name, :code, :teacher_id, :class_id)");
        return $stmt->execute([
            'name' => $name,
            'code' => $code,
            'teacher_id' => $teacherId ?: null,
            'class_id' => $classId
        ]);
    }

    /**
     * Update Subject
     * @param int $id
     * @param string $name
     * @param string $code
     * @param int|null $teacherId
     * @param int $classId
     * @return bool
     */
    public static function update($id, $name, $code, $teacherId, $classId) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE subjects SET subject_name = :name, subject_code = :code, teacher_id = :teacher_id, class_id = :class_id WHERE id = :id");
        return $stmt->execute([
            'name' => $name,
            'code' => $code,
            'teacher_id' => $teacherId ?: null,
            'class_id' => $classId,
            'id' => $id
        ]);
    }

    /**
     * Delete Subject
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM subjects WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
