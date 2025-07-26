<?php
// API endpoint for email management
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
    $userId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'POST':
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'take_emails':
                    $count = (int)($input['count'] ?? 1);
                    $appId = (int)($input['app_id'] ?? 0);
                    
                    if (!$appId) {
                        echo json_encode(['success' => false, 'message' => 'App is required']);
                        exit;
                    }
                    
                    if ($count < 1 || $count > 10) {
                        echo json_encode(['success' => false, 'message' => 'Count must be between 1 and 10']);
                        exit;
                    }
                    
                    // Check user limits
                    $limit = getUserLimitForApp($userId, $appId);
                    if ($count > $limit['remaining']) {
                        echo json_encode([
                            'success' => false, 
                            'message' => "You can only take {$limit['remaining']} more emails for this app today"
                        ]);
                        exit;
                    }
                    
                    // Get available emails
                    $availableEmails = $db->fetchAll(
                        "SELECT * FROM emails WHERE is_used = 0 AND (app_id IS NULL OR app_id = ?) ORDER BY created_at ASC LIMIT ?",
                        [$appId, $count]
                    );
                    
                    if (count($availableEmails) < $count) {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Not enough emails available. Only ' . count($availableEmails) . ' emails left.'
                        ]);
                        exit;
                    }
                    
                    // Process allocation
                    $allocatedEmails = [];
                    $db->getConnection()->beginTransaction();
                    
                    try {
                        foreach ($availableEmails as $email) {
                            // Mark email as used
                            $db->query(
                                "UPDATE emails SET is_used = 1, used_by = ?, used_at = CURRENT_TIMESTAMP, app_id = ? WHERE id = ?",
                                [$userId, $appId, $email['id']]
                            );
                            
                            // Add to history
                            $db->query(
                                "INSERT INTO email_history (user_id, email_id, app_id) VALUES (?, ?, ?)",
                                [$userId, $email['id'], $appId]
                            );
                            
                            $allocatedEmails[] = $email;
                        }
                        
                        // Update user limit
                        $db->query(
                            "UPDATE user_limits SET used_today = used_today + ? WHERE user_id = ? AND app_id = ?",
                            [$count, $userId, $appId]
                        );
                        
                        $db->getConnection()->commit();
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Emails allocated successfully',
                            'emails' => $allocatedEmails,
                            'count' => count($allocatedEmails)
                        ]);
                        
                    } catch (Exception $e) {
                        $db->getConnection()->rollback();
                        throw $e;
                    }
                    break;
                    
                case 'bulk_add':
                    if (!isAdmin()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Admin access required']);
                        exit;
                    }
                    
                    $emailsText = trim($input['emails'] ?? '');
                    if (empty($emailsText)) {
                        echo json_encode(['success' => false, 'message' => 'Email list is required']);
                        exit;
                    }
                    
                    $lines = explode("\n", $emailsText);
                    $added = 0;
                    $errors = [];
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        $parts = explode('|', $line);
                        if (count($parts) !== 2) {
                            $errors[] = "Invalid format: $line";
                            continue;
                        }
                        
                        $email = trim($parts[0]);
                        $password = trim($parts[1]);
                        
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Invalid email: $email";
                            continue;
                        }
                        
                        // Check if email already exists
                        $existing = $db->fetch("SELECT id FROM emails WHERE email = ?", [$email]);
                        if ($existing) {
                            $errors[] = "Email already exists: $email";
                            continue;
                        }
                        
                        try {
                            $db->query("INSERT INTO emails (email, password) VALUES (?, ?)", [$email, $password]);
                            $added++;
                        } catch (Exception $e) {
                            $errors[] = "Failed to add $email: " . $e->getMessage();
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "Added $added emails",
                        'added' => $added,
                        'errors' => $errors
                    ]);
                    break;
                    
                case 'add_single':
                    if (!isAdmin()) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Admin access required']);
                        exit;
                    }
                    
                    $email = trim($input['email'] ?? '');
                    $password = trim($input['password'] ?? '');
                    
                    if (empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                        exit;
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        exit;
                    }
                    
                    // Check if email already exists
                    $existing = $db->fetch("SELECT id FROM emails WHERE email = ?", [$email]);
                    if ($existing) {
                        echo json_encode(['success' => false, 'message' => 'Email already exists']);
                        exit;
                    }
                    
                    $db->query("INSERT INTO emails (email, password) VALUES (?, ?)", [$email, $password]);
                    echo json_encode(['success' => true, 'message' => 'Email added successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'GET':
            // Get user's email statistics per app
            $apps = getAllApps();
            $userStats = [];
            
            foreach ($apps as $app) {
                $limit = getUserLimitForApp($userId, $app['id']);
                $available = getAvailableEmailsForApp($app['id']);
                
                $userStats[] = [
                    'app' => $app,
                    'limit' => $limit,
                    'available' => $available
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $userStats]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}