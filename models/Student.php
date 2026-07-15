<?php
/**
 * Student Model
 */
class Student {

    /**
     * Get student by ID with user account and class details
     * @param int $id
     * @return array|false
     */
    public static function getById($id) {
        $db = Database::connect();
        $sql = "SELECT s.*, u.username, u.email as user_email, u.status as user_status, 
                       c.class_name, c.section, c.academic_year as class_academic_year 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE s.id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Retrieve students with filters, pagination, and sorting
     */
    public static function getFilteredList($search = '', $classId = '', $status = '', $sort = 's.first_name', $order = 'ASC', $limit = 10, $offset = 0) {
        $db = Database::connect();
        
        $allowedSortColumns = ['s.id', 's.admission_number', 's.first_name', 's.roll_number', 'c.class_name'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 's.first_name';
        }
        $order = ($order === 'DESC') ? 'DESC' : 'ASC';

        $where = ["1 = 1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(s.first_name LIKE :search OR s.last_name LIKE :search OR s.admission_number LIKE :search OR s.student_id_card LIKE :search OR s.roll_number LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($classId)) {
            $where[] = "s.class_id = :class_id";
            $params['class_id'] = (int)$classId;
        }

        if (!empty($status)) {
            $where[] = "s.status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(" AND ", $where);
        
        $sql = "SELECT s.*, c.class_name, c.section 
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE {$whereClause} 
                ORDER BY {$sort} {$order} 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count matching student records
     */
    public static function countFiltered($search = '', $classId = '', $status = '') {
        $db = Database::connect();
        $where = ["1 = 1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(s.first_name LIKE :search OR s.last_name LIKE :search OR s.admission_number LIKE :search OR s.student_id_card LIKE :search OR s.roll_number LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($classId)) {
            $where[] = "s.class_id = :class_id";
            $params['class_id'] = (int)$classId;
        }

        if (!empty($status)) {
            $where[] = "s.status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(" AND ", $where);
        
        $sql = "SELECT COUNT(*) FROM students s WHERE {$whereClause}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create Student in transaction
     */
    public static function create($userData, $studentData) {
        $db = Database::connect();
        try {
            $db->beginTransaction();

            // 1. Create User Credential
            $userId = User::create($userData['username'], $userData['email'], $userData['password'], 'student', $userData['status']);
            if (!$userId) {
                throw new \Exception("Failed to create credential user account.");
            }

            // 2. Create Student Record
            $sql = "INSERT INTO students (
                        user_id, admission_number, student_id_card, first_name, last_name, 
                        gender, date_of_birth, nationality, blood_group, phone, email, address, 
                        parent_name, parent_phone, parent_email, class_id, roll_number, 
                        academic_year, status, photo
                    ) VALUES (
                        :user_id, :admission_number, :student_id_card, :first_name, :last_name, 
                        :gender, :date_of_birth, :nationality, :blood_group, :phone, :email, :address, 
                        :parent_name, :parent_phone, :parent_email, :class_id, :roll_number, 
                        :academic_year, :status, :photo
                    )";
            
            $stmt = $db->prepare($sql);
            $studentData['user_id'] = $userId;
            $stmt->execute($studentData);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update Student in transaction
     */
    public static function update($id, $userData, $studentData) {
        $db = Database::connect();
        try {
            $db->beginTransaction();

            // Fetch student user_id
            $stmt = $db->prepare("SELECT user_id FROM students WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $userId = $stmt->fetchColumn();
            
            if (!$userId) {
                throw new \Exception("Linked student account not found.");
            }

            // 1. Update User Details
            $stmtUser = $db->prepare("UPDATE users SET username = :username, email = :email, status = :status WHERE id = :uid");
            $stmtUser->execute([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'status' => $userData['status'],
                'uid' => $userId
            ]);

            // 2. Update password if provided
            if (!empty($userData['password'])) {
                User::resetPassword($userId, $userData['password']);
            }

            // 3. Update Student Record
            $sql = "UPDATE students SET 
                        admission_number = :admission_number,
                        student_id_card = :student_id_card,
                        first_name = :first_name,
                        last_name = :last_name,
                        gender = :gender,
                        date_of_birth = :date_of_birth,
                        nationality = :nationality,
                        blood_group = :blood_group,
                        phone = :phone,
                        email = :email,
                        address = :address,
                        parent_name = :parent_name,
                        parent_phone = :parent_phone,
                        parent_email = :parent_email,
                        class_id = :class_id,
                        roll_number = :roll_number,
                        academic_year = :academic_year,
                        status = :status,
                        photo = :photo
                    WHERE id = :id";
            
            $stmtStudent = $db->prepare($sql);
            $studentData['id'] = $id;
            $stmtStudent->execute($studentData);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete Student (User cascade handles clean deletion)
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT user_id, photo FROM students WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            // Delete user credentials (cascade deletes student)
            User::delete($row['user_id']);
            if (!empty($row['photo']) && file_exists(UPLOAD_PATH . 'profiles/' . $row['photo'])) {
                unlink(UPLOAD_PATH . 'profiles/' . $row['photo']);
            }
            return true;
        }
        return false;
    }
}
