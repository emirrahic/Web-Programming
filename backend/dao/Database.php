<?php
class Database {
    private static $host = 'localhost';
    private static $dbName = 'projekat';
    private static $username = 'root';
    private static $password = '';
    private static $connection = null;

    public static function connect() {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=utf8mb4",
                    self::$username,
                    self::$password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                
                $error = "Database connection failed: " . $e->getMessage() . "\n";
                $error .= "Connection details: mysql:host=" . self::$host . ";dbname=" . self::$dbName . "\n";
                $error .= "Username: " . self::$username . "\n";
                error_log($error);
                die($error);
            }
        }
        return self::$connection;
    }
}
