<?php
// Create share functionality
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_POST && $_POST['action'] === 'create_share') {
    try {
        if (!isLoggedIn()) {
            throw new Exception('Unauthorized');
        }
        
        $mail_ids = $_POST['mail_ids'] ?? [];
        $password = trim($_POST['password'] ?? '');
        
        if (empty($mail_ids) || !is_array($mail_ids)) {
            throw new Exception('Vui lòng chọn ít nhất một mail để chia sẻ');
        }
        
        // Validate mail_ids belong to user or user is admin
        $db = new Database();
        $user = getCurrentUser();
        
        if ($user['role'] !== 'admin') {
            // For regular users, check if they own these mails
            $placeholders = str_repeat('?,', count($mail_ids) - 1) . '?';
            $ownedMails = $db->fetchAll(
                "SELECT DISTINCT m.id 
                 FROM mails m 
                 JOIN mail_history h ON m.id = h.mail_id 
                 WHERE h.user_id = ? AND m.id IN ($placeholders)",
                array_merge([$user['id']], $mail_ids)
            );
            
            if (count($ownedMails) !== count($mail_ids)) {
                throw new Exception('Bạn chỉ có thể chia sẻ mail mà bạn đã lấy');
            }
        }
        
        // Generate share token
        $shareToken = generateShareToken();
        $hashedPassword = !empty($password) ? md5($password) : null;
        
        // Insert share record
        $db->query(
            "INSERT INTO shared_mails (share_token, mail_ids, password, created_by) VALUES (?, ?, ?, ?)",
            [$shareToken, json_encode($mail_ids), $hashedPassword, $user['id']]
        );
        
        $shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
                    $_SERVER['HTTP_HOST'] . '/shared/?token=' . $shareToken;
        
        echo json_encode([
            'success' => true,
            'share_token' => $shareToken,
            'share_url' => $shareUrl
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>