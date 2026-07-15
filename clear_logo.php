<?php
require_once __DIR__ . '/config/config.php';
try {
    $db = Database::connect();
    $db->exec("UPDATE settings SET setting_value = '' WHERE setting_key = 'school_logo'");
    Setting::clearCache();
    echo "SUCCESS: Logo cleared from database.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
