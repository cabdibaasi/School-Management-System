<?php
/**
 * Utility helper class for security, upload and general actions
 */
class Utility {
    
    /**
     * Generate a CSRF token and store it in session
     * @return string
     */
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate the provided CSRF token
     * @param string $token
     * @return bool
     */
    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Set a flash message
     * @param string $type ('success', 'danger', 'info', 'warning')
     * @param string $message
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Display flash message if exists
     * @return string
     */
    public static function displayFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            
            $type = $flash['type'];
            $icon = 'info';
            $title = 'Notice';
            
            if ($type === 'success') {
                $icon = 'success';
                $title = 'Success!';
            } elseif ($type === 'danger' || $type === 'error') {
                $icon = 'error';
                $title = 'Error!';
            } elseif ($type === 'warning') {
                $icon = 'warning';
                $title = 'Warning!';
            }
            
            $message = addslashes($flash['message']);
            
            return "<script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: '{$icon}',
                        title: '{$title}',
                        text: '{$message}',
                        confirmButtonColor: '#1e3c72',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('{$message}');
                }
            });
            </script>";
        }
        return '';
    }

    /**
     * Render the flash message output (echoes displayFlash)
     */
    public static function renderFlash() {
        echo self::displayFlash();
    }

    /**
     * Upload and validate image files
     * @param array $file The $_FILES['name'] array
     * @param string $subFolder Subfolder within assets/uploads (e.g. 'profiles', 'system')
     * @param int $maxSize Max file size in bytes (default 2MB)
     * @param array $allowedTypes Mime types allowed
     * @return array [success => bool, filename => string|null, error => string|null]
     */
    public static function uploadImage($file, $subFolder = 'profiles', $maxSize = 2097152, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'filename' => null, 'error' => 'No file uploaded or upload error occurred.'];
        }

        // Validate File Size
        if ($file['size'] > $maxSize) {
            $sizeInMb = round($maxSize / (1024 * 1024), 2);
            return ['success' => false, 'filename' => null, 'error' => "File is too large. Maximum size allowed is {$sizeInMb}MB."];
        }

        // Validate Mime Type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedTypes)) {
            return ['success' => false, 'filename' => null, 'error' => 'Invalid file format. Only JPG, PNG, GIF, and WEBP are allowed.'];
        }

        // Rename file securely
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid('img_', true) . '.' . $ext;
        
        $destPath = UPLOAD_PATH . rtrim($subFolder, '/') . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => true, 'filename' => $newFilename, 'error' => null];
        }

        return ['success' => false, 'filename' => null, 'error' => 'Failed to save uploaded file.'];
    }

    /**
     * Format currency values based on system settings
     * @param float $amount
     * @param string $currencyCode
     * @return string
     */
    public static function formatCurrency($amount, $currencyCode = 'USD') {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'JPY' => '¥',
            'NGN' => '₦'
        ];
        $symbol = $symbols[$currencyCode] ?? '$';
        return $symbol . number_format($amount, 2);
    }
}
