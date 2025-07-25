<?php
require_once __DIR__ . '/../config/database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied. Admin privileges required.');
    }
}

function login($username, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? AND password_hash = ?");
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    
    if ($user = $stmt->fetch()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /login.php');
    exit();
}

// Utility functions
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function formatDate($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>