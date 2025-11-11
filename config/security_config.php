<?php
/**
 * Security Configuration for FeedLoop
 * Defines security settings and constants
 * Author: Cascade AI Assistant
 * Date: October 24, 2025
 */

// Prevent direct access
if (!defined('FEEDLOOP_SECURE')) {
    die('Direct access not permitted');
}

// Security Configuration
return [
    // HTTPS Settings
    'force_https' => true,
    'hsts_max_age' => 31536000, // 1 year
    'hsts_include_subdomains' => true,
    'hsts_preload' => true,
    
    // Session Security
    'session_secure' => true,
    'session_httponly' => true,
    'session_samesite' => 'Strict',
    'session_regenerate_interval' => 300, // 5 minutes
    
    // Password Security
    'password_min_length' => 8,
    'password_require_uppercase' => true,
    'password_require_lowercase' => true,
    'password_require_numbers' => true,
    'password_require_special' => true,
    'password_hash_algo' => PASSWORD_ARGON2ID,
    
    // Rate Limiting
    'login_attempts_max' => 5,
    'login_attempts_window' => 900, // 15 minutes
    'api_rate_limit' => 100, // requests per minute
    
    // Content Security Policy
    'csp_default_src' => "'self'",
    'csp_script_src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api.qrserver.com",
    'csp_style_src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    'csp_img_src' => "'self' data: https: blob:",
    'csp_font_src' => "'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    'csp_connect_src' => "'self' https://api.qrserver.com",
    
    // File Upload Security
    'upload_max_size' => 5242880, // 5MB
    'upload_allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
    'upload_scan_viruses' => false, // Set to true if antivirus available
    
    // Database Security
    'db_ssl_mode' => 'preferred',
    'db_charset' => 'utf8mb4',
    
    // Logging
    'log_security_events' => true,
    'log_failed_logins' => true,
    'log_admin_actions' => true,
    
    // Development/Production
    'environment' => 'development', // Change to 'production' for live site
    'debug_mode' => false,
    'error_reporting' => false
];
?>
