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
        
        <div class="login-links">
            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="../index.php">← Back to Homepage</a></p>
        </div>
    </div>
    
    
</body>
</html>
