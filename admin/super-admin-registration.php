<?php
session_start();
require '../db.php';

// Verify authorization token for Super Admin registration
$auth_token = $_GET['auth'] ?? '';
$type = $_GET['type'] ?? '';

if (base64_decode($auth_token) !== 'FL2024_SUPER_ADMIN_REG_TOKEN_ABC456' || $type !== 'super') {
    http_response_code(404);
    exit('Page not found');
}

// Check if Super Admin registration is still available
if (isset($_SESSION['super_registered']) && $_SESSION['super_registered'] === true) {
    http_response_code(404);
    exit('Registration no longer available');
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $super_admin_key = $_POST['super_admin_key'];
    
    // Super Admin verification key
    $valid_super_key = 'FEEDLOOP_SUPER_ADMIN_2024_MASTER_KEY';
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($super_admin_key)) {
        $error_message = "All fields are required.";
    } elseif ($super_admin_key !== $valid_super_key) {
        $error_message = "Invalid Super Admin verification key.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address format.";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = "Username already exists.";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = "Email already exists.";
                } else {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    // Insert into users table with admin role
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                    $stmt->execute([$username, $email, $hashed_password]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Insert into admins table with Super Admin position
                    $stmt = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, 'Super Admin')");
                    $stmt->execute([$user_id, $full_name]);
                    
                    // Log the registration activity
                    try {
                        require_once '../includes/activity_logger.php';
                        logActivity($pdo, $user_id, 'admin_registration', "New Super Admin account created via secure portal");
                    } catch (Exception $e) {
                        error_log("Error logging super admin registration: " . $e->getMessage());
                    }
                    
                    $pdo->commit();
                    
                    // Mark Super Admin registration as used
                    $_SESSION['super_registered'] = true;
                    
                    $success_message = "Super Admin account created successfully! You now have full system access.";
                    
                    // Clear form data
                    $username = $email = $full_name = '';
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Registration - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/login/unified_login.css">
    <link rel="stylesheet" href="../assets/css/admin/super_admin_registration.css">
</head>
<body class="super-admin-theme">
    <div class="login-container">
        <div class="login-card super-admin-card">
            <!-- Logo with Super Admin styling -->
            <div class="logo-container">
                <div class="logo-border">
                    <img src="../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo super-admin-logo">
                </div>
            </div>
            
            <h1 class="form-title super-admin-title">⭐ Super Admin Registration</h1>
            <p class="form-subtitle">Create Super Administrator account with full system access</p>
            
            <div class="super-admin-notice">
                <strong>🔐 Maximum Security Level</strong><br>
                Super Admin accounts have unrestricted access to all system functions.<br>
                Verification key required for registration.
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <strong>✅ Success!</strong> <?php echo $success_message; ?>
                    <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.2); border-radius: 8px;">
                        <small>🔄 Redirecting to admin login in <span id="countdown">5</span> seconds...</small>
                    </div>
                </div>
                <script>
                    // Redirect countdown functionality
                    let countdown = 5;
                    const countdownElement = document.getElementById('countdown');
                    
                    const timer = setInterval(() => {
                        countdown--;
                        countdownElement.textContent = countdown;
                        
                        if (countdown <= 0) {
                            clearInterval(timer);
                            window.location.href = 'login.php';
                        }
                    }, 1000);
                </script>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <strong>❌ Error!</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="superAdminForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           pattern="[a-zA-Z0-9_]{3,20}" title="3-20 characters, letters, numbers, and underscores only" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="super_admin_key" class="form-label">Super Admin Verification Key</label>
                    <input type="password" class="form-control super-admin-key" id="super_admin_key" name="super_admin_key" 
                           placeholder="Enter master verification key" required>
                    <small class="text-muted">Contact system administrator for verification key</small>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           minlength="8" required>
                    <small class="text-muted">Minimum 8 characters with strong complexity</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           minlength="8" required>
                </div>
                
                <button type="submit" class="btn btn-login btn-super-admin">
                    <span class="btn-text">⭐ Create Super Admin Account</span>
                </button>
            </form>
            
            <div class="login-links">
                <p><a href="../login/unified_login.php">← Back to Login</a></p>
                <p><a href="index.php">← Back to Admin Portal</a></p>
            </div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="../assets/js/admin/super_admin_registration.js"></script>
</body>
</html>
