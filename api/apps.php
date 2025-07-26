<?php
// API endpoint for app management
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = new Database();
    
    switch ($method) {
        case 'GET':
            // Get all apps
            $apps = getAllApps();
            echo json_encode(['success' => true, 'data' => $apps]);
            break;
            
        case 'POST':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }
            
            $appName = trim($input['name'] ?? '');
            if (empty($appName)) {
                echo json_encode(['success' => false, 'message' => 'App name is required']);
                exit;
            }
            
            // Check if app already exists
            $existing = $db->fetch("SELECT id FROM apps WHERE name = ?", [$appName]);
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'App already exists']);
                exit;
            }
            
            // Create new app
            $db->query("INSERT INTO apps (name) VALUES (?)", [$appName]);
            $appId = $db->lastInsertId();
            
            // Create default limits for all users
            $users = $db->fetchAll("SELECT id FROM users WHERE role = 'user'");
            foreach ($users as $user) {
                $db->query(
                    "INSERT INTO user_limits (user_id, app_id, daily_limit, used_today) VALUES (?, ?, ?, ?)",
                    [$user['id'], $appId, 25, 0]
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'App created successfully', 'id' => $appId]);
            break;
            
        case 'DELETE':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }
            
            $appId = $input['id'] ?? 0;
            if (!$appId) {
                echo json_encode(['success' => false, 'message' => 'App ID is required']);
                exit;
            }
            
            // Check if app has associated emails
            $emailCount = $db->fetch("SELECT COUNT(*) as count FROM emails WHERE app_id = ?", [$appId])['count'];
            if ($emailCount > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete app with associated emails']);
                exit;
            }
            
            // Delete app and related limits
            $db->query("DELETE FROM user_limits WHERE app_id = ?", [$appId]);
            $db->query("DELETE FROM apps WHERE id = ?", [$appId]);
            
            echo json_encode(['success' => true, 'message' => 'App deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}