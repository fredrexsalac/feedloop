<?php
/**
 * Gmail SMTP Mailer for FeedLoop
 * Sends emails using Gmail SMTP with STARTTLS
 */

class GmailMailer {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Check if Gmail SMTP is configured
     */
    public function isConfigured() {
        return !empty($this->config['smtp_host']) && 
               !empty($this->config['smtp_username']) && 
               !empty($this->config['smtp_password']);
    }
    
    /**
     * Send email via Gmail SMTP
     */
    public function send($to, $subject, $htmlBody, $textBody = null) {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            // Create plain SMTP connection (no TLS yet)
            $smtp = fsockopen(
                $this->config['smtp_host'],
                $this->config['smtp_port'],
                $errno,
                $errstr,
                30
            );
            
            if (!$smtp) {
                throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
            }
            
            // Set timeout
            stream_set_timeout($smtp, 30);
            
            // Read server greeting
            $this->getResponse($smtp);
            
            // Send EHLO
            fputs($smtp, "EHLO " . $this->config['smtp_host'] . "\r\n");
            $this->getResponse($smtp);
            
            // Upgrade to TLS using STARTTLS
            fputs($smtp, "STARTTLS\r\n");
            $response = $this->getResponse($smtp);
            
            if (strpos($response, '220') === false) {
                throw new Exception("STARTTLS command failed");
            }
            
            // Enable crypto (TLS encryption)
            $crypto = stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                throw new Exception("Failed to enable TLS encryption");
            }
            
            // Send EHLO again after TLS
            fputs($smtp, "EHLO " . $this->config['smtp_host'] . "\r\n");
            $this->getResponse($smtp);
            
            // Authenticate
            fputs($smtp, "AUTH LOGIN\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, base64_encode($this->config['smtp_username']) . "\r\n");
            $this->getResponse($smtp);
            
            fputs($smtp, base64_encode($this->config['smtp_password']) . "\r\n");
            $response = $this->getResponse($smtp);
            
            if (strpos($response, '235') === false) {
                throw new Exception("SMTP authentication failed: " . trim($response));
            }
            
            // Send MAIL FROM
            fputs($smtp, "MAIL FROM: <" . $this->config['smtp_from_email'] . ">\r\n");
            $this->getResponse($smtp);
            
            // Send RCPT TO
            fputs($smtp, "RCPT TO: <$to>\r\n");
            $this->getResponse($smtp);
            
            // Send DATA
            fputs($smtp, "DATA\r\n");
            $this->getResponse($smtp);
            
            // Build email headers and body
            $headers = "From: " . $this->config['smtp_from_name'] . " <" . $this->config['smtp_from_email'] . ">\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $message = $headers . $htmlBody . "\r\n.\r\n";
            
            fputs($smtp, $message);
            $this->getResponse($smtp);
            
            // Send QUIT
            fputs($smtp, "QUIT\r\n");
            $this->getResponse($smtp);
            
            fclose($smtp);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Gmail SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get SMTP server response
     */
    private function getResponse($smtp) {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
}
