<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user = getCurrentUser();
$stats = getUserEmailStats($user['id']);

jsonResponse([
    'success' => true,
    'user_id' => $user['id'],
    'username' => $user['username'],
    'limit' => $stats['limit'],
    'used' => $stats['used'],
    'remaining' => $stats['remaining'],
    'percentage' => $stats['limit'] > 0 ? round(($stats['used'] / $stats['limit']) * 100, 1) : 0
]);
?>