<?php
// Check if user is logged in - support both unified and legacy session variables
$is_logged_in = false;
$username = '';
$full_name = '';
$user_id = null;

// Check unified session variables first (new system)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    $is_logged_in = true;
    $username = $_SESSION['username'] ?? '';
    $full_name = $_SESSION['full_name'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;
}
// Fallback to legacy frontend session variables
elseif (isset($_SESSION['frontend_logged_in']) && $_SESSION['frontend_logged_in']) {
    $is_logged_in = true;
    $username = $_SESSION['frontend_username'] ?? '';
    $full_name = $_SESSION['frontend_full_name'] ?? '';
    $user_id = $_SESSION['frontend_user_id'] ?? null;
}

// Get user profile picture if logged in
$user_profile_pic = '';
if ($is_logged_in && !empty($user_id)) {
    try {
        // Only load db.php if not already loaded
        if (!isset($pdo)) {
            require_once __DIR__ . '/../db.php';
        }
        
        // Check if profile_pic column exists in users table (unified system)
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE user_id = ? AND role = 'user'");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            if ($result && !empty($result['profile_pic'])) {
                $user_profile_pic = $result['profile_pic'];
            }
        }
    } catch (Exception $e) {
        // Silently handle error - don't show database errors
        $user_profile_pic = '';
    }
}

// Determine the base path for links
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/homepage/') !== false) {
    $base_path = '../';
} elseif (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $base_path = '../';
} elseif (strpos($_SERVER['PHP_SELF'], '/feedback/') !== false) {
    $base_path = '../';
} elseif (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $base_path = '../';
} else {
    $base_path = './';
}
?>

<!-- Navbar -->
<!-- Theme CSS -->
<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/themes.css">
<script src="<?php echo $base_path; ?>assets/js/theme-switcher.js"></script>

<style>
.logo-with-border {
  border: 3px solid #007bff;
  padding: 8px;
  border-radius: 50%;
  background-color: white;
  transition: all 0.3s ease;
}

.logo-with-border:hover {
  border-color: #0056b3;
  box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
  transform: scale(1.05);
}
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top" style="background-color: var(--bs-body-bg, #fff) !important;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo $base_path; ?>index.php">
      <img src="<?php echo $base_path; ?>assets/img/logo/feedloop.jpg" alt="FeedLoop Logo" class="logo-with-border me-2" style="width: 32px; height: 32px;">
      <span class="text-primary">FeedLoop</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarFeedloop" aria-controls="navbarFeedloop" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarFeedloop">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/index.php') !== false) ? 'active text-primary' : ''; ?>" href="<?php echo $base_path; ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/user_portal.php') !== false) ? 'active text-primary' : ''; ?>" href="<?php echo $base_path; ?>pages/user_portal.php">Announcements</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>index.php#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>index.php#faq">FAQ</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <?php if ($is_logged_in): ?>
          <!-- Notifications Dropdown -->
          <div class="dropdown notification-dropdown me-3">
            <a href="#" id="notificationDropdown" class="btn btn-outline-primary position-relative" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-bell"></i>
              <span id="notificationBadge" class="notification-badge">0</span>
            </a>
            
            <!-- Notifications Menu -->
            <div id="notificationsMenu" class="dropdown-menu dropdown-menu-end bg-white border shadow">
              <div class="notification-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 px-3 py-2"><i class="fas fa-bell me-2"></i>Notifications</h6>
              </div>
              <div class="notification-items">
                <!-- Notifications will be loaded here via JavaScript -->
                <div class="p-3 text-center text-muted">
                  <small>No new notifications</small>
                </div>
              </div>
              <div class="notification-footer text-center p-2">
                <a href="<?php echo $base_path; ?>pages/notifications.php" class="btn btn-sm btn-primary w-100">
                  <i class="fas fa-list me-1"></i>View All Notifications
                </a>
              </div>
            </div>
          </div>
          
          <!-- Settings Dropdown -->
          <div class="dropdown me-3">
            <a href="#" class="dropdown-toggle btn btn-outline-primary" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-cog"></i> Settings
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
              <li><h6 class="dropdown-header">User Settings</h6></li>
              <li><a class="dropdown-item" href="<?php echo $base_path; ?>pages/user_profile.php"><i class="fas fa-user me-2"></i>Profile Settings</a></li>
              <li><a class="dropdown-item" href="<?php echo $base_path; ?>pages/change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><h6 class="dropdown-header">Theme Settings</h6></li>
              <li>
                <div class="px-3 py-2">
                  <div class="form-check">
                    <input class="form-check-input theme-option" type="radio" name="themeOption" id="lightTheme" value="light" checked>
                    <label class="form-check-label" for="lightTheme">Light Mode</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input theme-option" type="radio" name="themeOption" id="darkTheme" value="dark">
                    <label class="form-check-label" for="darkTheme">Dark Mode</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input theme-option" type="radio" name="themeOption" id="animatedTheme" value="animated">
                    <label class="form-check-label" for="animatedTheme">Animated Mix</label>
                  </div>
                </div>
              </li>
            </ul>
          </div>
          
          <?php if ($is_logged_in && !empty($username) && isset($_SESSION['frontend_user_id'])): ?>
          <span class="navbar-text me-3 d-flex align-items-center">
            <?php if (!empty($user_profile_pic)): ?>
              <?php
              // Clean up the profile pic path - remove any leading ../ since we're using base_path
              $clean_pic_path = str_replace('../', '', $user_profile_pic);
              ?>
              <img src="<?php echo $base_path . $clean_pic_path; ?>" alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #007bff;">
            <?php else: ?>
              <i class="fas fa-user-circle me-2"></i>
            <?php endif; ?>
            Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>
          </span>
          <?php endif; ?>
          <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
        <?php else: ?>
          <a href="<?php echo $base_path; ?>auth/login.php" class="btn btn-outline-primary me-2">Login</a>
          <a href="<?php echo $base_path; ?>auth/register.php" class="btn btn-primary">Create Account</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Notification Styles -->
<style>
  /* Notification Dropdown Styles */
  .notification-dropdown {
    position: relative;
  }
  
  .notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 50%;
    background-color: #dc3545;
    color: white;
    display: none;
  }
  
  #notificationsMenu {
    position: absolute;
    right: 0;
    top: 100%;
    width: 350px;
    z-index: 1000;
    border-radius: 0.25rem;
    max-height: 400px;
    overflow-y: auto;
  }
  
  #notificationsMenu.show {
    display: block;
  }
  
  #notificationsMenu.show {
    display: block;
  }
  
  .notification-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1rem;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
  }
  
  .notification-footer {
    background-color: #f8f9fa;
    padding: 0.75rem 1rem;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
  }
  
  .notification-items {
    max-height: 300px;
    overflow-y: auto;
  }
  
  .notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f1f1;
  }
  
  .notification-item:hover {
    background-color: #f8f9fa;
  }
  
  .notification-item.unread {
    background-color: #e3f2fd;
  }
  
  .notification-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }
  
  .notification-text {
    color: #6c757d;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>

<!-- Logout Modal -->
<?php if ($is_logged_in): ?>
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to log out of your account?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="<?php echo $base_path; ?>auth/logout.php" class="btn btn-primary">Logout</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Load notification script for logged in users -->
<?php if ($is_logged_in): ?>
<script src="<?php echo $base_path; ?>assets/js/notifications.js"></script>
<?php endif; ?>