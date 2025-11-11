<?php
session_start();
require_once '../../db.php';
require_once '../../includes/session_manager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    echo json_encode(['valid' => false, 'reason' => 'No session data']);
    exit();
}

// Determine user type for logging (simplified - only Admin and Super Admin)
$user_type = 'Unknown';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $user_type = isset($_SESSION['position']) && strpos(strtolower($_SESSION['position']), 'super') !== false 
        ? 'Super Admin' : 'Admin';
}

try {
    $sessionManager = new SessionManager($pdo);
    
    // Validate session token against database
    $isValid = $sessionManager->validateSession($_SESSION['user_id'], $_SESSION['session_token']);
    
    if (!$isValid) {
        // Session is invalid, clear it
        session_unset();
        session_destroy();
        echo json_encode(['valid' => false, 'reason' => 'Session token mismatch']);
        exit();
    }
    
    // Check session timeout (1 hour)
    $session_timeout = 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
        session_unset();
        session_destroy();
        echo json_encode(['valid' => false, 'reason' => 'Session timeout']);
        exit();
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Session is valid
    echo json_encode([
        'valid' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'user_type' => $user_type,
        'last_activity' => $_SESSION['last_activity']
    ]);
    
} catch (Exception $e) {
    error_log("Session validation error: " . $e->getMessage());
    echo json_encode(['valid' => false, 'reason' => 'Database error']);
}
?>
