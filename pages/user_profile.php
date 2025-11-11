<?php
session_start();
require '../db.php';

// Check if user is logged in (unified session variables)
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['role']) && $_SESSION['role'] === 'user';

if (!$is_logged_in) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$success_message = '';
$error_message = '';
$user_name = !empty($full_name) ? $full_name : ($username ?: 'User');

// Unread notifications (for navbar badge)
$unread_notifications = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_notifications = (int)$stmt->fetchColumn();
} catch (Exception $e) { /* ignore */ }

// Get profile picture if exists
$profile_pic = '';
$user_type = 'student';
$user_type_other = '';
try {
    // Check if profile_pic column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        $stmt = $pdo->prepare("SELECT profile_pic, user_type, user_type_other FROM users WHERE user_id = ? AND role = 'user'");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        if ($result) {
            if (!empty($result['profile_pic'])) {
                $profile_pic = $result['profile_pic'];
            }
            if (!empty($result['user_type'])) {
                $user_type = $result['user_type'];
            }
            if (!empty($result['user_type_other'])) {
                $user_type_other = $result['user_type_other'];
            }
        }
    }
} catch (PDOException $e) {
    // Silently handle error
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_full_name = trim($_POST['full_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_user_type = trim($_POST['user_type'] ?? '');
    $new_user_type_other = trim($_POST['user_type_other'] ?? '');
    $profile_pic_update = '';
    
    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../assets/img/profile/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $profile_pic_update = $upload_path;
                $profile_pic = $upload_path; // Update current session
            } else {
                $error_message = "Failed to upload profile picture";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed";
        }
    }
    
    // Validate inputs
    $allowed_types = ['student','teacher','staff','parent','alumni','guest','other'];
    if (empty($new_full_name) || empty($new_email)) {
        $error_message = "All fields are required";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } elseif (!in_array(strtolower($new_user_type), $allowed_types, true)) {
        $error_message = "Invalid user type";
    } elseif (strtolower($new_user_type) === 'other' && $new_user_type_other === '') {
        $error_message = "Please specify your identification when selecting Other";
    } else {
        try {
            // Ensure user_type column exists (best effort)
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type VARCHAR(20) DEFAULT 'student' AFTER email");
            } catch (Exception $e) { /* ignore */ }
            // Ensure user_type_other column exists
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type_other VARCHAR(50) NULL AFTER user_type");
            } catch (Exception $e) { /* ignore */ }

            // Check if profile_pic column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
            $column_exists = $stmt->fetch();
            
            // Update user profile
            $persist_other = strtolower($new_user_type) === 'other' ? $new_user_type_other : null;
            if (!empty($profile_pic_update) && $column_exists) {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, user_type_other = ?, profile_pic = ? WHERE user_id = ? AND role = 'user'");
                $stmt->execute([$new_full_name, $new_email, strtolower($new_user_type), $persist_other, $profile_pic_update, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, user_type_other = ? WHERE user_id = ? AND role = 'user'");
                $stmt->execute([$new_full_name, $new_email, strtolower($new_user_type), $persist_other, $user_id]);
                
                // If profile pic was uploaded but column doesn't exist, warn user
                if (!empty($profile_pic_update) && !$column_exists) {
                    $error_message = "Profile updated, but profile picture feature requires database update. Please run: database/add_profile_pic_column.sql";
                }
            }
            
            // Update session variables
            $_SESSION['full_name'] = $new_full_name;
            $_SESSION['email'] = $new_email;
            $_SESSION['user_type'] = strtolower($new_user_type);
            $_SESSION['user_type_other'] = $persist_other;
            
            $full_name = $new_full_name;
            $email = $new_email;
            $user_type = strtolower($new_user_type);
            $user_type_other = $persist_other ?? '';
            
            if (empty($error_message)) {
                $success_message = "Profile updated successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - FeedLoop</title>
    <?php include '../includes/favicon.php'; ?>
    <link href="../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Theme CSS -->
    <link rel="stylesheet" href="../assets/css/themes.css">
    <!-- Shared public styles -->
    <link rel="stylesheet" href="../assets/css/public/user_portal.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation (same as user_portal) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo/feedloop.jpg" alt="FeedLoop" height="40" class="me-2">
                <span class="fw-bold text-primary">FeedLoop</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">
                            <i class="fas fa-info-circle me-1"></i>Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_portal.php">
                            <i class="fas fa-clipboard-list me-1"></i>Forms
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="user_profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="notifications.php">
                            <i class="fas fa-bell me-1"></i>Notifications
                            <?php if ($unread_notifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($is_logged_in): ?>
                    <!-- Settings Dropdown -->
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">User Settings</h6></li>
                            <li><a class="dropdown-item" href="user_profile.php"><i class="fas fa-user me-2"></i>Profile Settings</a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                        </ul>
                    </li>
                    <!-- User Dropdown with Profile Picture -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (!empty($profile_pic)): ?>
                                <?php $clean_pic = str_replace('../', '', $profile_pic); ?>
                                <img src="../<?php echo $clean_pic; ?>" alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #007bff;">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="user_profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-cog me-3"></i>Profile Settings</h1>
                    <p class="mb-0">Manage your account information</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4 mb-5">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card profile-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if (!empty($profile_pic)): ?>
                                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($username); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($full_name); ?></p>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($email); ?></p>
                        <span class="badge bg-primary mt-2 text-uppercase"><?php echo htmlspecialchars($user_type); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card profile-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
                                <div class="form-text text-muted">Upload a profile picture (JPG, PNG, GIF)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" disabled>
                                <div class="form-text text-muted">Username cannot be changed</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Identification</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <?php
                                        $types = ['student'=>'Student','teacher'=>'Teacher','staff'=>'Staff','parent'=>'Parent','alumni'=>'Alumni','guest'=>'Guest','other'=>'Other'];
                                        $currentType = strtolower($user_type ?? 'student');
                                        foreach ($types as $val => $label) {
                                            $selected = $currentType === $val ? 'selected' : '';
                                            echo "<option value=\"{$val}\" {$selected}>{$label}</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <a href="change_password.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </a>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="fas fa-user-times me-2"></i>Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">This action is permanent and will remove your account and related data.</p>
                    <p class="mb-3">Type <strong>DELETE</strong> to confirm.</p>
                    <input type="text" id="deleteConfirmInput" class="form-control" placeholder="DELETE">
                    <div class="form-text text-muted mt-2">You will be logged out after deletion.</div>
                    <div id="deleteError" class="text-danger mt-2" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    (function(){
        const btn = document.getElementById('confirmDeleteBtn');
        if (!btn) return;
        btn.addEventListener('click', async function(){
            const input = document.getElementById('deleteConfirmInput');
            const err = document.getElementById('deleteError');
            err.style.display = 'none';
            const text = (input.value || '').trim();
            if (text !== 'DELETE') {
                err.textContent = 'Please type DELETE exactly to confirm.';
                err.style.display = 'block';
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
            try {
                const res = await fetch('../api/account/delete_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ confirm: 'DELETE' })
                });
                const data = await res.json();
                if (data && data.success) {
                    window.location.href = data.redirect || '../index.php';
                } else {
                    err.textContent = (data && data.error) ? data.error : 'Failed to delete account.';
                    err.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i>Delete';
                }
            } catch (e) {
                err.textContent = 'Network error. Please try again.';
                err.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i>Delete';
            }
        });
    })();
    </script>
</body>
</html>
