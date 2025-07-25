<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

try {
    $stats = getTotalStats();
    $appStats = getAppStats();
    $recentActivity = $database->fetchAll(
        "SELECT u.username, eu.app_name, eu.used_at 
         FROM email_usage eu 
         JOIN users u ON eu.user_id = u.id 
         ORDER BY eu.used_at DESC 
         LIMIT 10"
    );
    
    jsonResponse([
        'success' => true,
        'stats' => $stats,
        'app_stats' => $appStats,
        'recent_activity' => $recentActivity,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}
?>