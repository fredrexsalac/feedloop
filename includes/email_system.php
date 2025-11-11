<?php
/**
 * FeedLoop Email System
 * Handles all email communications including notifications, password resets, and feedback responses
 */

class EmailSystem {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    private $pdo;
    
    public function __construct($pdo, $config = null) {
        $this->pdo = $pdo;
        
        // Default configuration (can be overridden)
        $this->smtp_host = $config['smtp_host'] ?? 'localhost';
        $this->smtp_port = $config['smtp_port'] ?? 587;
        $this->smtp_username = $config['smtp_username'] ?? '';
        $this->smtp_password = $config['smtp_password'] ?? '';
        $this->from_email = $config['from_email'] ?? 'noreply@feedloop.local';
        $this->from_name = $config['from_name'] ?? 'FeedLoop System';
    }
    
    /**
     * Send email using PHP mail() function (fallback for local development)
     */
    private function sendMail($to, $subject, $body, $headers = '') {
        $default_headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $default_headers .= "Reply-To: {$this->from_email}\r\n";
        $default_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $default_headers .= "X-Mailer: FeedLoop System\r\n";
        
        $all_headers = $default_headers . $headers;
        
        // For local development, we'll simulate email sending
        if ($this->smtp_host === 'localhost') {
            return $this->simulateEmail($to, $subject, $body);
        }
        
        return mail($to, $subject, $body, $all_headers);
    }
    
    /**
     * Simulate email sending for local development
     */
    private function simulateEmail($to, $subject, $body) {
        $log_dir = __DIR__ . '/../logs/emails/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $filename = $log_dir . date('Y-m-d_H-i-s') . '_' . md5($to . $subject) . '.html';
        $email_content = "
        <html>
        <head><title>Email Simulation - {$subject}</title></head>
        <body>
            <h2>Email Simulation</h2>
            <p><strong>To:</strong> {$to}</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
            <hr>
            {$body}
        </body>
        </html>";
        
        file_put_contents($filename, $email_content);
        
        // Log to database
        try {
            $stmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, body, status, sent_at) VALUES (?, ?, ?, 'simulated', NOW())");
            $stmt->execute([$to, $subject, $body]);
        } catch (Exception $e) {
            // Create table if it doesn't exist
            $this->createEmailLogsTable();
            $stmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, body, status, sent_at) VALUES (?, ?, ?, 'simulated', NOW())");
            $stmt->execute([$to, $subject, $body]);
        }
        
        return true;
    }
    
    /**
     * Create email logs table
     */
    private function createEmailLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT,
            status ENUM('sent', 'failed', 'simulated') DEFAULT 'sent',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            error_message TEXT NULL
        )";
        $this->pdo->exec($sql);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($email, $reset_token, $user_type = 'user') {
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/feedloop/login/reset_password.php?token={$reset_token}&type={$user_type}";
        
        $subject = "FeedLoop - Password Reset Request";
        $body = $this->getPasswordResetTemplate($reset_link, $user_type);
        
        return $this->sendMail($email, $subject, $body);
    }
    
    /**
     * Send feedback response notification
     */
    public function sendFeedbackResponse($email, $feedback_subject, $admin_response, $feedback_id) {
        $subject = "FeedLoop - Response to Your Feedback: " . $feedback_subject;
        $body = $this->getFeedbackResponseTemplate($feedback_subject, $admin_response, $feedback_id);
        
        $result = $this->sendMail($email, $subject, $body);
        
        // Log the notification
        if ($result) {
            $this->logNotification($email, $subject, $admin_response, 'feedback_response');
        }
        
        return $result;
    }
    
    /**
     * Send welcome email for new users
     */
    public function sendWelcomeEmail($email, $full_name, $username, $temp_password = null) {
        $subject = "Welcome to FeedLoop - Your Account is Ready";
        $body = $this->getWelcomeTemplate($full_name, $username, $temp_password);
        
        return $this->sendMail($email, $subject, $body);
    }
    
    /**
     * Send admin notification for new feedback
     */
    public function sendAdminNotification($admin_email, $feedback_category, $feedback_subject, $feedback_id) {
        $subject = "FeedLoop - New Feedback Received: " . $feedback_category;
        $body = $this->getAdminNotificationTemplate($feedback_category, $feedback_subject, $feedback_id);
        
        return $this->sendMail($admin_email, $subject, $body);
    }
    
    /**
     * Log notification to database
     */
    private function logNotification($user_email, $title, $message, $type) {
        try {
            // Get user ID from email
            $stmt = $this->pdo->prepare("SELECT id FROM frontend_users WHERE email = ?");
            $stmt->execute([$user_email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$user['id'], $title, $message, $type]);
            }
        } catch (Exception $e) {
            // Create notifications table if it doesn't exist
            $this->createNotificationsTable();
            if ($user) {
                $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$user['id'], $title, $message, $type]);
            }
        }
    }
    
    /**
     * Create notifications table
     */
    private function createNotificationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            type VARCHAR(50) DEFAULT 'general',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES frontend_users(id) ON DELETE CASCADE
        )";
        $this->pdo->exec($sql);
    }
    
    /**
     * Email Templates
     */
    private function getPasswordResetTemplate($reset_link, $user_type) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .button { display: inline-block; padding: 12px 24px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                    <p>FeedLoop - Feedback Management System</p>
                </div>
                <div class='content'>
                    <h2>Hello,</h2>
                    <p>We received a request to reset your password for your " . ucfirst($user_type) . " account in FeedLoop.</p>
                    <p>Click the button below to reset your password:</p>
                    <p><a href='{$reset_link}' class='button'>Reset My Password</a></p>
                    <p><strong>Important:</strong> This link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
                    <hr>
                    <p><strong>Security Tips:</strong></p>
                    <ul>
                        <li>Never share your password with anyone</li>
                        <li>Use a strong, unique password</li>
                        <li>Log out when using shared computers</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System.<br>
                    Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getFeedbackResponseTemplate($feedback_subject, $admin_response, $feedback_id) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #198754, #20c997); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .feedback-box { background: white; border-left: 4px solid #0d6efd; padding: 15px; margin: 15px 0; }
                .response-box { background: #d1edff; border-left: 4px solid #198754; padding: 15px; margin: 15px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📬 Response to Your Feedback</h1>
                    <p>FeedLoop - Feedback Management System</p>
                </div>
                <div class='content'>
                    <h2>Hello,</h2>
                    <p>We have reviewed your feedback and provided a response. Thank you for taking the time to share your thoughts with us.</p>
                    
                    <div class='feedback-box'>
                        <h3>Your Original Feedback:</h3>
                        <p><strong>Subject:</strong> {$feedback_subject}</p>
                        <p><strong>Feedback ID:</strong> #" . str_pad($feedback_id, 4, '0', STR_PAD_LEFT) . "</p>
                    </div>
                    
                    <div class='response-box'>
                        <h3>Our Response:</h3>
                        <p>" . nl2br(htmlspecialchars($admin_response)) . "</p>
                    </div>
                    
                    <p>If you have any follow-up questions or additional feedback, please don't hesitate to submit another feedback through our system.</p>
                    
                    <p>Thank you for helping us improve!</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System.<br>
                    Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getWelcomeTemplate($full_name, $username, $temp_password) {
        $password_info = $temp_password ? 
            "<p><strong>Temporary Password:</strong> <code>{$temp_password}</code></p>
             <p><em>Please change this password after your first login.</em></p>" : 
            "<p>Please use the password you created during registration.</p>";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .credentials { background: white; border: 2px solid #0d6efd; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .button { display: inline-block; padding: 12px 24px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to FeedLoop!</h1>
                    <p>Your Account is Ready</p>
                </div>
                <div class='content'>
                    <h2>Hello {$full_name},</h2>
                    <p>Welcome to FeedLoop! Your account has been successfully created and is ready to use.</p>
                    
                    <div class='credentials'>
                        <h3>Your Login Credentials:</h3>
                        <p><strong>Username:</strong> {$username}</p>
                        {$password_info}
                    </div>
                    
                    <p>You can now:</p>
                    <ul>
                        <li>Submit feedback on various topics</li>
                        <li>Track your feedback submissions</li>
                        <li>Receive notifications when admins respond</li>
                        <li>Update your profile information</li>
                    </ul>
                    
                    <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/feedloop/' class='button'>Access FeedLoop</a></p>
                    
                    <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System.<br>
                    Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getAdminNotificationTemplate($feedback_category, $feedback_subject, $feedback_id) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #fd7e14, #ffc107); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
                .button { display: inline-block; padding: 12px 24px; background: #fd7e14; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔔 New Feedback Alert</h1>
                    <p>FeedLoop Admin Notification</p>
                </div>
                <div class='content'>
                    <h2>New Feedback Received</h2>
                    
                    <div class='alert-box'>
                        <h3>Feedback Details:</h3>
                        <p><strong>Category:</strong> {$feedback_category}</p>
                        <p><strong>Subject:</strong> {$feedback_subject}</p>
                        <p><strong>Feedback ID:</strong> #" . str_pad($feedback_id, 4, '0', STR_PAD_LEFT) . "</p>
                        <p><strong>Received:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    </div>
                    
                    <p>A new feedback submission has been received and requires your attention. Please review and respond promptly.</p>
                    
                    <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/feedloop/admin/' class='button'>Review Feedback</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from FeedLoop System.<br>
                    Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
