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
    $input = json_decode(file_get_contents('php://input'), true);
    $collectionId = $input['collection_id'] ?? null;
    
    if (!$collectionId) {
        jsonResponse(['success' => false, 'message' => 'Collection ID is required']);
    }
    
    $userId = getCurrentUserId();
    
    // Verify ownership (unless admin)
    if (!isAdmin()) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT user_id FROM shared_collections WHERE id = ?");
        $stmt->execute([$collectionId]);
        $collection = $stmt->fetch();
        
        if (!$collection || $collection['user_id'] != $userId) {
            jsonResponse(['success' => false, 'message' => 'Collection not found or access denied']);
        }
    }
    
    // Delete collection (cascading will handle shared_emails)
    $result = deleteSharedCollection($collectionId, isAdmin() ? null : $userId);
    
    if ($result) {
        jsonResponse(['success' => true, 'message' => 'Collection deleted successfully']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to delete collection']);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to delete share: ' . $e->getMessage()]);
}
?>