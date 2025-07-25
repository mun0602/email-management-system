<?php
// Application configuration
define('APP_NAME', 'Email Management System');
define('APP_URL', 'http://localhost');
define('APP_DEBUG', true);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>