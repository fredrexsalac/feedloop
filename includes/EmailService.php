<?php
/**
 * Email Service Class for FeedLoop
 * Handles Gmail SMTP email sending for password reset
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

// Email service with Gmail SMTP support
// Tries to send via Gmail if configured, otherwise logs to file
require_once __DIR__ . '/GmailMailer.php';

class EmailService {
    private $config;
    private $pdo;
    private $mailer;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!defined('FEEDLOOP_SECURE')) {
            define('FEEDLOOP_SECURE', true);
        }
        $this->config = include __DIR__ . '/../config/email_config.php';
        $this->mailer = new GmailMailer($this->config);
    }
    
    /**
     * Get FeedLoop logo as base64 for email embedding
     */
    private function getLogoBase64() {
        $logoPath = __DIR__ . '/../assets/img/logo/logo.jpg';
        if (file_exists($logoPath)) {
            $imageData = base64_encode(file_get_contents($logoPath));
            return "data:image/jpeg;base64,{$imageData}";
        }
        // Fallback: return empty string if logo not found
        return '';
    }
    
    /**
     * Get logo HTML for email header
     */
    private function getLogoHtml() {
        $logoBase64 = $this->getLogoBase64();
        if ($logoBase64) {
            return "<img src='{$logoBase64}' alt='FeedLoop Logo' style='width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px; border: 3px solid white; object-fit: cover;'>";
        }
        return "<div style='width: 80px; height: 80px; background: white; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; color: #667eea;'>FL</div>";
    }
    
    /**
     * Send password reset email with verification code
     */
    public function sendPasswordResetEmail($email, $username, $resetCode) {
        try {
            // Check rate limiting
            if (!$this->checkRateLimit($email)) {
                throw new Exception('Too many reset attempts. Please try again later.');
            }
            
            // Prepare email content
            $subject = $this->config['subject_reset'];
            $htmlBody = $this->getResetEmailTemplate($username, $resetCode);
            $textBody = $this->getResetEmailTextTemplate($username, $resetCode);
            
            // Send email
            $result = $this->sendEmail($email, $subject, $htmlBody, $textBody);
            
            if ($result) {
                $this->logEmailActivity($email, 'reset_code_sent', 'success');
                return true;
            } else {
                throw new Exception('Failed to send email');
            }
            
        } catch (Exception $e) {
            $this->logEmailActivity($email, 'reset_code_failed', $e->getMessage());
            throw $e;
        }
    }
    
    
    /**
     * Send password reset confirmation email
     */
    public function sendPasswordResetConfirmation($email, $username) {
        try {
            $subject = $this->config['subject_confirmation'];
            $htmlBody = $this->getConfirmationEmailTemplate($username);
            $textBody = $this->getConfirmationEmailTextTemplate($username);
            
            $result = $this->sendEmail($email, $subject, $htmlBody, $textBody);
            
            if ($result) {
                $this->logEmailActivity($email, 'reset_confirmation_sent', 'success');
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logEmailActivity($email, 'reset_confirmation_failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Send registration OTP email
     */
    public function sendRegistrationOTP($email, $username, $otpCode) {
        try {
            // Prepare email content
            $subject = $this->config['subject_registration_otp'] ?? 'Verify Your FeedLoop Account';
            $htmlBody = $this->getRegistrationOTPTemplate($username, $otpCode);
            $textBody = $this->getRegistrationOTPTextTemplate($username, $otpCode);

            // Send email
            $result = $this->sendEmail($email, $subject, $htmlBody, $textBody);

            if ($result) {
                $this->logEmailActivity($email, 'registration_otp_sent', 'success');
                return true;
            } else {
                throw new Exception('Failed to send registration OTP email');
            }
        } catch (Exception $e) {
            $this->logEmailActivity($email, 'registration_otp_failed', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Core email sending function
     * Tries Gmail SMTP if configured, otherwise logs to file
     */
    private function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        // Try Gmail SMTP first if configured
        if ($this->mailer->isConfigured()) {
            $sent = $this->mailer->send($to, $subject, $htmlBody, $textBody);
            
            if ($sent) {
                error_log("Email sent via Gmail SMTP to: {$to}");
                return true;
            }
            // If SMTP fails, fall through to file logging
            error_log("Gmail SMTP failed, falling back to file logging");
        }
        
        // Fall back to file logging
        $logFile = __DIR__ . '/../logs/email_debug.log';
        $logContent = "=== EMAIL DEBUG LOG ===\n";
        $logContent .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "To: " . $to . "\n";
        $logContent .= "Subject: " . $subject . "\n";
        $logContent .= "Note: " . ($this->mailer->isConfigured() ? "Gmail SMTP failed, logged instead" : "Gmail not configured, logged instead") . "\n";
        $logContent .= "Body: " . strip_tags($htmlBody) . "\n";
        $logContent .= "========================\n\n";
        
        $logsDir = dirname($logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
        
        error_log("Email logged for: {$to}");
        return true;
    }
    
    /**
     * Check rate limiting for password reset requests
     */
    private function checkRateLimit($email) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM password_reset_attempts 
            WHERE email = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$email, $this->config['rate_limit_window']]);
        $result = $stmt->fetch();
        
        return $result['attempt_count'] < $this->config['max_reset_attempts'];
    }
    
    /**
     * Log email activity for security and debugging
     */
    private function logEmailActivity($email, $action, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (email, action, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $email,
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            // Log to file if database logging fails
            if ($this->config['enable_logging']) {
                error_log(date('Y-m-d H:i:s') . " - Email Log Error: " . $e->getMessage() . "\n", 3, $this->config['log_file']);
            }
        }
    }
    
    /**
     * HTML email template for password reset
     */
    private function getResetEmailTemplate($username, $resetCode) {
        $logo = $this->getLogoHtml();
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset - FeedLoop</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .reset-code { background: #fff; border: 2px dashed #007bff; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .code { font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 5px; font-family: monospace; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    {$logo}
                    <h1>🔐 Password Reset Request</h1>
                    <p>FeedLoop Security System</p>
                </div>
                <div class='content'>
                    <h2>Hello, {$username}!</h2>
                    <p>We received a request to reset your password for your FeedLoop account. If you made this request, please use the verification code below:</p>
                    
                    <div class='reset-code'>
                        <p><strong>Your Verification Code:</strong></p>
                        <div class='code'>{$resetCode}</div>
                        <p><small>This code expires in 15 minutes</small></p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong>
                        <ul>
                            <li>This code is valid for 15 minutes only</li>
                            <li>Do not share this code with anyone</li>
                            <li>If you didn't request this reset, please ignore this email</li>
                            <li>Your password remains unchanged until you complete the reset process</li>
                        </ul>
                    </div>
                    
                    <p>To complete your password reset:</p>
                    <ol>
                        <li>Return to the FeedLoop password reset page</li>
                        <li>Enter the verification code above</li>
                        <li>Create your new password</li>
                    </ol>
                    
                    <p>If you have any questions or concerns, please contact our support team.</p>
                    
                    <p>Best regards,<br>The FeedLoop Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System. Please do not reply to this email.</p>
                    <p>© " . date('Y') . " FeedLoop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getResetEmailTextTemplate($username, $resetCode) {
        return "
Password Reset Request - FeedLoop

Hello, {$username}!

We received a request to reset your password for your FeedLoop account.

Your Verification Code: {$resetCode}

This code expires in 15 minutes.

Security Notice:
- This code is valid for 15 minutes only
- Do not share this code with anyone
- If you didn't request this reset, please ignore this email

To complete your password reset:
1. Return to the FeedLoop password reset page
2. Enter the verification code above
3. Create your new password

Best regards,
The FeedLoop Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " FeedLoop. All rights reserved.
        ";
    }
    
    /**
     * HTML email template for password reset confirmation
     */
    private function getConfirmationEmailTemplate($username) {
        $logo = $this->getLogoHtml();
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset Successful - FeedLoop</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    {$logo}
                    <h1>✅ Password Reset Successful</h1>
                    <p>FeedLoop Security System</p>
                </div>
                <div class='content'>
                    <h2>Hello, {$username}!</h2>
                    
                    <div class='success'>
                        <strong>✅ Success!</strong> Your password has been successfully reset.
                    </div>
                    
                    <p>Your FeedLoop account password has been successfully updated. You can now log in with your new password.</p>
                    
                    <p><strong>Security Information:</strong></p>
                    <ul>
                        <li>Reset completed at: " . date('Y-m-d H:i:s T') . "</li>
                        <li>If you didn't make this change, please contact support immediately</li>
                        <li>Consider enabling two-factor authentication for added security</li>
                    </ul>
                    
                    <p>Thank you for using FeedLoop!</p>
                    
                    <p>Best regards,<br>The FeedLoop Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System.</p>
                    <p>© " . date('Y') . " FeedLoop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Plain text confirmation email template
     */
    private function getConfirmationEmailTextTemplate($username) {
        return "
Password Reset Successful - FeedLoop

Hello, {$username}!

Your FeedLoop account password has been successfully updated.

Security Information:
- Reset completed at: " . date('Y-m-d H:i:s T') . "
- If you didn't make this change, please contact support immediately

You can now log in with your new password.

Best regards,
The FeedLoop Team

---
© " . date('Y') . " FeedLoop. All rights reserved.
        ";
    }
    
    /**
     * HTML Registration OTP email template
     */
    private function getRegistrationOTPTemplate($username, $otpCode) {
        $logo = $this->getLogoHtml();
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Verify Your FeedLoop Account</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-code { background: #fff; border: 3px dashed #007bff; padding: 20px; margin: 25px 0; text-align: center; border-radius: 10px; }
                .otp-code h1 { color: #007bff; font-size: 48px; margin: 10px 0; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .btn { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    {$logo}
                    <h1>🔐 Verify Your Account</h1>
                    <p>FeedLoop Registration</p>
                </div>
                <div class='content'>
                    <h2>Hello, {$username}!</h2>
                    <p>Thank you for registering with FeedLoop! To complete your registration and verify your email address, please use the verification code below:</p>
                    
                    <div class='otp-code'>
                        <p><strong>Your Verification Code:</strong></p>
                        <h1>{$otpCode}</h1>
                        <p style='color: #666; font-size: 14px;'>This code expires in 15 minutes</p>
                    </div>
                    
                    <p>To complete your registration:</p>
                    <ol>
                        <li>Return to the FeedLoop registration page</li>
                        <li>Enter the verification code above</li>
                        <li>Complete your account setup</li>
                    </ol>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong> If you didn't request this verification code, please ignore this email. Your account will not be created without verification.
                    </div>
                    
                    <p>Welcome to FeedLoop - Your modern feedback management system!</p>
                    
                    <p>Best regards,<br>The FeedLoop Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System.</p>
                    <p>© " . date('Y') . " FeedLoop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Plain text Registration OTP email template
     */
    private function getRegistrationOTPTextTemplate($username, $otpCode) {
        return "
Verify Your FeedLoop Account

Hello, {$username}!

Thank you for registering with FeedLoop! To complete your registration, please use the verification code below:

YOUR VERIFICATION CODE: {$otpCode}

This code expires in 15 minutes.

To complete your registration:
1. Return to the FeedLoop registration page
2. Enter the verification code above
3. Complete your account setup

Security Notice: If you didn't request this verification code, please ignore this email.

Best regards,
The FeedLoop Team

---
© " . date('Y') . " FeedLoop. All rights reserved.
        ";
    }
}
?>
