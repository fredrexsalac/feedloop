<?php
/**
 * Check Session API
 * Returns current user session status for AJAX calls
 * Author: Fredrex Salac
 * Date: November 5, 2025
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check unified session variables first (new system)
$is_logged_in = false;
$user_data = [
    'logged_in' => false,
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role' => '',
    'user_id' => null,
    'profile_pic' => ''
];

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    $is_logged_in = true;
    $user_data = [
        'logged_in' => true,
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'role' => 'user',
        'user_id' => $_SESSION['user_id'] ?? null,
        'profile_pic' => ''
    ];
    
    // Get profile picture from database if available
    if (!empty($user_data['user_id'])) {
        try {
            require_once '../db.php';
            $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE user_id = ? AND role = 'user'");
            $stmt->execute([$user_data['user_id']]);
            $result = $stmt->fetch();
            if ($result && !empty($result['profile_pic'])) {
                $user_data['profile_pic'] = $result['profile_pic'];
            }
        } catch (Exception $e) {
            // Silently handle error
        }
    }
}
// Fallback to legacy frontend session variables
elseif (isset($_SESSION['frontend_logged_in']) && $_SESSION['frontend_logged_in']) {
    $is_logged_in = true;
    $user_data = [
        'logged_in' => true,
        'username' => $_SESSION['frontend_username'] ?? '',
        'full_name' => $_SESSION['frontend_full_name'] ?? $_SESSION['frontend_username'] ?? 'User',
        'email' => $_SESSION['frontend_email'] ?? '',
        'role' => 'user',
        'user_id' => $_SESSION['frontend_user_id'] ?? null,
        'profile_pic' => ''
    ];
}

echo json_encode($user_data);
