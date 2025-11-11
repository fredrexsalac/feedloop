<?php
/**
 * Registration OTP Verification API
 * Verifies OTP code and completes user registration
 */

header('Content-Type: application/json');
session_start();

require_once '../../db.php';

try {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $otpCode = trim($input['otp_code'] ?? '');
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    
    // Validation
    if (empty($otpCode)) {
        throw new Exception('Verification code is required');
    }
    
    if (!preg_match('/^\d{6}$/', $otpCode)) {
        throw new Exception('Invalid verification code format');
    }
    
    if (empty($password)) {
        throw new Exception('Password is required');
    }
    
    if ($password !== $confirm_password) {
        throw new Exception('Passwords do not match');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Check if OTP session exists
    if (!isset($_SESSION['registration_otp'])) {
        throw new Exception('No verification code found. Please request a new code.');
    }
    
    $otpData = $_SESSION['registration_otp'];
    
    // Check if OTP expired
    if (time() > $otpData['expires_at']) {
        unset($_SESSION['registration_otp']);
        throw new Exception('Verification code has expired. Please request a new code.');
    }
    
    // Check attempt limit (max 5 attempts)
    if ($otpData['attempts'] >= 5) {
        unset($_SESSION['registration_otp']);
        throw new Exception('Too many failed attempts. Please request a new code.');
    }
    
    // Verify OTP code
    if ($otpCode !== $otpData['code']) {
        $_SESSION['registration_otp']['attempts']++;
        $remainingAttempts = 5 - $_SESSION['registration_otp']['attempts'];
        throw new Exception("Invalid verification code. {$remainingAttempts} attempts remaining.");
    }
    
    // OTP is valid - create user account
    $username = $otpData['username'];
    $email = $otpData['email'];
    $full_name = $otpData['full_name'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into unified users table with role='user'
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, full_name, password, email_verified, role, status)
        VALUES (?, ?, ?, ?, 1, 'user', 'active')
    ");
    $stmt->execute([$username, $email, $full_name, $hashed_password]);
    $userId = $pdo->lastInsertId();
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'user_registration', ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        "User registered with email verification: {$email}",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Clear OTP session
    unset($_SESSION['registration_otp']);
    
    // Auto-login the user
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user';
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Your account has been created.',
        'user_id' => $userId,
        'redirect_url' => '../pages/user_portal.php?registered=success'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
