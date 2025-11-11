<?php
/**
 * Dev-only endpoint to fetch current registration OTP from session
 * Returns the OTP only in development environment and only if one exists
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();

try {
    // Load environment from security_config.php
    define('FEEDLOOP_SECURE', true);
    $security = require __DIR__ . '/../../config/security_config.php';
    $env = $security['environment'] ?? 'production';

    if ($env !== 'development') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not available in this environment']);
        exit;
    }

    // Ensure OTP exists in session
    $otp = $_SESSION['registration_otp'] ?? null;
    if (!$otp || empty($otp['code'])) {
        echo json_encode(['success' => false, 'error' => 'No active OTP']);
        exit;
    }

    // Optional: ensure not expired
    if (!empty($otp['expires_at']) && time() > $otp['expires_at']) {
        echo json_encode(['success' => false, 'error' => 'OTP expired']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'otp_code' => $otp['code'],
        'expires_in' => max(0, (int)$otp['expires_at'] - time()),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
