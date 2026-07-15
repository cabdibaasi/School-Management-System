<?php
/**
 * Setting Model for global configuration key-value storage
 */
class Setting {
    private static $cache = [];

    /**
     * Get specific setting value by key
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function get($key, $default = '') {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
            $stmt->execute(['key' => $key]);
            $result = $stmt->fetch();
            $val = $result ? $result['setting_value'] : $default;
            self::$cache[$key] = $val;
            return $val;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Update a setting value
     * @param string $key
     * @param string $value
     * @return bool
     */
    public static function set($key, $value) {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value2");
        $res = $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
        if ($res) {
            self::$cache[$key] = $value;
        }
        return $res;
    }

    /**
     * Clear the in-memory settings cache
     */
    public static function clearCache() {
        self::$cache = [];
    }
}
