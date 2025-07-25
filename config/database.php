<?php
/**
 * Database Configuration
 * Cấu hình kết nối cơ sở dữ liệu
 */

// Check if we're in demo mode (no MySQL available)
$isDemoMode = !function_exists('mysql_connect') && !extension_loaded('pdo_mysql');

if ($isDemoMode || getenv('DEMO_MODE') === 'true') {
    require_once __DIR__ . '/demo.php';
    return;
}

class Database {
    private $host = 'localhost';
    private $dbname = 'email_management';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Fallback to demo mode if database connection fails
            require_once __DIR__ . '/demo.php';
            return;
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}

// Try to create database instance, fallback to demo if fails
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    require_once __DIR__ . '/demo.php';
}
?>