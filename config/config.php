<?php
/**
 * Application Configuration & Autoloader
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    // Configure session cookie parameters for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Only set secure cookie if running on HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Define Directory Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/');

// Determine Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$rootPath = str_replace('\\', '/', ROOT_PATH);
$relativeScript = str_replace($rootPath, '', $scriptFilename);
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = substr($scriptName, 0, strlen($scriptName) - strlen($relativeScript));
define('BASE_URL', $protocol . $host . rtrim($basePath, '/') . '/');

// Register Class Autoloader
spl_autoload_register(function ($class) {
    // Convert namespace separators to directory separators if namespaces are used later
    $classFile = str_replace('\\', '/', $class) . '.php';
    
    // Directory search paths
    $directories = [
        ROOT_PATH . 'models/',
        ROOT_PATH . 'controllers/',
        ROOT_PATH . 'helpers/',
        ROOT_PATH . 'config/'
    ];
    
    foreach ($directories as $directory) {
        $path = $directory . $classFile;
        if (file_exists($path)) {
            require_once $path;
            return;
        }
        
        // Try fallback for classes that might use slightly different naming
        // e.g. ClassObject vs class filename
        $fallbackPath = $directory . str_replace('Model', '', $class) . '.php';
        if (file_exists($fallbackPath)) {
            require_once $fallbackPath;
            return;
        }
    }
});

// Load DB
require_once ROOT_PATH . 'config/database.php';

// Include Global Helper functions
if (!function_exists('e')) {
    /**
     * Escape HTML output for XSS prevention
     */
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    /**
     * Resolve asset urls
     */
    function asset($path) {
        return BASE_URL . 'assets/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to another route
     */
    function redirect($url) {
        header("Location: " . BASE_URL . $url);
        exit;
    }
}

// Create uploads folder structure if not exists
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!is_dir(UPLOAD_PATH . 'profiles/')) {
    mkdir(UPLOAD_PATH . 'profiles/', 0755, true);
}
if (!is_dir(UPLOAD_PATH . 'system/')) {
    mkdir(UPLOAD_PATH . 'system/', 0755, true);
}
if (!is_dir(UPLOAD_PATH . 'logos/')) {
    mkdir(UPLOAD_PATH . 'logos/', 0755, true);
}
