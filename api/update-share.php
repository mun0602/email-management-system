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
    $collectionName = $input['collection_name'] ?? null;
    $password = $input['password'] ?? null;
    $isActive = $input['is_active'] ?? null;
    
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
    
    // Update collection
    $pdo = getDBConnection();
    $updates = [];
    $params = [];
    
    if ($collectionName !== null) {
        $updates[] = "collection_name = ?";
        $params[] = $collectionName;
    }
    
    if ($password !== null) {
        $updates[] = "password_hash = ?";
        $params[] = empty($password) ? null : password_hash($password, PASSWORD_DEFAULT);
    }
    
    if ($isActive !== null) {
        $updates[] = "is_active = ?";
        $params[] = $isActive ? 1 : 0;
    }
    
    if (empty($updates)) {
        jsonResponse(['success' => false, 'message' => 'No updates provided']);
    }
    
    $params[] = $collectionId;
    if (!isAdmin()) {
        $updates[] = "user_id = ?";
        $params[] = $userId;
    }
    
    $sql = "UPDATE shared_collections SET " . implode(', ', $updates) . " WHERE id = ?";
    if (!isAdmin()) {
        $sql .= " AND user_id = ?";
    }
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        jsonResponse(['success' => true, 'message' => 'Collection updated successfully']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update collection']);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update share: ' . $e->getMessage()]);
}
?>