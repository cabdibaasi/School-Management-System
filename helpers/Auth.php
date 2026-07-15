<?php
/**
 * Authentication & Authorization Helper Class
 */
class Auth {

    /**
     * Attempt login
     * @param string $usernameOrEmail
     * @param string $password
     * @return bool
     */
    public static function attempt($usernameOrEmail, $password) {
        $db = Database::connect();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = :username OR email = :email) LIMIT 1");
        $stmt->execute(['username' => $usernameOrEmail, 'email' => $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active') {
            if (password_verify($password, $user['password'])) {
                // Set session parameters
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // Track details from students/teachers if applicable
                if ($user['role'] === 'student') {
                    $sStmt = $db->prepare("SELECT id, class_id, first_name, last_name, photo FROM students WHERE user_id = :uid LIMIT 1");
                    $sStmt->execute(['uid' => $user['id']]);
                    $student = $sStmt->fetch();
                    if ($student) {
                        $_SESSION['profile_id'] = $student['id'];
                        $_SESSION['class_id'] = $student['class_id'];
                        $_SESSION['display_name'] = $student['first_name'] . ' ' . $student['last_name'];
                        $_SESSION['photo'] = $student['photo'];
                    }
                } elseif ($user['role'] === 'teacher') {
                    $tStmt = $db->prepare("SELECT id, full_name, photo FROM teachers WHERE user_id = :uid LIMIT 1");
                    $tStmt->execute(['uid' => $user['id']]);
                    $teacher = $tStmt->fetch();
                    if ($teacher) {
                        $_SESSION['profile_id'] = $teacher['id'];
                        $_SESSION['display_name'] = $teacher['full_name'];
                        $_SESSION['photo'] = $teacher['photo'];
                    }
                } else {
                    $_SESSION['display_name'] = 'Administrator';
                    $_SESSION['photo'] = '';
                }
                
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user profile ID (student ID or teacher ID)
     * @return int|null
     */
    public static function profileId() {
        return $_SESSION['profile_id'] ?? null;
    }

    /**
     * Get current user display name
     * @return string
     */
    public static function displayName() {
        return $_SESSION['display_name'] ?? 'Guest';
    }

    /**
     * Get current user photo
     * @return string
     */
    public static function photo() {
        return !empty($_SESSION['photo']) ? $_SESSION['photo'] : '';
    }

    /**
     * Get current user role
     * @return string|null
     */
    public static function role() {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Restrict page to specific roles
     * @param array|string $roles Allowed roles
     */
    public static function requireRole($roles) {
        if (!self::check()) {
            Utility::setFlash('danger', 'You must login to view that page.');
            redirect('views/login.php');
        }

        $roles = is_array($roles) ? $roles : [$roles];
        if (!in_array(self::role(), $roles)) {
            Utility::setFlash('danger', 'Unauthorized access.');
            redirect('views/dashboard.php');
        }
    }

    /**
     * End user session
     */
    public static function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
