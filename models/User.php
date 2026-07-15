<?php
/**
 * User Model
 */
class User {
    
    /**
     * Get all users
     * @return array
     */
    public static function getAll() {
        $db = Database::connect();
        $stmt = $db->query("SELECT * FROM users ORDER BY role ASC, username ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|false
     */
    public static function getById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create user account
     * @param string $username
     * @param string $email
     * @param string $password Unhashed password
     * @param string $role ('admin', 'teacher', 'student')
     * @param string $status ('active', 'inactive')
     * @return int Inserted ID or 0 on failure
     */
    public static function create($username, $email, $password, $role, $status = 'active') {
        $db = Database::connect();
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, status) VALUES (:username, :email, :password, :role, :status)");
        if ($stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashed,
            'role' => $role,
            'status' => $status
        ])) {
            return (int)$db->lastInsertId();
        }
        return 0;
    }

    /**
     * Update user details
     * @param int $id
     * @param string $username
     * @param string $email
     * @param string $role
     * @param string $status
     * @return bool
     */
    public static function update($id, $username, $email, $role, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE users SET username = :username, email = :email, role = :role, status = :status WHERE id = :id");
        return $stmt->execute([
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'id' => $id
        ]);
    }

    /**
     * Reset user password
     * @param int $id
     * @param string $newPassword Unhashed
     * @return bool
     */
    public static function resetPassword($id, $newPassword) {
        $db = Database::connect();
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute(['password' => $hashed, 'id' => $id]);
    }

    /**
     * Deactivate or activate user account
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function updateStatus($id, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Delete user
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
