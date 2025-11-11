<?php
/**
 * Email Configuration for FeedLoop
 * Gmail SMTP settings for password reset functionality
 * Author: Fredrex Salac
 * Date: October 25, 2025
 */

// Prevent direct access
if (!defined('FEEDLOOP_SECURE')) {
    define('FEEDLOOP_SECURE', true);
}

return [
    // Gmail SMTP Configuration (CONFIGURED)
    'smtp_host' => 'smtp.gmail.com', // Gmail SMTP server
    'smtp_port' => 587,
    'smtp_username' => 'fredrexsalac@gmail.com',
    'smtp_password' => 'iszrqtpeaagndljc', // Gmail App Password (spaces removed)
    'smtp_from_email' => 'fredrexsalac@gmail.com',
    'smtp_from_name' => 'FeedLoop System',
    
    // Fallback Email Settings (used when SMTP not configured)
    'from_name' => 'FeedLoop System',
    'from_email' => 'noreply@feedloop.local',
    
    // Reset Code Settings
    'reset_code_length' => 6,
    'reset_code_expiry' => 900, // 15 minutes
    'max_reset_attempts' => 5,
    'rate_limit_window' => 3600, // 1 hour in seconds
    
    // Email Templates
    'subject_reset' => 'FeedLoop - Password Reset Code',
    'subject_confirmation' => 'FeedLoop - Password Reset Successful',
    'subject_registration' => 'Verify Your FeedLoop Account',
    
    // Development Mode
    // When true, OTP codes are shown on screen instead of emailed
    'dev_mode' => false, // Gmail SMTP is now configured - emails will be sent
    
    // Security
    'enable_logging' => true,
    'log_file' => __DIR__ . '/../logs/email.log'
];

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Enable 2-Factor Authentication on your Gmail account
 * 2. Generate an App Password:
 *    - Go to Google Account settings
 *    - Security → 2-Step Verification → App passwords
 *    - Generate password for "Mail"
 * 3. Update the credentials above:
 *    - smtp_username: your-email@gmail.com
 *    - smtp_password: the 16-character app password
 *    - from_email: your-email@gmail.com
 *    - reply_to: support@yourdomain.com (optional)
 */
?>
