<?php
require_once __DIR__ . '/includes/functions.php';

// Nếu đã đăng nhập, chuyển hướng đến trang tương ứng
if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('/admin/index.php');
    } else {
        redirectTo('/member/index.php');
    }
} else {
    // Nếu chưa đăng nhập, chuyển đến trang login
    redirectTo('/auth/login.php');
}
?>