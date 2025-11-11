<?php
/**
 * Password Reset Interface
 * Multi-step password reset process with email verification
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

// Enable HTTPS security
require_once '../includes/https_redirect.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - FeedLoop</title>
    
    <!-- Bootstrap CSS -->
    <link href="../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
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
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .reset-body {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .step-connector {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin-top: 19px;
        }
        
        .step-connector.active {
            background: #28a745; /* green to match completed steps */
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .verification-code-input {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            font-family: monospace;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .loading {
            display: none;
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .timer {
            font-weight: bold;
            color: #dc3545;
        }
        
        .resend-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .resend-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h2><i class="fas fa-lock me-2"></i>Password Reset</h2>
                <p class="mb-0">Secure password recovery process</p>
            </div>
            
            <div class="reset-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">1</div>
                    <div class="step-connector" id="connector1"></div>
                    <div class="step inactive" id="step2-indicator">2</div>
                    <div class="step-connector" id="connector2"></div>
                    <div class="step inactive" id="step3-indicator">3</div>
                </div>
                
                <!-- Alert Container -->
                <div id="alert-container"></div>
                
                <!-- Step 1: Email Input -->
                <div class="step-content active" id="step1">
                    <h4 class="text-center mb-4">Enter Your Email Address</h4>
                    <form id="email-form">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your registered email address" required>
                            <div class="form-text">
                                We'll send a 6-digit verification code to this email address.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="btn-text">Send Verification Code</span>
                            <span class="loading">
                                <i class="fas fa-spinner fa-spin me-2"></i>Sending...
                            </span>
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-muted">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </div>
                
                <!-- Step 2: Code Verification -->
                <div class="step-content" id="step2">
                    <h4 class="text-center mb-4">Enter Verification Code</h4>
                    <form id="verification-form">
                        <div class="form-group">
                            <label for="verification-code" class="form-label">
                                <i class="fas fa-key me-2"></i>6-Digit Code
                            </label>
                            <input type="text" class="form-control verification-code-input" 
                                   id="verification-code" name="verification_code" 
                                   placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                            <div class="form-text">
                                Check your email for the verification code. 
                                <span class="timer" id="timer">Expires in 15:00</span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="btn-text">Verify Code</span>
                            <span class="loading">
                                <i class="fas fa-spinner fa-spin me-2"></i>Verifying...
                            </span>
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-2">Didn't receive the code?</p>
                        <a href="#" class="resend-link" id="resend-code">
                            <i class="fas fa-redo me-2"></i>Resend Code
                        </a>
                    </div>
                </div>
                
                <!-- Step 3: New Password -->
                <div class="step-content" id="step3">
                    <h4 class="text-center mb-4">Create New Password</h4>
                    <form id="password-form">
                        <div class="form-group">
                            <label for="new-password" class="form-label">
                                <i class="fas fa-lock me-2"></i>New Password
                            </label>
                            <input type="password" class="form-control" id="new-password" 
                                   name="new_password" placeholder="Enter new password" required>
                            <div class="password-strength">
                                <div class="strength-bar bg-light" id="strength-bar"></div>
                                <small class="form-text" id="strength-text">
                                    Password must contain at least 8 characters, including uppercase, lowercase, and number.
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm-password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm-password" 
                                   name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="btn-text">Reset Password</span>
                            <span class="loading">
                                <i class="fas fa-spinner fa-spin me-2"></i>Resetting...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Password Reset JavaScript -->
    <script src="../assets/js/public/password_reset.js"></script>
</body>
</html>
