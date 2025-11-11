<?php
/**
 * Email Configuration Template
 * Copy this file to email_config.php and fill in your details
 * 
 * IMPORTANT: email_config.php is gitignored - never commit credentials!
 */

if (!defined('FEEDLOOP_SECURE')) {
    die('Direct access not permitted');
}

return [
    // Gmail SMTP Configuration (OPTIONAL)
    // Leave empty to use development mode (OTP shown on screen)
    'smtp_host' => '', // 'smtp.gmail.com' for Gmail
    'smtp_port' => 587,
    'smtp_username' => '', // Your Gmail address
    'smtp_password' => '', // Gmail App Password (NOT your regular password)
    'smtp_from_email' => '', // Same as smtp_username
    'smtp_from_name' => 'FeedLoop System',
    
    // Email Settings
    'subject_reset' => 'Password Reset - FeedLoop',
    'subject_confirmation' => 'Password Reset Successful - FeedLoop',
    'subject_registration' => 'Verify Your FeedLoop Account',
    
    // Security Settings
    'rate_limit_window' => 3600, // 1 hour in seconds
    'max_reset_attempts' => 3,
    'otp_expiration' => 900, // 15 minutes in seconds
    
    // Development Mode
    // If true, OTP codes will be displayed on screen instead of emailed
    'dev_mode' => true, // Set to false in production
    
    /**
     * HOW TO GET GMAIL APP PASSWORD:
     * 
     * 1. Go to https://myaccount.google.com/security
     * 2. Enable "2-Step Verification" if not already enabled
     * 3. Go to "App passwords" section
     * 4. Select "Mail" and "Other (Custom name)"
     * 5. Enter "FeedLoop" as the name
     * 6. Click "Generate"
     * 7. Copy the 16-character password
     * 8. Paste it in 'smtp_password' above
     * 
     * SECURITY NOTES:
     * - Never share your App Password
     * - Never commit email_config.php to Git
     * - Use environment variables in production
     * - Rotate passwords regularly
     */
];
