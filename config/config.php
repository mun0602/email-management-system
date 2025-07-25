<?php
/**
 * General Configuration
 * Cấu hình chung của ứng dụng
 */

// Bảo mật
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'email_management_session');

// Cấu hình ứng dụng
define('APP_NAME', 'Hệ thống quản lý Email');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Asia/Ho_Chi_Minh');

// Cấu hình email
define('DEFAULT_APP_OPTIONS', [
    'TanTan' => 'TanTan',
    'Hello Talk' => 'Hello Talk',
    'Khác' => 'Khác'
]);

// Cấu hình phân trang
define('ITEMS_PER_PAGE', 20);

// Cấu hình bảo mật
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// Thiết lập timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// CSRF Token functions
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Sanitization functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeEmail($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

// Response functions
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirectTo($url) {
    header("Location: $url");
    exit;
}
?>