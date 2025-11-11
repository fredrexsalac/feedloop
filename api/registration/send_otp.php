<?php
/**
 * Registration OTP Send API
 * Sends verification code to user's email during registration
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

try {
    require_once '../../db.php';
    require_once '../../includes/EmailService.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error: ' . $e->getMessage()
    ]);
    exit();
}

try {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log received data for debugging
    error_log("Registration OTP Request - Received data: " . json_encode($input));
    
    $email = trim($input['email'] ?? '');
    $username = trim($input['username'] ?? '');
    $full_name = trim($input['full_name'] ?? '');
    
    // Validation
    if (empty($email) || empty($username) || empty($full_name)) {
        $missing = [];
        if (empty($email)) $missing[] = 'email';
        if (empty($username)) $missing[] = 'username';
        if (empty($full_name)) $missing[] = 'full_name';
        throw new Exception('Missing required fields: ' . implode(', ', $missing));
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address format');
    }
    
    // Check if email already exists in unified users table
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('This email is already registered');
    }
    
    // Check if username already exists in unified users table
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('This username is already taken');
    }
    
    // Generate 6-digit OTP
    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in session with expiration (15 minutes)
    $_SESSION['registration_otp'] = [
        'code' => $otpCode,
        'email' => $email,
        'username' => $username,
        'full_name' => $full_name,
        'expires_at' => time() + (15 * 60), // 15 minutes
        'attempts' => 0
    ];
    
    // Try to send OTP email (optional - works if Gmail configured)
    $emailSent = false;
    $emailError = null;
    
    try {
        // Suppress PHP warnings from mail() function
        set_error_handler(function() {}, E_WARNING);
        
        $emailService = new EmailService($pdo);
        $emailService->sendRegistrationOTP($email, $username, $otpCode);
        $emailSent = true;
        
        restore_error_handler();
    } catch (Exception $e) {
        restore_error_handler();
        // Email sending failed - that's okay, we'll show OTP on screen
        $emailError = $e->getMessage();
        error_log("Email sending failed (expected in dev mode): " . $emailError);
    } catch (Error $e) {
        restore_error_handler();
        // Catch PHP errors from mail() function
        $emailError = $e->getMessage();
        error_log("Email error (expected in dev mode): " . $emailError);
    }
    
    // Log activity (skip if activity_logs requires user_id)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (action, details, ip_address, user_agent)
            VALUES ('registration_otp_generated', ?, ?, ?)
        ");
        $stmt->execute([
            "OTP generated for {$email}" . ($emailSent ? " (sent via email)" : " (email not configured)"),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Ignore activity log errors - not critical
        error_log("Activity log failed: " . $e->getMessage());
    }
    
    $response = [
        'success' => true,
        'message' => $emailSent ? 'Verification code sent to your email' : 'Verification code generated',
        'expires_in' => 900, // 15 minutes in seconds
        'email_sent' => $emailSent
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
