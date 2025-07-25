<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Redirect based on role
if (isAdmin()) {
    header('Location: /admin/');
} else {
    header('Location: /user/');
}
exit;
?>