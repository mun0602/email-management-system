<?php
/**
 * Common Functions
 * Các hàm tiện ích chung
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Authentication Functions
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('/auth/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirectTo('/member/index.php');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $database;
    return $database->fetchOne(
        "SELECT * FROM users WHERE id = ?", 
        [$_SESSION['user_id']]
    );
}

/**
 * User Management Functions
 */
function createUser($username, $password, $role = 'member', $email_limit = 10) {
    global $database;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $database->query(
            "INSERT INTO users (username, password, role, email_limit) VALUES (?, ?, ?, ?)",
            [$username, $hashedPassword, $role, $email_limit]
        );
        return $database->lastInsertId();
    } catch (Exception $e) {
        return false;
    }
}

function updateUser($id, $username, $role, $email_limit, $password = null) {
    global $database;
    
    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, password = ?, role = ?, email_limit = ? WHERE id = ?";
        $params = [$username, $hashedPassword, $role, $email_limit, $id];
    } else {
        $sql = "UPDATE users SET username = ?, role = ?, email_limit = ? WHERE id = ?";
        $params = [$username, $role, $email_limit, $id];
    }
    
    try {
        $database->query($sql, $params);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function deleteUser($id) {
    global $database;
    
    try {
        $database->query("DELETE FROM users WHERE id = ?", [$id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Email Management Functions
 */
function addEmails($emailData) {
    global $database;
    
    $successCount = 0;
    $errors = [];
    
    foreach ($emailData as $item) {
        $parts = explode('|', $item);
        if (count($parts) !== 2) {
            $errors[] = "Định dạng không hợp lệ: $item";
            continue;
        }
        
        $email = trim($parts[0]);
        $password = trim($parts[1]);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ: $email";
            continue;
        }
        
        try {
            $database->query(
                "INSERT INTO emails (email, password) VALUES (?, ?)",
                [$email, $password]
            );
            $successCount++;
        } catch (Exception $e) {
            $errors[] = "Lỗi thêm email $email: " . $e->getMessage();
        }
    }
    
    return [
        'success' => $successCount,
        'errors' => $errors
    ];
}

function getAvailableEmails($limit = 1) {
    global $database;
    
    return $database->fetchAll(
        "SELECT * FROM emails WHERE is_used = 0 ORDER BY created_at ASC LIMIT ?",
        [$limit]
    );
}

function markEmailAsUsed($emailId, $userId, $appName) {
    global $database;
    
    try {
        $database->getConnection()->beginTransaction();
        
        // Mark email as used
        $database->query(
            "UPDATE emails SET is_used = 1 WHERE id = ?",
            [$emailId]
        );
        
        // Record usage
        $database->query(
            "INSERT INTO email_usage (user_id, email_id, app_name) VALUES (?, ?, ?)",
            [$userId, $emailId, $appName]
        );
        
        $database->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        $database->getConnection()->rollback();
        return false;
    }
}

/**
 * Statistics Functions
 */
function getTotalStats() {
    global $database;
    
    return [
        'total_users' => $database->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'member'")['count'],
        'total_emails' => $database->fetchOne("SELECT COUNT(*) as count FROM emails")['count'],
        'used_emails' => $database->fetchOne("SELECT COUNT(*) as count FROM emails WHERE is_used = 1")['count'],
        'available_emails' => $database->fetchOne("SELECT COUNT(*) as count FROM emails WHERE is_used = 0")['count'],
        'total_usage' => $database->fetchOne("SELECT COUNT(*) as count FROM email_usage")['count']
    ];
}

function getUserEmailStats($userId) {
    global $database;
    
    $user = $database->fetchOne("SELECT email_limit FROM users WHERE id = ?", [$userId]);
    $used = $database->fetchOne(
        "SELECT COUNT(*) as count FROM email_usage WHERE user_id = ?", 
        [$userId]
    )['count'];
    
    return [
        'limit' => $user['email_limit'],
        'used' => $used,
        'remaining' => $user['email_limit'] - $used
    ];
}

function getAppStats() {
    global $database;
    
    return $database->fetchAll(
        "SELECT app_name, COUNT(*) as count FROM email_usage GROUP BY app_name ORDER BY count DESC"
    );
}

/**
 * Utility Functions
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    if ($time < 31536000) return floor($time/2592000) . ' tháng trước';
    
    return floor($time/31536000) . ' năm trước';
}

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}
?>