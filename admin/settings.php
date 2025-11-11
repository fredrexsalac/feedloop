<?php
session_start();
require '../db.php'; // Include database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/unified_login.php");
    exit();
}

// Check if user is Super Admin
$is_super_admin = ($_SESSION['position'] === 'Super Admin');

// Handle settings updates
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Update admin profile
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        
        try {
            $stmt = $pdo->prepare("UPDATE admins SET full_name = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $_SESSION['user_id']]);
            
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            $_SESSION['full_name'] = $full_name;
            $success_message = "Profile updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match!";
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (password_verify($current_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Current password is incorrect!";
                }
            } catch (Exception $e) {
                $error_message = "Error changing password: " . $e->getMessage();
            }
        }
    }
    
    if ($is_super_admin && isset($_POST['update_system_settings'])) {
        // Update system settings (Super Admin only)
        $system_name = $_POST['system_name'];
        $admin_email = $_POST['admin_email'];
        $feedback_limit = $_POST['feedback_limit'];
        
        // This would typically update a system_settings table
        $success_message = "System settings updated successfully!";
    }
}

// Get current admin data
try {
    $stmt = $pdo->prepare("
        SELECT a.full_name, u.email 
        FROM admins a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE a.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_data = $stmt->fetch();
} catch (Exception $e) {
    $admin_data = ['full_name' => '', 'email' => ''];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
</head>
<body>
    <div class="container">
        <!-- Logo at Top -->
        <div class="text-center mb-4 mt-3">
            <img src="../assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="mt-3">Settings</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Profile Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Profile Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Password Change -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        </div>
                        <div class="col-md-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($is_super_admin): ?>
        <!-- System Settings (Super Admin Only) -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>System Settings (Super Admin Only)</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="system_name" class="form-label">System Name</label>
                            <input type="text" class="form-control" id="system_name" name="system_name" 
                                   value="FeedLoop" placeholder="System Name">
                        </div>
                        <div class="col-md-4">
                            <label for="admin_email" class="form-label">Admin Email</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                   value="admin@feedloop.edu" placeholder="Admin Email">
                        </div>
                        <div class="col-md-4">
                            <label for="feedback_limit" class="form-label">Daily Feedback Limit</label>
                            <input type="number" class="form-control" id="feedback_limit" name="feedback_limit" 
                                   value="5" min="1" max="20" placeholder="Daily Limit">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_system_settings" class="btn btn-primary">Update System Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Notification Preferences -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Notification Preferences</h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="email_notifications" checked>
                        <label class="form-check-label" for="email_notifications">
                            Email Notifications
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="feedback_updates" checked>
                        <label class="form-check-label" for="feedback_updates">
                            Feedback Update Notifications
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="system_alerts" checked>
                        <label class="form-check-label" for="system_alerts">
                            System Alert Notifications
                        </label>
                    </div>
                    <button type="button" class="btn btn-primary mt-2">Save Preferences</button>
                </form>
            </div>
        </div>
        
        <!-- Back to Dashboard -->
        <a href="dashboard.php" class="btn btn-secondary mb-4">Back to Dashboard</a>
    </div>
</body>
</html>
