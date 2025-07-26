<?php
require_once __DIR__ . '/auth.php';

function getPageTitle($page = '') {
    $titles = [
        'admin_dashboard' => 'Bảng điều khiển Admin',
        'admin_members' => 'Quản lý thành viên',
        'admin_mails' => 'Quản lý Mail',
        'admin_statistics' => 'Thống kê tổng quan',
        'user_dashboard' => 'Lấy Mail',
        'user_history' => 'Lịch sử cá nhân',
        'shared_mails' => 'Mail được chia sẻ',
        'login' => 'Đăng nhập'
    ];
    
    return isset($titles[$page]) ? $titles[$page] : 'Hệ thống quản lý Mail';
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function getAppOptions() {
    return [
        'TanTan' => 'TanTan',
        'HelloTalk' => 'HelloTalk',
        'other' => 'Loại khác'
    ];
}

function generateShareToken() {
    return bin2hex(random_bytes(16));
}

function getMailStats() {
    $db = new Database();
    
    $totalMails = $db->fetch("SELECT COUNT(*) as count FROM emails")['count'];
    $usedMails = $db->fetch("SELECT COUNT(*) as count FROM emails WHERE is_used = 1")['count'];
    $availableMails = $totalMails - $usedMails;
    
    return [
        'total' => $totalMails,
        'used' => $usedMails,
        'available' => $availableMails
    ];
}

function getUserMailCount($userId) {
    $db = new Database();
    return $db->fetch(
        "SELECT COUNT(*) as count FROM email_history WHERE user_id = ?",
        [$userId]
    )['count'];
}

function getMailsByApp() {
    $db = new Database();
    return $db->fetchAll(
        "SELECT a.name as app_name, COUNT(*) as count 
         FROM email_history h 
         JOIN apps a ON h.app_id = a.id
         GROUP BY a.name 
         ORDER BY count DESC"
    );
}

function getUserLimitForApp($userId, $appId) {
    $db = new Database();
    $limit = $db->fetch(
        "SELECT daily_limit, used_today, last_reset FROM user_limits 
         WHERE user_id = ? AND app_id = ?",
        [$userId, $appId]
    );
    
    if (!$limit) {
        // Create default limit if not exists
        $db->query(
            "INSERT INTO user_limits (user_id, app_id, daily_limit, used_today) VALUES (?, ?, ?, ?)",
            [$userId, $appId, 25, 0]
        );
        return ['daily_limit' => 25, 'used_today' => 0, 'remaining' => 25];
    }
    
    // Reset daily count if new day
    if ($limit['last_reset'] !== date('Y-m-d')) {
        $db->query(
            "UPDATE user_limits SET used_today = 0, last_reset = DATE('now') WHERE user_id = ? AND app_id = ?",
            [$userId, $appId]
        );
        $limit['used_today'] = 0;
    }
    
    $limit['remaining'] = max(0, $limit['daily_limit'] - $limit['used_today']);
    return $limit;
}

function getAllApps() {
    $db = new Database();
    return $db->fetchAll("SELECT * FROM apps ORDER BY name");
}

function getAvailableEmailsForApp($appId = null) {
    $db = new Database();
    if ($appId) {
        return $db->fetch(
            "SELECT COUNT(*) as count FROM emails WHERE is_used = 0 AND (app_id = ? OR app_id IS NULL)",
            [$appId]
        )['count'];
    } else {
        return $db->fetch("SELECT COUNT(*) as count FROM emails WHERE is_used = 0")['count'];
    }
}
?>