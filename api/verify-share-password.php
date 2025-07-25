<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/share-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $shareToken = $input['share_token'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($shareToken)) {
        jsonResponse(['success' => false, 'message' => 'Share token is required']);
    }
    
    $collection = getSharedCollection($shareToken);
    if (!$collection) {
        jsonResponse(['success' => false, 'message' => 'Share not found or inactive']);
    }
    
    // Check password if required
    if ($collection['password_hash']) {
        if (empty($password)) {
            jsonResponse(['success' => false, 'message' => 'Password is required']);
        }
        
        if (!password_verify($password, $collection['password_hash'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid password']);
        }
    }
    
    // Set session for authenticated access
    $_SESSION['shared_authenticated'][$shareToken] = time() + (24 * 60 * 60); // 24 hours
    
    jsonResponse(['success' => true, 'message' => 'Password verified successfully']);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
}
?>