<?php
session_start();
require '../../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $user_id = $_SESSION['user_id'];
        $current_password = $input['current_password'] ?? '';
        $new_password = $input['new_password'] ?? '';
        $confirm_password = $input['confirm_password'] ?? '';
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('All password fields are required');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('New password and confirmation do not match');
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception('New password must be at least 8 characters long');
        }
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        // Log the password change activity
        try {
            $activity_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
            $activity_stmt->execute([$user_id, 'Password changed']);
        } catch (Exception $e) {
            // Activity logging is optional, don't fail if it doesn't work
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Password changed successfully! Please use your new password for future logins.',
            'user_role' => $_SESSION['role']
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
