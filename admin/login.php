<?php
// Redirect to unified login for consistency
header('Location: unified_login.php');
exit();

// Admin Login - Only accessible through /admin/ directory
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
            // Check if user is suspended
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
    <title>FeedLoop Control Center • Administrative Portal</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/login/unified_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        
        .admin-login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border: 3px solid #28a745;
        }
        
        .admin-login-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .admin-login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 2px,
                rgba(255,255,255,0.03) 2px,
                rgba(255,255,255,0.03) 4px
            );
            animation: securePattern 20s linear infinite;
        }
        
        @keyframes securePattern {
            0% { transform: translateX(-50px) translateY(-50px); }
            100% { transform: translateX(50px) translateY(50px); }
        }
        
        .admin-login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        
        .admin-login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 2;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .admin-login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .btn-admin-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #20c997;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <h1><i class="fas fa-user-shield"></i> FeedLoop</h1>
                <p>Admin Account</p>
            </div>
            
            <div class="admin-login-form">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="adminLoginForm">
                    <input type="hidden" name="ajax" value="1">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="" required>
                    </div>
                    
                    <button type="submit" class="btn-admin-login" id="loginBtn">
                        Login
                    </button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p style="margin: 10px 0;">
                            <a href="../password_reset.php" style="color: #007bff; text-decoration: none; font-weight: 500;">
                                <i class="fas fa-key me-1"></i>Forgot Password?
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <a href="../" class="back-link">
            ← Back to Homepage
        </a>
    </div>

    <!-- External JavaScript -->
    <script src="../assets/js/admin/admin_login.js"></script>
</body>
</html>
