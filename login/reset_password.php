<?php
session_start();
require '../db.php';
require '../includes/email_system.php';

$message = '';
$message_type = '';
$token = $_GET['token'] ?? '';
$user_type = $_GET['type'] ?? 'user';

// Initialize email system
$email_system = new EmailSystem($pdo);

// Handle password reset form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reset_password'])) {
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate passwords
        if (empty($new_password) || empty($confirm_password)) {
            $message = "Please fill in all fields.";
            $message_type = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match.";
            $message_type = "danger";
        } elseif (strlen($new_password) < 6) {
            $message = "Password must be at least 6 characters long.";
            $message_type = "danger";
        } else {
            try {
                // Check if token exists and is valid
                $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = 0");
                $stmt->execute([$token]);
                $reset_request = $stmt->fetch();
                
                if ($reset_request) {
                    // Update user password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    if ($reset_request['user_type'] === 'admin') {
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    } else {
                        $stmt = $pdo->prepare("UPDATE frontend_users SET password = ? WHERE email = ?");
                    }
                    
                    $stmt->execute([$hashed_password, $reset_request['email']]);
                    
                    // Mark token as used
                    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                    $stmt->execute([$token]);
                    
                    $message = "Your password has been successfully reset. You can now log in with your new password.";
                    $message_type = "success";
                    
                    // Clear the token from URL
                    $token = '';
                } else {
                    $message = "Invalid or expired reset token. Please request a new password reset.";
                    $message_type = "danger";
                }
            } catch (Exception $e) {
                $message = "Error resetting password. Please try again.";
                $message_type = "danger";
            }
        }
    } elseif (isset($_POST['request_reset'])) {
        $email = trim($_POST['email']);
        $user_type = $_POST['user_type'];
        
        if (empty($email)) {
            $message = "Please enter your email address.";
            $message_type = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "danger";
        } else {
            try {
                // Check if user exists
                if ($user_type === 'admin') {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'super_admin')");
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM frontend_users WHERE email = ? AND status = 'active'");
                }
                
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Create password reset tokens table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(255) NOT NULL,
                        token VARCHAR(255) NOT NULL UNIQUE,
                        user_type ENUM('user', 'admin') NOT NULL,
                        expires_at TIMESTAMP NOT NULL,
                        used BOOLEAN DEFAULT FALSE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    // Generate secure token
                    $reset_token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in database
                    $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, user_type, expires_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$email, $reset_token, $user_type, $expires_at]);
                    
                    // Send reset email
                    $email_sent = $email_system->sendPasswordReset($email, $reset_token, $user_type);
                    
                    if ($email_sent) {
                        $message = "Password reset instructions have been sent to your email address. Please check your inbox and follow the instructions.";
                        $message_type = "success";
                    } else {
                        $message = "Password reset email has been queued for delivery. Please check your email in a few minutes.";
                        $message_type = "info";
                    }
                } else {
                    // Don't reveal if email exists or not for security
                    $message = "If an account with that email exists, password reset instructions have been sent.";
                    $message_type = "info";
                }
            } catch (Exception $e) {
                $message = "Error processing request. Please try again later.";
                $message_type = "danger";
            }
        }
    }
}

// Check if token is provided and valid
$valid_token = false;
$token_user_type = 'user';
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = 0");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();
        
        if ($reset_request) {
            $valid_token = true;
            $token_user_type = $reset_request['user_type'];
        }
    } catch (Exception $e) {
        // Token validation failed
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .reset-body {
            padding: 30px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .back-link {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #0d6efd;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #198754; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h2><i class="fas fa-key me-2"></i>Password Reset</h2>
                <p class="mb-0">FeedLoop System</p>
            </div>
            
            <div class="reset-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> mb-4">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($valid_token): ?>
                    <!-- Reset Password Form -->
                    <form method="POST" action="">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <h4 class="mb-3">
                            <i class="fas fa-lock me-2"></i>Set New Password
                        </h4>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   required minlength="6" onkeyup="checkPasswordStrength()">
                            <div id="password-strength" class="password-strength"></div>
                            <small class="text-muted">Password must be at least 6 characters long</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required onkeyup="checkPasswordMatch()">
                            <div id="password-match" class="mt-1"></div>
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn btn-primary btn-reset w-100">
                            <i class="fas fa-save me-2"></i>Reset Password
                        </button>
                    </form>
                    
                <?php else: ?>
                    <!-- Request Reset Form -->
                    <form method="POST" action="">
                        <h4 class="mb-3">
                            <i class="fas fa-envelope me-2"></i>Request Password Reset
                        </h4>
                        
                        <div class="mb-3">
                            <label for="user_type" class="form-label">Account Type</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="user" <?php echo $user_type === 'user' ? 'selected' : ''; ?>>Student/User Account</option>
                                <option value="admin" <?php echo $user_type === 'admin' ? 'selected' : ''; ?>>Admin Account</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your email address" required>
                            <small class="text-muted">We'll send reset instructions to this email</small>
                        </div>
                        
                        <button type="submit" name="request_reset" class="btn btn-primary btn-reset w-100">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                        </button>
                    </form>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="../frontend_login.php" class="back-link">
                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth/password_utils.js"></script>
</body>
</html>
