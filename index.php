<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: http://localhost:8000/login.php');
    exit;
}

// Redirect based on role
if (isAdmin()) {
    header('Location: http://localhost:8000/admin/');
} else {
    header('Location: http://localhost:8000/user/');
}
exit;
?>