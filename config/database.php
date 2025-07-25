<?php
// Database configuration for Mail Management System
class Database {
    private $host = 'localhost';
    private $database = 'mail_management';
    private $username = 'root';
    private $password = '';
    
    // For demo purposes, use SQLite if MySQL is not available
    private $use_sqlite = false;
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        // Try SQLite for demo purposes if MySQL fails
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Fallback to SQLite for demo
            $sqlite_path = __DIR__ . '/../mail_management.db';
            $dsn = "sqlite:$sqlite_path";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $this->pdo = new PDO($dsn, '', '', $options);
            $this->use_sqlite = true;
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
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
?>