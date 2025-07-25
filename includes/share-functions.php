<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Share-specific functions
function createSharedCollection($userId, $collectionName, $emailIds, $password = null) {
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();
        
        // Generate unique share token
        do {
            $shareToken = generateToken();
            $stmt = $pdo->prepare("SELECT id FROM shared_collections WHERE share_token = ?");
            $stmt->execute([$shareToken]);
        } while ($stmt->fetch());
        
        // Create shared collection
        $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        $stmt = $pdo->prepare("INSERT INTO shared_collections (user_id, collection_name, share_token, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $collectionName, $shareToken, $passwordHash]);
        $collectionId = $pdo->lastInsertId();
        
        // Add emails to collection
        if (!empty($emailIds)) {
            $stmt = $pdo->prepare("INSERT INTO shared_emails (collection_id, email_id) VALUES (?, ?)");
            foreach ($emailIds as $emailId) {
                $stmt->execute([$collectionId, $emailId]);
            }
        }
        
        $pdo->commit();
        return $shareToken;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getSharedCollection($shareToken) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM shared_collections WHERE share_token = ? AND is_active = 1");
    $stmt->execute([$shareToken]);
    return $stmt->fetch();
}

function getSharedCollectionEmails($collectionId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.id, e.email_address, e.password, e.description, e.category 
        FROM emails e 
        JOIN shared_emails se ON e.id = se.email_id 
        WHERE se.collection_id = ? AND e.is_active = 1
        ORDER BY e.email_address
    ");
    $stmt->execute([$collectionId]);
    return $stmt->fetchAll();
}

function verifySharePassword($shareToken, $password) {
    $collection = getSharedCollection($shareToken);
    if (!$collection) {
        return false;
    }
    
    if (!$collection['password_hash']) {
        return true; // No password required
    }
    
    return password_verify($password, $collection['password_hash']);
}

function incrementShareViewCount($collectionId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE shared_collections SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$collectionId]);
}

function logShareAccess($collectionId, $ipAddress, $userAgent) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO share_access_logs (collection_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$collectionId, $ipAddress, $userAgent]);
}

function getUserSharedCollections($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT sc.*, COUNT(se.id) as email_count 
        FROM shared_collections sc 
        LEFT JOIN shared_emails se ON sc.id = se.collection_id 
        WHERE sc.user_id = ? 
        GROUP BY sc.id 
        ORDER BY sc.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function deleteSharedCollection($collectionId, $userId = null) {
    $pdo = getDBConnection();
    $sql = "DELETE FROM shared_collections WHERE id = ?";
    $params = [$collectionId];
    
    if ($userId !== null) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function updateSharedCollection($collectionId, $userId, $collectionName, $password = null, $isActive = true) {
    $pdo = getDBConnection();
    
    $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
    $stmt = $pdo->prepare("UPDATE shared_collections SET collection_name = ?, password_hash = ?, is_active = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([$collectionName, $passwordHash, $isActive, $collectionId, $userId]);
}

function getShareUrl($shareToken) {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
           '://' . $_SERVER['HTTP_HOST'] . '/shared/?token=' . $shareToken;
}
?>