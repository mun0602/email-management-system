<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/share-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

requireAuth();

try {
    $collectionName = sanitizeInput($_POST['collection_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailIds = $_POST['email_ids'] ?? [];
    
    if (empty($collectionName)) {
        jsonResponse(['success' => false, 'message' => 'Collection name is required']);
    }
    
    if (empty($emailIds) || !is_array($emailIds)) {
        jsonResponse(['success' => false, 'message' => 'At least one email must be selected']);
    }
    
    $userId = getCurrentUserId();
    
    // Verify user owns the selected emails (unless admin)
    if (!isAdmin()) {
        $pdo = getDBConnection();
        $placeholders = str_repeat('?,', count($emailIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id FROM emails WHERE id IN ($placeholders) AND user_id = ?");
        $stmt->execute(array_merge($emailIds, [$userId]));
        $ownedEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($ownedEmails) !== count($emailIds)) {
            jsonResponse(['success' => false, 'message' => 'You can only share your own emails']);
        }
    }
    
    $shareToken = createSharedCollection(
        $userId,
        $collectionName,
        $emailIds,
        empty($password) ? null : $password
    );
    
    jsonResponse([
        'success' => true,
        'message' => 'Share created successfully',
        'share_token' => $shareToken,
        'share_url' => getShareUrl($shareToken)
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to create share: ' . $e->getMessage()]);
}
?>