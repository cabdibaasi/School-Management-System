<?php
/**
 * SchoolClass Model
 */
class SchoolClass {
    
    /**
     * Get all classes
     * @return array
     */
    public static function getAll() {
        $db = Database::connect();
        $stmt = $db->query("SELECT * FROM classes ORDER BY class_name ASC, section ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get class by ID
     * @param int $id
     * @return array|false
     */
    public static function getById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM classes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create class
     * @param string $className
     * @param string $section
     * @param string $academicYear
     * @return bool
     */
    public static function create($className, $section, $academicYear) {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO classes (class_name, section, academic_year) VALUES (:class_name, :section, :academic_year)");
        return $stmt->execute([
            'class_name' => $className,
            'section' => $section,
            'academic_year' => $academicYear
        ]);
    }

    /**
     * Update class
     * @param int $id
     * @param string $className
     * @param string $section
     * @param string $academicYear
     * @return bool
     */
    public static function update($id, $className, $section, $academicYear) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE classes SET class_name = :class_name, section = :section, academic_year = :academic_year WHERE id = :id");
        return $stmt->execute([
            'class_name' => $className,
            'section' => $section,
            'academic_year' => $academicYear,
            'id' => $id
        ]);
    }

    /**
     * Delete class
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM classes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
