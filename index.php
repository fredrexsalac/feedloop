<?php
/**
 * FeedLoop Smart Router
 * 
 * Automatically redirects users based on their login status and role
 * instead of always showing the landing page
 */

// Enable HTTPS security
require_once 'includes/https_redirect.php';

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection for user verification
require_once 'db.php';

// Load configuration
$config = include 'config/landing_config.php';

// Check for maintenance mode
if ($config['settings']['maintenance_mode'] ?? false) {
    include 'maintenance.html';
    exit();
}

// Check if user explicitly wants to see landing page (bypass smart redirect)
if (isset($_GET['landing']) || isset($_GET['home'])) {
    // User explicitly wants landing page - skip smart redirect
    $show_landing = true;
} else {
    // Smart redirect based on user status
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // User is logged in - redirect to appropriate dashboard
        try {
            // Debug: Log session info
            error_log("Index.php - Session check: user_id=" . $_SESSION['user_id'] . ", role=" . $_SESSION['role']);
            
            // Verify session is still valid
            if ($_SESSION['role'] === 'admin') {
                // Admin user - check if session is valid
                $stmt = $pdo->prepare("SELECT user_id, username, position FROM users u LEFT JOIN admins a ON u.user_id = a.user_id WHERE u.user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Valid admin session - redirect to admin dashboard
                    header('Location: admin/dashboard_admin/admin_dashboard.php');
                    exit();
                } else {
                    // Invalid session - clear and redirect to login
                    session_unset();
                    session_destroy();
                    header('Location: admin/login.php');
                    exit();
                }
            } elseif ($_SESSION['role'] === 'user') {
                // Frontend user - check if session is valid
                $stmt = $pdo->prepare("SELECT id, username FROM frontend_users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Valid user session - redirect to user portal
                    header('Location: pages/user_portal.php');
                    exit();
                } else {
                    // Invalid session - clear and redirect to login
                    session_unset();
                    session_destroy();
                    header('Location: auth/login.php');
                    exit();
                }
            }
        } catch (Exception $e) {
            // Database error - log and continue to landing page
            error_log("Session verification error: " . $e->getMessage());
        }
    }
    $show_landing = false;
}

// No valid session - show landing page
$landing_version = $config['version'] ?? 'php';

switch ($landing_version) {
    case 'php':
    case 'html':
        // Always use the new HTML landing page
        include 'index.html';
        break;
        
    case 'original':
    default:
        // Fallback to new landing as well
        include 'index.html';
        break;
}
?>