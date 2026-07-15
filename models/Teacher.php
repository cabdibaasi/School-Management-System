<?php
/**
 * Teacher Model
 */
class Teacher {

    /**
     * Get teacher by ID with user account details
     * @param int $id
     * @return array|false
     */
    public static function getById($id) {
        $db = Database::connect();
        $sql = "SELECT t.*, u.username, u.email as user_email, u.status as user_status 
                FROM teachers t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Retrieve teachers list with search filters, sorting, and pagination
     */
    public static function getFilteredList($search = '', $status = '', $sort = 't.full_name', $order = 'ASC', $limit = 10, $offset = 0) {
        $db = Database::connect();
        
        $allowedSortColumns = ['t.id', 't.employee_id', 't.full_name', 't.salary', 't.date_joined'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 't.full_name';
        }
        $order = ($order === 'DESC') ? 'DESC' : 'ASC';

        $where = ["1 = 1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(t.full_name LIKE :search OR t.employee_id LIKE :search OR t.email LIKE :search OR t.qualification LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($status)) {
            $where[] = "t.status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(" AND ", $where);
        
        $sql = "SELECT t.* FROM teachers t 
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
     * Count matching teachers
     */
    public static function countFiltered($search = '', $status = '') {
        $db = Database::connect();
        $where = ["1 = 1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(t.full_name LIKE :search OR t.employee_id LIKE :search OR t.email LIKE :search OR t.qualification LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($status)) {
            $where[] = "t.status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(" AND ", $where);
        
        $sql = "SELECT COUNT(*) FROM teachers t WHERE {$whereClause}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create Teacher in transaction
     */
    public static function create($userData, $teacherData) {
        $db = Database::connect();
        try {
            $db->beginTransaction();

            // 1. Create user account credentials
            $userId = User::create($userData['username'], $userData['email'], $userData['password'], 'teacher', $userData['status']);
            if (!$userId) {
                throw new \Exception("Failed to create credential user account.");
            }

            // 2. Create teacher details profile
            $sql = "INSERT INTO teachers (
                        user_id, employee_id, full_name, gender, phone, email, address, 
                        qualification, salary, date_joined, status, photo
                    ) VALUES (
                        :user_id, :employee_id, :full_name, :gender, :phone, :email, :address, 
                        :qualification, :salary, :date_joined, :status, :photo
                    )";
            
            $stmt = $db->prepare($sql);
            $teacherData['user_id'] = $userId;
            $stmt->execute($teacherData);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update Teacher record
     */
    public static function update($id, $userData, $teacherData) {
        $db = Database::connect();
        try {
            $db->beginTransaction();

            // Get linked user ID
            $stmt = $db->prepare("SELECT user_id FROM teachers WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $userId = $stmt->fetchColumn();
            
            if (!$userId) {
                throw new \Exception("Teacher account association not found.");
            }

            // 1. Update user details
            $stmtUser = $db->prepare("UPDATE users SET username = :username, email = :email, status = :status WHERE id = :uid");
            $stmtUser->execute([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'status' => $userData['status'],
                'uid' => $userId
            ]);

            // 2. Update password if updated
            if (!empty($userData['password'])) {
                User::resetPassword($userId, $userData['password']);
            }

            // 3. Update teacher details
            $sql = "UPDATE teachers SET 
                        employee_id = :employee_id,
                        full_name = :full_name,
                        gender = :gender,
                        phone = :phone,
                        email = :email,
                        address = :address,
                        qualification = :qualification,
                        salary = :salary,
                        date_joined = :date_joined,
                        status = :status,
                        photo = :photo
                    WHERE id = :id";
            
            $stmtTeacher = $db->prepare($sql);
            $teacherData['id'] = $id;
            $stmtTeacher->execute($teacherData);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete Teacher (User cascade wipes profile)
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT user_id, photo FROM teachers WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            User::delete($row['user_id']);
            if (!empty($row['photo']) && file_exists(UPLOAD_PATH . 'profiles/' . $row['photo'])) {
                unlink(UPLOAD_PATH . 'profiles/' . $row['photo']);
            }
            return true;
        }
        return false;
    }
}
