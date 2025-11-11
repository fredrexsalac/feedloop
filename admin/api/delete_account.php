<?php
session_start();
require_once '../../db.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

// Get JSON input
$json_input = json_decode(file_get_contents('php://input'), true);

if (!$json_input || $json_input['action'] !== 'delete_admin_account') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    $user_id = $_SESSION['user_id'];
    $admin_email = $_SESSION['email'];
    $admin_name = $_SESSION['full_name'];
    
    // Log the account deletion activity before deleting
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, admin_id, timestamp) 
        VALUES (?, 'account_deleted', ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id, 
        "Admin account self-deleted: $admin_name ($admin_email)", 
        $user_id
    ]);
    
    // Delete from admins table first (due to foreign key constraints)
    $stmt = $pdo->prepare("DELETE FROM admins WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete from users table
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Account not found or cannot be deleted');
    }
    
    $pdo->commit();
    
    // Destroy session
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Account deleted successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Delete account error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to delete account: ' . $e->getMessage()
    ]);
}
?>
