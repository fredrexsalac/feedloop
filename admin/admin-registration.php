<?php
session_start();
require '../db.php';

// Verify authorization token
$auth_token = $_GET['auth'] ?? '';
$type = $_GET['type'] ?? '';

$valid_tokens = [
    'admin' => 'FL2024_ADMIN_REG_SECURE_TOKEN_XYZ789'
];

$decoded_token = base64_decode($auth_token);
if ($type !== 'admin' || !isset($valid_tokens[$type]) || $decoded_token !== $valid_tokens[$type]) {
    http_response_code(404);
    exit('Page not found');
}

// Check if this registration type is still available
$session_key = $type . '_registered';
if (isset($_SESSION[$session_key]) && $_SESSION[$session_key] === true) {
    http_response_code(404);
    exit('Registration no longer available');
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $position = $_POST['position'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($position) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address format.";
    } elseif ($type === 'admin' && $position !== 'Admin') {
        $error_message = "Invalid position for this registration type.";
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
                    
                    // All registrations are admin role
                    $role = 'admin';
                    
                    // Insert into users table
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $role]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Insert into admins table
                    $stmt = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $full_name, $position]);
                    
                    // Log the registration activity
                    try {
                        require_once '../includes/activity_logger.php';
                        logActivity($pdo, $user_id, 'admin_registration', "New $position account created via admin portal");
                    } catch (Exception $e) {
                        error_log("Error logging admin registration: " . $e->getMessage());
                    }
                    
                    $pdo->commit();
                    
                    // Mark this registration type as used
                    $_SESSION[$session_key] = true;
                    
                    $success_message = "$position account created successfully! You can now login with your credentials.";
                    
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
    <title>Admin Registration - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/login/unified_login.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_registration.css">
</head>
<body data-role="<?php echo $type; ?>">
    <div class="login-container">
        <div class="login-card">
            <!-- Logo with dynamic border -->
            <div class="logo-container">
                <div class="logo-container">
                <img src="../assets/img/logo/logo.jpg" alt="FeedLoop" class="logo">
                </div>
            </div>
            
            <h1 class="form-title">🟢 Admin Registration</h1>
            <p class="form-subtitle">Create Standard Admin account with full access</p>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <strong>✅ Success!</strong> <?php echo $success_message; ?>
                    <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.2); border-radius: 8px;">
                        <small>🔄 Redirecting to admin login in <span id="countdown">5</span> seconds...</small>
                        <button type="button" onclick="cancelRedirect()" style="margin-left: 10px; padding: 2px 8px; font-size: 0.7rem; background: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.5); border-radius: 4px; cursor: pointer;">Cancel</button>
                    </div>
                </div>
                <script>
                    // Redirect countdown functionality
                    let countdown = 5;
                    let timer;
                    const countdownElement = document.getElementById('countdown');
                    
                    function startRedirect() {
                        timer = setInterval(() => {
                            countdown--;
                            countdownElement.textContent = countdown;
                            
                            if (countdown <= 0) {
                                clearInterval(timer);
                                window.location.href = 'login.php';
                            }
                        }, 1000);
                    }
                    
                    function cancelRedirect() {
                        clearInterval(timer);
                        document.querySelector('.alert-success div').innerHTML = '<small>✋ Redirect cancelled. You can stay on this page.</small>';
                    }
                    
                    // Start the redirect timer
                    startRedirect();
                </script>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <strong>❌ Error!</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registrationForm">
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
                    <label for="position" class="form-label">Position</label>
                    <?php if ($type === 'admin'): ?>
                        <input type="hidden" name="position" value="Admin">
                        <div class="form-control" style="background: #e8f5e8; border-color: #28a745;">
                            🟢 <strong>Admin</strong> <span class="position-admin">Standard Access</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           minlength="8" required>
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           minlength="8" required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <span class="btn-text">👤 Create Account</span>
                </button>
            </form>
            
            <div class="login-links">
                <p><a href="../login/unified_login.php">← Back to Login</a></p>
                <p><a href="index.php">← Back to Admin Portal</a></p>
            </div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="../assets/js/admin/admin_registration.js"></script>
</body>
</html>
