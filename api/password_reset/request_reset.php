<?php
/**
 * Password Reset Request API
 * Handles initial password reset requests and sends verification codes
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

// Prevent any output before headers
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
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
    echo json_encode([
        'success' => false, 
        'message' => 'Method not allowed. Expected POST, received ' . $_SERVER['REQUEST_METHOD'],
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set'
        ]
    ]);
    exit;
}

require_once '../../db.php';
require_once '../../includes/EmailService.php';

// Auto-create tables if they don't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `email` varchar(255) NOT NULL,
            `reset_token` varchar(255) NOT NULL,
            `reset_code` varchar(10) NOT NULL,
            `expires_at` datetime NOT NULL,
            `is_used` tinyint(1) DEFAULT 0,
            `attempts` int(11) DEFAULT 0,
            `max_attempts` int(11) DEFAULT 3,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `used_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_reset_token` (`reset_token`),
            KEY `idx_reset_code` (`reset_code`),
            KEY `idx_email_expires` (`email`, `expires_at`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `attempt_type` enum('request', 'verify', 'reset') NOT NULL,
            `success` tinyint(1) DEFAULT 0,
            `error_message` text DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_email_created` (`email`, `created_at`),
            KEY `idx_ip_created` (`ip_address`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Tables might already exist, continue
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate input
    $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        throw new Exception('Please enter a valid email address');
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email, u.role 
        FROM users u 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Do not reveal existence, but signal the UI to display a non-committal warning
        echo json_encode([
            'success' => true,
            'message' => 'If this email is registered, you will receive a password reset code shortly.',
            'warning' => true,
            'code_sent' => false
        ]);
        exit;
    }
    
    // Check rate limiting
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempt_count 
        FROM password_reset_attempts 
        WHERE email = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        AND attempt_type = 'request'
    ");
    $stmt->execute([$email]);
    $attempts = $stmt->fetch();
    
    if ($attempts['attempt_count'] >= 3) {
        throw new Exception('Too many reset attempts. Please try again in 1 hour.');
    }
    
    // Generate reset token and code
    $resetToken = bin2hex(random_bytes(32));
    $resetCode = sprintf('%06d', random_int(100000, 999999));
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Invalidate any existing reset tokens for this user
        $stmt = $pdo->prepare("
            UPDATE password_reset_tokens 
            SET is_used = 1 
            WHERE user_id = ? AND is_used = 0
        ");
        $stmt->execute([$user['user_id']]);
        
        // Insert new reset token
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_tokens 
            (user_id, email, reset_token, reset_code, expires_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), ?, ?)
        ");
        $stmt->execute([
            $user['user_id'],
            $email,
            $resetToken,
            $resetCode,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Log the attempt
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_attempts 
            (email, ip_address, attempt_type, success, user_agent)
            VALUES (?, ?, 'request', 1, ?)
        ");
        $stmt->execute([
            $email,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Send email
        $emailService = new EmailService($pdo);
        $emailSent = $emailService->sendPasswordResetEmail($email, $user['username'], $resetCode);
        
        if (!$emailSent) {
            throw new Exception('Failed to send reset email');
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset code sent to your email address. Please check your inbox.',
            'reset_token' => $resetToken, // Frontend needs this for verification
            'expires_in' => 900 // 15 minutes in seconds
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        
        // Log failed attempt
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_attempts 
            (email, ip_address, attempt_type, success, error_message, user_agent)
            VALUES (?, ?, 'request', 0, ?, ?)
        ");
        $stmt->execute([
            $email,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $e->getMessage(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        throw $e;
    }
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => __LINE__,
            'error_type' => get_class($e)
        ]
    ]);
} catch (Error $e) {
    // Handle fatal errors
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename(__FILE__)
        ]
    ]);
}

// Flush output buffer
ob_end_flush();
?>
