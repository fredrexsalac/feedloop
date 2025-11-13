<?php
/**
 * User Registration with Email OTP Verification
 * Two-step registration process with Gmail verification
 */
session_start();
require '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth/register.css">
    <style>
        /* Force scrolling on mobile */
        html, body {
            height: auto !important;
            min-height: 100vh;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        body {
            align-items: flex-start !important;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .step.active .step-number {
            background: #007bff;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step-content {
            display: none;
        }
        .step-content.active {
            display: block;
        }
        .otp-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
        }
        .timer {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo-container">
            <img src="../assets/img/logo/logo.jpg" alt="FeedLoop Logo" class="logo">
        </div>
        
        <h1 class="register-title">Create Your Account</h1>
        <p class="register-subtitle">Join FeedLoop with email verification</p>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step-indicator-1">
                <div class="step-number">1</div>
                <span>Details</span>
            </div>
            <div class="step" id="step-indicator-2">
                <div class="step-number">2</div>
                <span>Verify Email</span>
            </div>
            <div class="step" id="step-indicator-3">
                <div class="step-number">3</div>
                <span>Password</span>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <div id="alert-container"></div>
        
        <!-- Step 1: User Details -->
        <div class="step-content active" id="step1">
            <form id="details-form">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Choose a username" required>
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="Enter your full name" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email Address (Gmail recommended)
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email" required>
                    <small class="text-muted">We'll send a verification code to this email</small>
                </div>
                
                <button type="submit" class="btn btn-register" id="send-otp-btn">
                    <i class="fas fa-paper-plane"></i> Send Verification Code
                </button>
            </form>
        </div>
        
        <!-- Step 2: OTP Verification -->
        <div class="step-content" id="step2">
            <h4 class="text-center mb-4">Verify Your Email</h4>
            <p class="text-center">We've sent a 6-digit code to <strong id="email-display"></strong></p>
            
            <form id="otp-form">
                <div class="mb-3">
                    <label for="otp_code" class="form-label">
                        <i class="fas fa-key me-2"></i>Verification Code
                    </label>
                    <input type="text" id="otp_code" name="otp_code" class="form-control otp-input" 
                           placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                    <small class="text-muted">
                        Check your email inbox. Code expires in <span class="timer" id="timer">15:00</span>
                    </small>
                </div>
                
                <button type="submit" class="btn btn-register" id="verify-otp-btn">
                    <i class="fas fa-check"></i> Verify Code
                </button>
                
                <button type="button" class="btn btn-secondary w-100 mt-2" id="resend-otp-btn">
                    <i class="fas fa-redo"></i> Resend Code
                </button>
            </form>
        </div>
        
        <!-- Step 3: Set Password -->
        <div class="step-content" id="step3">
            <h4 class="text-center mb-4">Set Your Password</h4>
            
            <form id="password-form">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Create a password" required>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-register" id="complete-registration-btn">
                    <i class="fas fa-user-plus"></i> Complete Registration
                </button>
            </form>
        </div>
        
        <!-- Divider -->
        <div class="divider">
            <span>OR</span>
        </div>
        
        <!-- Google Sign-In Button -->
        <?php
        define('FEEDLOOP_SECURE', true);
        $googleConfig = include '../config/google_oauth_config.php';
        if ($googleConfig['enabled']):
            $googleAuthUrl = $googleConfig['auth_url'] . '?' . http_build_query([
                'client_id' => $googleConfig['client_id'],
                'redirect_uri' => $googleConfig['redirect_uri'],
                'response_type' => 'code',
                'scope' => implode(' ', $googleConfig['scopes']),
                'access_type' => 'online',
                'prompt' => 'select_account'
            ]);
        ?>
        <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="btn btn-google">
            <i class="fab fa-google me-2"></i> Sign up with Google
        </a>
        <?php endif; ?>
        
        <div class="register-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p><a href="../index.php">← Back to Homepage</a></p>
        </div>
    </div>
    
    <style>
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider span {
            padding: 0 10px;
            color: #666;
            font-size: 14px;
        }
        .btn-google {
            width: 100%;
            padding: 12px;
            background: white;
            color: #444;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-google:hover {
            background: #f8f9fa;
            border-color: #4285f4;
            color: #4285f4;
            box-shadow: 0 2px 8px rgba(66, 133, 244, 0.2);
        }
        .btn-google i {
            color: #4285f4;
        }
    </style>
    
    <script src="../assets/js/auth/register_otp.js"></script>
</body>
</html>
