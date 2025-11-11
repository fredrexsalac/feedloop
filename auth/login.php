<?php
session_start();
require '../db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        try {
            // Check if user exists in unified users table (role='user')
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' AND role = 'user'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session for regular user
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'user';
                $_SESSION['logged_in'] = true;
                
                // Redirect to user portal
                header("Location: ../pages/user_portal.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (Exception $e) {
            $error_message = "Login failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth/login.css">
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
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo">
        </div>
        
        <h1 class="login-title">Welcome Back</h1>
        <p class="login-subtitle">Login to submit feedback</p>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'admin_not_allowed'): ?>
            <div class="alert alert-warning">
                <i class="fas fa-shield-alt"></i> Admin accounts cannot use Google Sign-In. Please use your admin username and password.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'not_registered'): ?>
            <div class="alert alert-danger">
                <i class="fas fa-user-times"></i> <strong>Account Not Found!</strong><br>
                The email <strong><?php echo htmlspecialchars($_GET['email'] ?? ''); ?></strong> is not registered in FeedLoop.<br>
                <small>Please <a href="register.php" class="alert-link">register first</a> using your email, then you can use Google Sign-In.</small>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'oauth_failed'): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Google Sign-In failed. Please try again or use username/password login.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
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
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
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
            <i class="fab fa-google me-2"></i> Sign in with Google
        </a>
        <?php endif; ?>
        
        <div class="login-links">
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
            <p><a href="password_reset.php"><i class="fas fa-key me-1"></i>Forgot your password?</a></p>
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
</body>
</html>
