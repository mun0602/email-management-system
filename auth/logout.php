<?php
require_once __DIR__ . '/../includes/functions.php';

// Xóa session và chuyển hướng
session_destroy();
redirectTo('login.php');
?>