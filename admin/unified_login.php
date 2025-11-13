<?php
// Set session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Lax');

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging
function log_debug($message) {
    $log_file = __DIR__ . '/../logs/login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

require '../db.php'; // Include database connection
require '../includes/session_manager.php'; // Include session manager

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Check if user exists in users table (only admin)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if user account is suspended
            if (isset($user['status']) && $user['status'] === 'suspended') {
                $suspension_message = "Sorry, your account has been suspended due to violation of policies. Contact your administrator for more details.";
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'suspended' => true, 'message' => $suspension_message]);
                    exit();
                }
                // Set suspension flag for modal display
                $show_suspension_modal = true;
                $error_message = $suspension_message;
            } else {
                // Login successful - use session manager to handle security
                $sessionManager = new SessionManager($pdo);
                
                // Invalidate all existing sessions for this user
                $new_session_token = $sessionManager->invalidateUserSessions($user['user_id']);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Clear any existing session data
                session_unset();
                
                // Set new session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                $_SESSION['session_token'] = $new_session_token;
                
                // Log the login activity
                $sessionManager->logSessionActivity($user['user_id'], 'login', 'User logged in successfully');
                
                $redirect_url = '';
                $user_type = '';
                $position = '';
                
                if ($user['role'] === 'admin') {
                    // Get admin details
                    $stmt_admin = $pdo->prepare("SELECT * FROM admins WHERE user_id = ?");
                    $stmt_admin->execute([$user['user_id']]);
                    $admin = $stmt_admin->fetch();
                    
                    if ($admin) {
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['full_name'] = $admin['full_name'];
                        $_SESSION['position'] = $admin['position'];
                        $position = $admin['position'];
                        
                        // Determine user type and redirect based on actual position from database
                        // All admins now use the same dashboard (formerly super admin dashboard)
                        if (strtolower($position) === 'super admin') {
                            $user_type = 'Super Admin';
                        } else {
                            $user_type = 'Admin';
                        }
                        $redirect_url = 'dashboard_admin/admin_dashboard.php';
                        
                        // Log admin activity
                        try {
                            require_once '../includes/activity_logger.php';
                            logActivity($pdo, $user['user_id'], 'admin_login', "Admin login successful - Position: $position");
                        } catch (Exception $e) {
                            error_log("Error logging admin login: " . $e->getMessage());
                        }
                    }
                } else {
                    // Invalid role - only admin roles are allowed
                    $error_message = "Access denied. Only administrators can log in.";
                    if (isset($_POST['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $error_message]);
                        exit();
                    }
                }
                
                // Set login success data
                $_SESSION['login_success'] = true;
                $_SESSION['login_redirect'] = $redirect_url;
                $_SESSION['login_user_type'] = $user_type;
                
                log_debug("Login successful - User: $username, Type: $user_type");
                
                // Return JSON response for AJAX
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'user_type' => $user_type,
                        'user_id' => $user['user_id'],
                        'redirect_url' => $redirect_url
                    ]);
                    exit();
                }
                
                // Regular form submission redirect with tab close
                echo '<script>
                    window.location.href = "' . $redirect_url . '";
                    setTimeout(function() {
                        window.close();
                    }, 500);
                </script>';
                exit();
            }
        } else {
            $error_message = "Invalid username or password.";
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error_message]);
                exit();
            }
        }
    } catch (Exception $e) {
        log_debug("Login error: " . $e->getMessage());
        $error_message = "Database connection error. Please try again.";
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error_message]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/login/unified_login.css">
    <link rel="stylesheet" href="../assets/css/login/login_modal.css">
    <style>
        /* Theme-based styling */
        .theme-admin .logo-border {
            border-color: #28a745 !important;
            box-shadow: 0 0 20px rgba(40, 167, 69, 0.3) !important;
        }
        
        .theme-super-admin .logo-border {
            border-color: #fd7e14 !important;
            box-shadow: 0 0 20px rgba(253, 126, 20, 0.3) !important;
        }
        
        .theme-admin .btn-login {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
        }
        
        .theme-super-admin .btn-login {
            background: linear-gradient(135deg, #fd7e14, #ffc107) !important;
        }
        
        .theme-admin .form-control:focus {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
        
        .theme-super-admin .form-control:focus {
            border-color: #fd7e14 !important;
            box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25) !important;
        }
        
        /* Spinner animation */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo with dynamic border -->
            <div class="logo-container">
                <div class="logo-border" id="logoBorder">
                    <img src="../assets/img/logo/logo.jpg" alt="FeedLoop Logo" class="logo">
                </div>
            </div>
            
            <h1 class="login-title">Welcome to FeedLoop</h1>
            <p class="login-subtitle">Admin Account</p>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="ajax" value="1">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Enter your username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginBtn">
                    <span class="btn-text">Login</span>
                    <span class="btn-spinner" style="display: none;">
                        <div class="spinner"></div>
                    </span>
                </button>
            </form>
            
            <div class="login-links">
                <p><a href="forgot_password.php">Forgot Password?</a></p>
                <p><a href="../">← Back to Homepage</a></p>
            </div>
        </div>
    </div>

    <!-- Suspension Modal -->
    <div id="suspensionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="suspension-icon" id="suspensionIcon">⚠️</div>
            <h2 id="suspensionTitle">Account Suspended</h2>
            <p id="suspensionMessage">Your account has been suspended.</p>
            <button onclick="closeSuspensionModal()" style="margin-top: 20px; padding: 10px 20px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 8px; cursor: pointer; font-size: 14px;">Close</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="success-icon" id="successIcon">✓</div>
            <h2 id="successTitle">Login Successful!</h2>
            <p id="welcomeMessage">Welcome to your account</p>
            <div class="countdown">Redirecting in <span id="countdown">3</span> seconds...</div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="../assets/js/admin/admin_unified_login.js"></script>
</body>
</html>
