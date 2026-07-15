<?php
/**
 * Database Connection class using PDO
 */
class Database {
    private static $host = '127.0.0.1';
    private static $db = 'school_management';
    private static $user = 'root';
    private static $pass = '';
    private static $charset = 'utf8mb4';
    private static $pdo = null;

    /**
     * Get PDO Database connection instance
     * @return PDO
     */
    public static function connect() {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$pdo = new PDO($dsn, self::$user, self::$pass, $options);
            } catch (\PDOException $e) {
                // If database doesn't exist, try connecting without dbname to create it
                try {
                    $dsnWithoutDb = "mysql:host=" . self::$host . ";charset=" . self::$charset;
                    $tempPdo = new PDO($dsnWithoutDb, self::$user, self::$pass, $options);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . self::$db . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Now reconnect with the database selected
                    self::$pdo = new PDO($dsn, self::$user, self::$pass, $options);
                    
                    // Initialize the schema
                    self::initializeSchema(self::$pdo);
                } catch (\PDOException $innerEx) {
                    throw new \PDOException($innerEx->getMessage(), (int)$innerEx->getCode());
                }
            }
        }
        return self::$pdo;
    }

    /**
     * Run the schema.sql script to initialize the database
     * @param PDO $pdo
     */
    private static function initializeSchema($pdo) {
        $schemaFile = dirname(__DIR__) . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
            
            // Seed initial administrator user dynamically
            $adminUsername = 'admin';
            $adminEmail = 'admin@school.com';
            $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (:username, :email, :password, 'admin', 'active')");
            $stmt->execute([
                'username' => $adminUsername,
                'email' => $adminEmail,
                'password' => $adminPassword
            ]);
        }
    }
}
