<?php
/**
 * Password Reset Completion API
 * Handles the final password reset after code verification
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../db.php';
require_once '../../includes/EmailService.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate input
    $sessionToken = trim($input['session_token'] ?? '');
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    if (empty($sessionToken) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('All fields are required');
    }
    
    // Validate passwords match
    if ($newPassword !== $confirmPassword) {
        throw new Exception('Passwords do not match');
    }
    
    // Validate password strength
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
        throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, and one number');
    }
    
    // Find the session token
    $stmt = $pdo->prepare("
        SELECT prt.*, u.user_id, u.username, u.email, u.password
        FROM password_reset_tokens prt
        JOIN users u ON u.user_id = prt.user_id
        WHERE prt.reset_code = ? 
        AND prt.is_used = 0
        AND prt.expires_at > NOW()
    ");
    $stmt->execute([$sessionToken]);
    $resetRecord = $stmt->fetch();
    
    if (!$resetRecord) {
        throw new Exception('Invalid or expired session. Please start the password reset process again.');
    }
    
    // Check if new password is same as current password
    if (password_verify($newPassword, $resetRecord['password'])) {
        throw new Exception('New password cannot be the same as your current password');
    }
    
    // Check password history (prevent reuse of last 5 passwords)
    $stmt = $pdo->prepare("
        SELECT password_hash 
        FROM password_history 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$resetRecord['user_id']]);
    $passwordHistory = $stmt->fetchAll();
    
    foreach ($passwordHistory as $oldPassword) {
        if (password_verify($newPassword, $oldPassword['password_hash'])) {
            throw new Exception('You cannot reuse one of your last 5 passwords');
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        // Update user password
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, password_updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$hashedPassword, $resetRecord['user_id']]);
        
        // Add current password to history
        $stmt = $pdo->prepare("
            INSERT INTO password_history (user_id, password_hash)
            VALUES (?, ?)
        ");
        $stmt->execute([$resetRecord['user_id'], $resetRecord['password']]);
        
        // Mark reset token as used
        $stmt = $pdo->prepare("
            UPDATE password_reset_tokens 
            SET is_used = 1, used_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$resetRecord['id']]);
        
        // Log successful reset
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_attempts 
            (email, ip_address, attempt_type, success, user_agent)
            VALUES (?, ?, 'reset', 1, ?)
        ");
        $stmt->execute([
            $resetRecord['email'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, 'password_reset', 'Password reset via email verification', ?, ?)
        ");
        $stmt->execute([
            $resetRecord['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Send confirmation email
        $emailService = new EmailService($pdo);
        $emailService->sendPasswordResetConfirmation($resetRecord['email'], $resetRecord['username']);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully! You can now log in with your new password.',
            'redirect' => '/feedloop/login.php'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        
        // Log failed attempt
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_attempts 
            (email, ip_address, attempt_type, success, error_message, user_agent)
            VALUES (?, ?, 'reset', 0, ?, ?)
        ");
        $stmt->execute([
            $resetRecord['email'] ?? 'unknown',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $e->getMessage(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
