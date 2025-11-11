<?php
/**
 * FeedLoop - Secure Admin Registration Configuration
 * 
 * This file contains configuration settings for the secure admin registration system.
 * Keep this file secure and change the default values before deployment.
 */

// ===========================================
// SECURITY CONFIGURATION
// ===========================================

// Secret token for accessing registration page
// IMPORTANT: Change this to a unique, complex string before deployment
define('ADMIN_REG_SECRET_TOKEN', 'FL2024_ADMIN_REG_SECURE_TOKEN_XYZ789');

// Enable/disable registration functionality
// Set to false to completely disable admin registration
define('ADMIN_REG_ENABLED', true);

// Time-based access control (optional)
// Set specific hours when registration is allowed (24-hour format)
define('ADMIN_REG_START_HOUR', 0);  // 00:00 (midnight)
define('ADMIN_REG_END_HOUR', 23);   // 23:59 (11:59 PM)

// IP whitelist (optional) - Leave empty to allow all IPs
// Add specific IP addresses that are allowed to access registration
$ALLOWED_IPS = [
    // '127.0.0.1',        // Localhost
    // '192.168.1.100',    // Specific local IP
    // '203.0.113.1',      // Specific public IP
];

// ===========================================
// URL CONFIGURATION
// ===========================================

// Different URL patterns you can use (choose one):
// 1. Token-based: /admin/secure-registration.php?token=YOUR_SECRET_TOKEN
// 2. Path-based: /admin/management-portal.php (rename the file)
// 3. Subdirectory: /admin/internal/register.php (create subdirectory)

// ===========================================
// NOTIFICATION SETTINGS
// ===========================================

// Email notification when new admin is registered
define('NOTIFY_ON_REGISTRATION', true);
define('NOTIFICATION_EMAIL', 'admin@feedloop.com'); // Change this

// ===========================================
// SECURITY FUNCTIONS
// ===========================================

/**
 * Check if registration is currently allowed
 */
function isRegistrationAllowed() {
    // Check if registration is enabled
    if (!ADMIN_REG_ENABLED) {
        return false;
    }
    
    // Check time restrictions
    $current_hour = (int)date('H');
    if ($current_hour < ADMIN_REG_START_HOUR || $current_hour > ADMIN_REG_END_HOUR) {
        return false;
    }
    
    // Check IP restrictions
    global $ALLOWED_IPS;
    if (!empty($ALLOWED_IPS)) {
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($client_ip, $ALLOWED_IPS)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Validate secret token
 */
function validateSecretToken($provided_token) {
    return hash_equals(ADMIN_REG_SECRET_TOKEN, $provided_token);
}

/**
 * Generate secure registration URLs
 */
function getRegistrationUrls() {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    
    return [
        'secure_token' => $base_url . '/secure-registration.php?token=' . ADMIN_REG_SECRET_TOKEN,
        'management_portal' => $base_url . '/management-portal.php?access=' . ADMIN_REG_SECRET_TOKEN,
        'internal_access' => $base_url . '/internal/admin-setup.php?key=' . ADMIN_REG_SECRET_TOKEN
    ];
}

/**
 * Log registration attempts
 */
function logRegistrationAttempt($success, $username = '', $ip = '', $details = '') {
    $log_file = __DIR__ . '/../logs/admin_registration.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    $log_entry = "[$timestamp] $status - IP: $ip - Username: $username - Details: $details" . PHP_EOL;
    
    // Ensure logs directory exists
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Send notification email for new admin registration
 */
function sendRegistrationNotification($admin_data) {
    if (!NOTIFY_ON_REGISTRATION || !NOTIFICATION_EMAIL) {
        return;
    }
    
    $subject = 'New Admin Account Created - FeedLoop';
    $message = "A new administrator account has been created:\n\n";
    $message .= "Username: " . $admin_data['username'] . "\n";
    $message .= "Full Name: " . $admin_data['full_name'] . "\n";
    $message .= "Position: " . $admin_data['position'] . "\n";
    $message .= "Email: " . $admin_data['email'] . "\n";
    $message .= "Registration Time: " . date('Y-m-d H:i:s') . "\n";
    $message .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n";
    $message .= "Please verify this registration was authorized.";
    
    $headers = 'From: noreply@feedloop.com' . "\r\n" .
               'Reply-To: noreply@feedloop.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    mail(NOTIFICATION_EMAIL, $subject, $message, $headers);
}

// ===========================================
// ALTERNATIVE URL SUGGESTIONS
// ===========================================

/*
SECURITY RECOMMENDATIONS:

1. RENAME THE FILE to something less obvious:
   - management-portal.php
   - system-setup.php
   - internal-access.php
   - admin-onboarding.php

2. USE SUBDIRECTORIES:
   - /admin/internal/setup.php
   - /admin/system/register.php
   - /admin/secure/onboard.php

3. IMPLEMENT ADDITIONAL SECURITY:
   - Time-based access (only certain hours)
   - IP whitelisting
   - Rate limiting
   - CAPTCHA verification
   - Two-factor authentication

4. EXAMPLE SECURE URLS:
   - https://yoursite.com/admin/management-portal.php?access=YOUR_SECRET_TOKEN
   - https://yoursite.com/admin/internal/setup.php?key=YOUR_SECRET_TOKEN
   - https://yoursite.com/admin/system-onboarding.php?token=YOUR_SECRET_TOKEN

5. DISABLE AFTER USE:
   - Set ADMIN_REG_ENABLED to false after creating initial admins
   - Delete the registration file entirely
   - Move to a completely different location
*/

?>
