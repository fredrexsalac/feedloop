<?php
/**
 * Password Reset Code Verification API
 * Validates the 6-digit code sent to user's email
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

// Prevent any output before headers
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate input
    $resetToken = trim($input['reset_token'] ?? '');
    $resetCode = trim($input['reset_code'] ?? '');
    
    if (empty($resetToken) || empty($resetCode)) {
        throw new Exception('Reset token and verification code are required');
    }
    
    // Validate code format (6 digits)
    if (!preg_match('/^\d{6}$/', $resetCode)) {
        throw new Exception('Invalid verification code format');
    }
    
    // Find the reset token first (regardless of status)
    $stmt = $pdo->prepare("
        SELECT prt.*, u.user_id, u.username, u.email
        FROM password_reset_tokens prt
        JOIN users u ON u.user_id = prt.user_id
        WHERE prt.reset_token = ?
    ");
    $stmt->execute([$resetToken]);
    $resetRecord = $stmt->fetch();

    if (!$resetRecord) {
        // Log failed attempt (unknown token)
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_attempts 
            (email, ip_address, attempt_type, success, error_message, user_agent)
            VALUES (?, ?, 'verify', 0, 'Token not found', ?)
        ");
        $stmt->execute([
            'unknown',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        throw new Exception('Invalid reset token. Please request a new code.');
    }
    
    // Specific checks
    if ((int)$resetRecord['is_used'] === 1) {
        throw new Exception('This reset token has already been used. Please request a new code.');
    }
    
    // Expiration check (use DB comparison to avoid timezone issues)
    $stmt = $pdo->prepare('SELECT NOW() < ? AS not_expired');
    $stmt->execute([$resetRecord['expires_at']]);
    $row = $stmt->fetch();
    if (empty($row) || (int)$row['not_expired'] !== 1) {
        throw new Exception('This reset token has expired. Please request a new code.');
    }
    
    // Check if too many attempts
    if ($resetRecord['attempts'] >= $resetRecord['max_attempts']) {
        // Mark token as used to prevent further attempts
        $stmt = $pdo->prepare("
            UPDATE password_reset_tokens 
            SET is_used = 1 
            WHERE id = ?
        ");
        $stmt->execute([$resetRecord['id']]);
        
        throw new Exception('Too many verification attempts. Please request a new reset code.');
    }
    
    // Verify the code
    if ($resetCode !== $resetRecord['reset_code']) {
        // Increment attempt count ONLY on mismatch
        $stmt = $pdo->prepare("
            UPDATE password_reset_tokens 
            SET attempts = attempts + 1 
            WHERE id = ?
        ");
        $stmt->execute([$resetRecord['id']]);
        
        // Log failed attempt
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_attempts 
            (email, ip_address, attempt_type, success, error_message, user_agent)
            VALUES (?, ?, 'verify', 0, 'Invalid verification code', ?)
        ");
        $stmt->execute([
            $resetRecord['email'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $remainingAttempts = $resetRecord['max_attempts'] - $resetRecord['attempts'];
        throw new Exception("Invalid verification code. {$remainingAttempts} attempts remaining.");
    }
    
    // Code is valid - generate session token for password reset
    $sessionToken = bin2hex(random_bytes(32));
    
    // Store session token (valid for 10 minutes for password reset)
    $stmt = $pdo->prepare("
        UPDATE password_reset_tokens 
        SET reset_code = ?, expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        WHERE id = ?
    ");
    $stmt->execute([$sessionToken, $resetRecord['id']]);
    
    // Log successful verification
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_attempts 
        (email, ip_address, attempt_type, success, user_agent)
        VALUES (?, ?, 'verify', 1, ?)
    ");
    $stmt->execute([
        $resetRecord['email'],
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification code confirmed. You can now reset your password.',
        'session_token' => $sessionToken,
        'username' => $resetRecord['username'],
        'expires_in' => 600 // 10 minutes for password reset
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
