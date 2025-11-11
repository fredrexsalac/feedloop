<?php
/**
 * Google OAuth Callback Handler
 * Processes Google Sign-In and creates/logs in users
 */

session_start();
require_once '../db.php';

// Load Google OAuth config
define('FEEDLOOP_SECURE', true);
$googleConfig = include '../config/google_oauth_config.php';

// Check if OAuth is enabled
if (!$googleConfig['enabled']) {
    die('Google Sign-In is not configured. Please contact the administrator.');
}

// Check for authorization code
if (!isset($_GET['code'])) {
    header('Location: login.php?error=oauth_failed');
    exit();
}

$authCode = $_GET['code'];

try {
    // Exchange authorization code for access token
    $tokenData = [
        'code' => $authCode,
        'client_id' => $googleConfig['client_id'],
        'client_secret' => $googleConfig['client_secret'],
        'redirect_uri' => $googleConfig['redirect_uri'],
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($googleConfig['token_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to get access token from Google');
    }
    
    $tokenResponse = json_decode($response, true);
    $accessToken = $tokenResponse['access_token'];
    
    // Get user info from Google
    $ch = curl_init($googleConfig['userinfo_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!isset($userInfo['email'])) {
        throw new Exception('Failed to get user email from Google');
    }
    
    // Extract user data
    $googleEmail = $userInfo['email'];
    $googleName = $userInfo['name'] ?? '';
    $googlePicture = $userInfo['picture'] ?? null;
    $googleId = $userInfo['id'];
    $emailVerified = $userInfo['verified_email'] ?? false;
    
    // Check if user exists in database with role='user' (only registered users can use Google Sign-In)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' AND role = 'user'");
    $stmt->execute([$googleEmail]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // User exists and is registered - log them in
        $_SESSION['user_id'] = $existingUser['user_id'];
        $_SESSION['username'] = $existingUser['username'];
        $_SESSION['email'] = $existingUser['email'];
        $_SESSION['full_name'] = $existingUser['full_name'];
        $_SESSION['role'] = $existingUser['role'];
        $_SESSION['logged_in'] = true;
        
        // Update last activity
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?");
        $stmt->execute([$existingUser['user_id']]);
        
        // Update profile picture from Google if not set
        if (empty($existingUser['profile_pic']) && !empty($googlePicture)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
            $stmt->execute([$googlePicture, $existingUser['user_id']]);
        }
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, 'google_login', 'User logged in via Google OAuth', ?, ?)
        ");
        $stmt->execute([
            $existingUser['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Redirect to user portal
        header('Location: ../pages/user_portal.php');
        exit();
        
    } else {
        // Check if this email belongs to an admin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$googleEmail]);
        $adminUser = $stmt->fetch();
        
        if ($adminUser) {
            // Admin account found - Google Sign-In not allowed for admins
            header('Location: login.php?error=admin_not_allowed');
            exit();
        }
        
        // Email not registered in FeedLoop - reject login
        header('Location: login.php?error=not_registered&email=' . urlencode($googleEmail));
        exit();
    }
    
} catch (Exception $e) {
    error_log('Google OAuth Error: ' . $e->getMessage());
    header('Location: login.php?error=oauth_failed&message=' . urlencode($e->getMessage()));
    exit();
}
