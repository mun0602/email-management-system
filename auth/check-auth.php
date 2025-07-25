<?php
require_once __DIR__ . '/../includes/functions.php';

// Kiểm tra xác thực và chuyển hướng
if (!isLoggedIn()) {
    redirectTo('/auth/login.php');
}

// API endpoint cho việc kiểm tra trạng thái đăng nhập
if (isset($_GET['check'])) {
    jsonResponse([
        'logged_in' => isLoggedIn(),
        'is_admin' => isAdmin(),
        'user' => getCurrentUser()
    ]);
}
?>