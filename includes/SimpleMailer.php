<?php
/**
 * Simple SMTP Mailer for Gmail
 * Uses Gmail SMTP with App Password - no Google Cloud needed
 * Optional: Only used if configured in database
 */

class SimpleMailer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if SMTP is configured in database
     */
    public function isConfigured() {
        try {
            $stmt = $this->pdo->query("
                SELECT smtp_username, smtp_password 
                FROM email_smtp_config 
                WHERE is_active = 1 
                LIMIT 1
            ");
            $config = $stmt->fetch();
            
            return $config && !empty($config['smtp_username']) && !empty($config['smtp_password']);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Send email via Gmail SMTP
     */
    public function send($to, $subject, $htmlBody) {
        try {
            // Get SMTP config from database
            $stmt = $this->pdo->query("
                SELECT * FROM email_smtp_config 
                WHERE is_active = 1 
                LIMIT 1
            ");
            $config = $stmt->fetch();
            
            if (!$config) {
                return false;
            }
            
            // Create email headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: FeedLoop System <{$config['smtp_username']}>\r\n";
            $headers .= "Reply-To: {$config['smtp_username']}\r\n";
            
            // Configure PHP to use Gmail SMTP
            ini_set('SMTP', 'smtp.gmail.com');
            ini_set('smtp_port', '587');
            ini_set('sendmail_from', $config['smtp_username']);
            
            // Try to send via mail() function
            // Note: This requires proper SMTP configuration in php.ini
            $result = @mail($to, $subject, $htmlBody, $headers);
            
            if ($result) {
                error_log("Email sent via SMTP to: {$to}");
                return true;
            }
            
            // If mail() fails, try socket connection
            return $this->sendViaSocket($to, $subject, $htmlBody, $config);
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via direct socket connection to Gmail SMTP
     */
    private function sendViaSocket($to, $subject, $body, $config) {
        try {
            // Connect to Gmail SMTP
            $smtp = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 30);
            if (!$smtp) {
                return false;
            }
            
            // Read server response
            fgets($smtp, 515);
            
            // Send EHLO
            fputs($smtp, "EHLO localhost\r\n");
            fgets($smtp, 515);
            
            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            fgets($smtp, 515);
            
            // Enable crypto
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Send EHLO again
            fputs($smtp, "EHLO localhost\r\n");
            fgets($smtp, 515);
            
            // Authenticate
            fputs($smtp, "AUTH LOGIN\r\n");
            fgets($smtp, 515);
            
            fputs($smtp, base64_encode($config['smtp_username']) . "\r\n");
            fgets($smtp, 515);
            
            fputs($smtp, base64_encode($config['smtp_password']) . "\r\n");
            $auth_response = fgets($smtp, 515);
            
            if (strpos($auth_response, '235') === false) {
                fclose($smtp);
                return false;
            }
            
            // Send email
            fputs($smtp, "MAIL FROM: <{$config['smtp_username']}>\r\n");
            fgets($smtp, 515);
            
            fputs($smtp, "RCPT TO: <{$to}>\r\n");
            fgets($smtp, 515);
            
            fputs($smtp, "DATA\r\n");
            fgets($smtp, 515);
            
            // Email content
            $message = "From: FeedLoop System <{$config['smtp_username']}>\r\n";
            $message .= "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body . "\r\n";
            $message .= ".\r\n";
            
            fputs($smtp, $message);
            fgets($smtp, 515);
            
            // Quit
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            error_log("Email sent via socket to: {$to}");
            return true;
            
        } catch (Exception $e) {
            error_log("Socket SMTP Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
