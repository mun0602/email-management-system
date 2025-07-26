<?php
// API endpoint for sharing emails
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
            
            if ($action === 'create_share') {
                $emailIds = $input['email_ids'] ?? [];
                $password = trim($input['password'] ?? '');
                $expiresHours = (int)($input['expires_hours'] ?? 0);
                
                if (empty($emailIds)) {
                    echo json_encode(['success' => false, 'message' => 'No emails selected']);
                    exit;
                }
                
                // Validate that user has access to these emails
                $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';
                $userEmails = $db->fetchAll(
                    "SELECT id, email FROM emails WHERE id IN ($placeholders) AND used_by = ?",
                    array_merge($emailIds, [$userId])
                );
                
                if (count($userEmails) !== count($emailIds)) {
                    echo json_encode(['success' => false, 'message' => 'You can only share emails that you have taken']);
                    exit;
                }
                
                // Generate unique share code
                do {
                    $shareCode = generateShareToken();
                    $existing = $db->fetch("SELECT id FROM shared_emails WHERE share_code = ?", [$shareCode]);
                } while ($existing);
                
                // Calculate expiration
                $expiresAt = null;
                if ($expiresHours > 0) {
                    $expiresAt = date('Y-m-d H:i:s', time() + ($expiresHours * 3600));
                }
                
                // Hash password if provided
                $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
                
                // Create share record
                $db->query(
                    "INSERT INTO shared_emails (share_code, email_ids, password, created_by, expires_at) VALUES (?, ?, ?, ?, ?)",
                    [$shareCode, json_encode($emailIds), $hashedPassword, $userId, $expiresAt]
                );
                
                $shareUrl = "http://localhost:8000/shared/view.php?code=" . $shareCode;
                
                echo json_encode([
                    'success' => true,
                    'share_code' => $shareCode,
                    'share_url' => $shareUrl,
                    'email_count' => count($emailIds),
                    'expires_at' => $expiresAt,
                    'has_password' => !empty($password)
                ]);
            }
            
            elseif ($action === 'access_shared') {
                $shareCode = trim($input['share_code'] ?? '');
                $password = trim($input['password'] ?? '');
                
                if (empty($shareCode)) {
                    echo json_encode(['success' => false, 'message' => 'Share code is required']);
                    exit;
                }
                
                // Get share record
                $share = $db->fetch(
                    "SELECT * FROM shared_emails WHERE share_code = ?",
                    [$shareCode]
                );
                
                if (!$share) {
                    echo json_encode(['success' => false, 'message' => 'Invalid share code']);
                    exit;
                }
                
                // Check expiration
                if ($share['expires_at'] && strtotime($share['expires_at']) < time()) {
                    echo json_encode(['success' => false, 'message' => 'Share link has expired']);
                    exit;
                }
                
                // Check password
                if ($share['password'] && !password_verify($password, $share['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid password']);
                    exit;
                }
                
                // Get shared emails
                $emailIds = json_decode($share['email_ids'], true);
                $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';
                
                $emails = $db->fetchAll(
                    "SELECT e.email, e.password, a.name as app_name, e.created_at 
                     FROM emails e 
                     LEFT JOIN apps a ON e.app_id = a.id 
                     WHERE e.id IN ($placeholders)",
                    $emailIds
                );
                
                echo json_encode([
                    'success' => true,
                    'emails' => $emails,
                    'shared_by' => $share['created_by'],
                    'created_at' => $share['created_at']
                ]);
            }
            
            else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'GET':
            // Get user's shared links
            $shares = $db->fetchAll(
                "SELECT share_code, email_ids, created_at, expires_at, 
                        CASE WHEN password IS NOT NULL THEN 1 ELSE 0 END as has_password
                 FROM shared_emails 
                 WHERE created_by = ? 
                 ORDER BY created_at DESC",
                [$userId]
            );
            
            // Add email count to each share
            foreach ($shares as &$share) {
                $emailIds = json_decode($share['email_ids'], true);
                $share['email_count'] = count($emailIds);
                $share['share_url'] = "http://localhost:8000/shared/view.php?code=" . $share['share_code'];
                $share['is_expired'] = $share['expires_at'] && strtotime($share['expires_at']) < time();
            }
            
            echo json_encode(['success' => true, 'data' => $shares]);
            break;
            
        case 'DELETE':
            $shareCode = $input['share_code'] ?? '';
            
            if (empty($shareCode)) {
                echo json_encode(['success' => false, 'message' => 'Share code is required']);
                exit;
            }
            
            // Delete share (only if owned by current user)
            $result = $db->query(
                "DELETE FROM shared_emails WHERE share_code = ? AND created_by = ?",
                [$shareCode, $userId]
            );
            
            if ($result->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Share deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Share not found or access denied']);
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