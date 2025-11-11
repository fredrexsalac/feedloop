<?php
session_start();
require '../db.php'; // Include database connection

// Get role from query parameter or default to student
$role = isset($_GET['role']) ? $_GET['role'] : 'student';
$role_title = ucfirst($role);

// Handle password reset request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    
    try {
        // Check if email exists for the specified role
        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT u.*, a.full_name FROM users u 
                                  JOIN admins a ON u.user_id = a.user_id 
                                  WHERE u.email = ? AND u.role = 'admin'");
        } else {
            $stmt = $pdo->prepare("SELECT u.*, s.full_name FROM users u 
                                  JOIN students s ON u.user_id = s.user_id 
                                  WHERE u.email = ? AND u.role = 'student'");
        }
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token (32 characters)
            $reset_token = bin2hex(random_bytes(16));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour
            
            // Store token in session
            $_SESSION['reset_token'] = $reset_token;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role'] = $role;
            $_SESSION['reset_expires'] = $expires_at;
            
            $success_message = "Password reset instructions have been sent to your email.";
            
        } else {
            $error_message = "No $role account found with that email address.";
        }
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long!";
    } else {
        // Verify token
        if (isset($_SESSION['reset_token']) && $_SESSION['reset_token'] === $token) {
            $email = $_SESSION['reset_email'];
            $role = $_SESSION['reset_role'];
            
            try {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ? AND role = ?");
                $stmt->execute([$hashed_password, $email, $role]);
                
                // Clear reset session
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_role']);
                unset($_SESSION['reset_expires']);
                
                $success_message = "Password reset successfully! You can now login with your new password.";
                $reset_complete = true;
                
            } catch (Exception $e) {
                $error_message = "Error resetting password: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid or expired reset token!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role_title; ?> Password Reset - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/login/<?php echo $role === 'admin' ? 'admin_login.css' : 'student_login.css'; ?>">
</head>
<body>
    <div class="login-card">
        <!-- Logo at Top Center -->
        <div class="logo-container">
            <img src="../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo">
        </div>
        
        <h1 class="form-title"><?php echo $role_title; ?> Password Reset</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['reset_token']) && !isset($reset_complete)): ?>
        <!-- Request Reset Form -->
        <form action="" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Enter your <?php echo strtolower($role_title); ?> email address">
            </div>
            <button type="submit" name="request_reset" class="btn btn-primary w-100">Send Reset Instructions</button>
        </form>
        
        <?php elseif (isset($_SESSION['reset_token']) && !isset($reset_complete)): ?>
        <!-- Reset Password Form -->
        <form action="" method="POST">
            <input type="hidden" name="token" value="<?php echo $_SESSION['reset_token']; ?>">
            
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required 
                       minlength="6" placeholder="Enter new password (min 6 characters)">
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                       placeholder="Confirm new password">
            </div>
            
            <button type="submit" name="reset_password" class="btn btn-primary w-100">Reset Password</button>
        </form>
        
        <?php elseif (isset($reset_complete)): ?>
        <!-- Reset Complete -->
        <div class="text-center">
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> Password reset successfully!
            </div>
            <a href="unified_login.php" class="btn btn-primary">Return to Login</a>
        </div>
        <?php endif; ?>
        
        <div class="login-links mt-3">
            <p><a href="unified_login.php">← Back to Login</a></p>
            <p><a href="../index.html">← Back to Homepage</a></p>
        </div>
    </div>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>
