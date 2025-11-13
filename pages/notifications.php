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
$user_name = !empty($full_name) ? $full_name : ($username ?: 'User');
$notifications = [];
$unread_count = 0;

// Load profile pic for navbar (best-effort)
$profile_pic = '';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE user_id = ? AND role = 'user'");
        $stmt->execute([$user_id]);
        $res = $stmt->fetch();
        if ($res && !empty($res['profile_pic'])) { $profile_pic = $res['profile_pic']; }
    }
} catch (Exception $e) { /* ignore */ }

try {
    // Get all notifications for the user
    $stmt = $pdo->prepare("SELECT n.* 
                          FROM user_notifications n
                          WHERE n.user_id = ? 
                          ORDER BY n.created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    // Count unread notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error = "Error loading notifications: " . $e->getMessage();
}

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['mark_read'], $user_id]);
        header("Location: notifications.php");
        exit();
    } catch (Exception $e) {
        // Continue with error message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - FeedLoop</title>
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
        
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        
        .notification-card.unread {
            background-color: #e3f2fd;
            border-left-color: #2196f3;
        }
        
        .notification-card.read {
            background-color: white;
            border-left-color: #dee2e6;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .notification-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .badge-unread {
            background-color: #ff4444;
            color: white;
            font-size: 0.75rem;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation (same as user_portal/profile) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo/logo.jpg" alt="FeedLoop" height="40" class="me-2">
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
                        <a class="nav-link active" href="notifications.php">
                            <i class="fas fa-bell me-1"></i>Notifications
                            <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_profile.php">
                            <i class="fas fa-user me-1"></i>Profile
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
    <div class="notification-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-bell me-3"></i>Notifications</h1>
                    <p class="mb-0">Stay updated with new announcements and important updates</p>
                </div>
                <!-- Removed Back to Announcements button for cleaner header -->
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-bell fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Total Notifications</h5>
                                <h3 class="mb-0"><?php echo count($notifications); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-envelope fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Unread</h5>
                                <h3 class="mb-0"><?php echo $unread_count; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No notifications yet</h4>
                <p class="text-muted">You'll receive notifications here when there are new announcements or updates.</p>
                <a href="user_portal.php" class="btn btn-primary">
                    <i class="fas fa-bullhorn me-2"></i>View Announcements
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="card notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge badge-unread">NEW</span>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-reply me-2"></i>
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h6>
                                        <p class="card-text text-muted mb-2">
                                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            From: <?php echo htmlspecialchars($notification['admin_name']); ?>
                                            <span class="ms-3">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-check me-1"></i>Mark as Read
                                    </a>
                                <?php endif; ?>
                                <a href="feedback/?view=<?php echo $notification['feedback_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye me-1"></i>View Feedback
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
