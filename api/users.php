<?php
// API endpoint for user management
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = new Database();
    
    switch ($method) {
        case 'GET':
            if ($action === 'get_limits') {
                $userId = (int)($_GET['user_id'] ?? 0);
                
                if (!$userId) {
                    echo json_encode(['success' => false, 'message' => 'User ID is required']);
                    exit;
                }
                
                // Get user limits
                $limits = $db->fetchAll(
                    "SELECT ul.*, a.name as app_name 
                     FROM user_limits ul 
                     JOIN apps a ON ul.app_id = a.id 
                     WHERE ul.user_id = ? 
                     ORDER BY a.name",
                    [$userId]
                );
                
                // Reset daily usage if new day
                foreach ($limits as &$limit) {
                    if ($limit['last_reset'] !== date('Y-m-d')) {
                        $db->query(
                            "UPDATE user_limits SET used_today = 0, last_reset = DATE('now') WHERE user_id = ? AND app_id = ?",
                            [$userId, $limit['app_id']]
                        );
                        $limit['used_today'] = 0;
                        $limit['last_reset'] = date('Y-m-d');
                    }
                }
                
                echo json_encode(['success' => true, 'limits' => $limits]);
            }
            
            elseif ($action === 'get_stats') {
                $userId = (int)($_GET['user_id'] ?? 0);
                
                if (!$userId) {
                    echo json_encode(['success' => false, 'message' => 'User ID is required']);
                    exit;
                }
                
                // Get user statistics
                $stats = $db->fetch(
                    "SELECT 
                        u.username,
                        u.full_name,
                        COUNT(h.id) as total_emails,
                        COUNT(CASE WHEN DATE(h.taken_at) = DATE('now') THEN 1 END) as today_emails,
                        MAX(h.taken_at) as last_activity
                     FROM users u 
                     LEFT JOIN email_history h ON u.id = h.user_id 
                     WHERE u.id = ?
                     GROUP BY u.id",
                    [$userId]
                );
                
                echo json_encode(['success' => true, 'stats' => $stats]);
            }
            
            else {
                // Get all users with basic info
                $users = $db->fetchAll(
                    "SELECT u.id, u.username, u.full_name, u.created_at,
                            COUNT(h.id) as total_emails
                     FROM users u 
                     LEFT JOIN email_history h ON u.id = h.user_id 
                     WHERE u.role = 'user'
                     GROUP BY u.id 
                     ORDER BY u.created_at DESC"
                );
                
                echo json_encode(['success' => true, 'data' => $users]);
            }
            break;
            
        case 'POST':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            if ($action === 'create_user') {
                $username = trim($input['username'] ?? '');
                $password = trim($input['password'] ?? '');
                $fullName = trim($input['full_name'] ?? '');
                
                if (empty($username) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                    exit;
                }
                
                try {
                    $db->getConnection()->beginTransaction();
                    
                    // Create user
                    $db->query(
                        "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'user')",
                        [$username, md5($password), $fullName]
                    );
                    $userId = $db->lastInsertId();
                    
                    // Create default limits for all apps
                    $apps = getAllApps();
                    foreach ($apps as $app) {
                        $db->query(
                            "INSERT INTO user_limits (user_id, app_id, daily_limit, used_today) VALUES (?, ?, ?, ?)",
                            [$userId, $app['id'], 25, 0]
                        );
                    }
                    
                    $db->getConnection()->commit();
                    echo json_encode(['success' => true, 'message' => 'User created successfully', 'user_id' => $userId]);
                    
                } catch (PDOException $e) {
                    $db->getConnection()->rollback();
                    echo json_encode(['success' => false, 'message' => 'Username already exists']);
                }
            }
            
            elseif ($action === 'update_limits') {
                $userId = (int)($input['user_id'] ?? 0);
                $limits = $input['limits'] ?? [];
                
                if (!$userId || empty($limits)) {
                    echo json_encode(['success' => false, 'message' => 'User ID and limits are required']);
                    exit;
                }
                
                try {
                    $db->getConnection()->beginTransaction();
                    
                    foreach ($limits as $appId => $limit) {
                        $dailyLimit = (int)$limit;
                        if ($dailyLimit >= 0) {
                            $db->query(
                                "UPDATE user_limits SET daily_limit = ? WHERE user_id = ? AND app_id = ?",
                                [$dailyLimit, $userId, $appId]
                            );
                        }
                    }
                    
                    $db->getConnection()->commit();
                    echo json_encode(['success' => true, 'message' => 'Limits updated successfully']);
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    echo json_encode(['success' => false, 'message' => 'Error updating limits: ' . $e->getMessage()]);
                }
            }
            
            else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'DELETE':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($input['user_id'] ?? 0);
            
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }
            
            try {
                $db->getConnection()->beginTransaction();
                
                // Delete user limits
                $db->query("DELETE FROM user_limits WHERE user_id = ?", [$userId]);
                
                // Delete user (keep history for integrity)
                $db->query("DELETE FROM users WHERE id = ? AND role = 'user'", [$userId]);
                
                $db->getConnection()->commit();
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}