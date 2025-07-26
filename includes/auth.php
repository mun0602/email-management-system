<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /user/');
        exit;
    }
}

function login($username, $password) {
    $db = new Database();
    $user = $db->fetch(
        "SELECT * FROM users WHERE username = ? AND password = ?",
        [$username, md5($password)]
    );
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /login.php');
    exit;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = new Database();
    return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}
?>