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
    
    $totalMails = $db->fetch("SELECT COUNT(*) as count FROM mails")['count'];
    $usedMails = $db->fetch("SELECT COUNT(*) as count FROM mails WHERE status = 'used'")['count'];
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
        "SELECT COUNT(*) as count FROM mail_history WHERE user_id = ?",
        [$userId]
    )['count'];
}

function getMailsByApp() {
    $db = new Database();
    return $db->fetchAll(
        "SELECT app_name, COUNT(*) as count 
         FROM mail_history 
         GROUP BY app_name 
         ORDER BY count DESC"
    );
}
?>