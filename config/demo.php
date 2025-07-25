<?php
/**
 * Demo Mode - Database Mock for Testing
 */

class MockDatabase {
    private $mockData = [
        'users' => [
            ['id' => 1, 'username' => 'admin', 'password' => '$2y$10$ogNlI4GIvC4Kr2wyUNSjKObH1vduvNRokc.xZvgCqAcizMav/8hVG', 'role' => 'admin', 'email_limit' => 999999],
            ['id' => 2, 'username' => 'member1', 'password' => '$2y$10$ogNlI4GIvC4Kr2wyUNSjKObH1vduvNRokc.xZvgCqAcizMav/8hVG', 'role' => 'member', 'email_limit' => 10],
        ],
        'emails' => [
            ['id' => 1, 'email' => 'test1@example.com', 'password' => 'password123', 'is_used' => 0],
            ['id' => 2, 'email' => 'test2@example.com', 'password' => 'password456', 'is_used' => 0],
            ['id' => 3, 'email' => 'test3@example.com', 'password' => 'password789', 'is_used' => 1],
        ],
        'email_usage' => [
            ['id' => 1, 'user_id' => 2, 'email_id' => 3, 'app_name' => 'TanTan', 'used_at' => '2024-01-01 10:00:00'],
        ]
    ];

    public function fetchAll($sql, $params = []) {
        // Simple mock queries
        if (strpos($sql, 'SELECT COUNT(*) as count FROM users') !== false && strpos($sql, "role = 'member'") !== false) {
            return [['count' => 1]]; // Only member count
        }
        if (strpos($sql, 'SELECT COUNT(*) as count FROM users') !== false) {
            return [['count' => 2]];
        }
        if (strpos($sql, 'SELECT COUNT(*) as count FROM emails WHERE is_used = 0') !== false) {
            return [['count' => 2]];
        }
        if (strpos($sql, 'SELECT COUNT(*) as count FROM emails WHERE is_used = 1') !== false) {
            return [['count' => 1]];
        }
        if (strpos($sql, 'SELECT COUNT(*) as count FROM emails') !== false) {
            return [['count' => 3]];
        }
        if (strpos($sql, 'SELECT COUNT(*) as count FROM email_usage') !== false) {
            return [['count' => 1]];
        }
        if (strpos($sql, 'app_name, COUNT(*) as count FROM email_usage GROUP BY app_name') !== false) {
            return [['app_name' => 'TanTan', 'count' => 1]];
        }
        
        return [];
    }

    public function fetchOne($sql, $params = []) {
        if (strpos($sql, 'SELECT * FROM users WHERE username = ?') !== false && !empty($params)) {
            foreach ($this->mockData['users'] as $user) {
                if ($user['username'] === $params[0]) {
                    return $user;
                }
            }
        }
        if (strpos($sql, 'SELECT * FROM users WHERE id = ?') !== false && !empty($params)) {
            foreach ($this->mockData['users'] as $user) {
                if ($user['id'] == $params[0]) {
                    return $user;
                }
            }
        }
        
        $result = $this->fetchAll($sql, $params);
        return !empty($result) ? $result[0] : null;
    }

    public function query($sql, $params = []) {
        return true; // Mock success
    }

    public function lastInsertId() {
        return 1;
    }

    public function getConnection() {
        return $this;
    }

    public function beginTransaction() { return true; }
    public function commit() { return true; }
    public function rollback() { return true; }
}

// Tạo instance demo
$database = new MockDatabase();
$pdo = $database->getConnection();
?>