<?php
/**
 * FeedLoop Smart Router
 * Automatically redirects users based on their login status and role
 * or shows the landing page.
 */

// Enable HTTPS security if available
require_once 'includes/https_redirect.php';

// Start session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Load configuration
$config = include 'config/landing_config.php';

// Check maintenance mode
if ($config['settings']['maintenance_mode'] ?? false) {
    include 'maintenance.html';
    exit();
}

// Determine if landing page should be shown
$show_landing = false;

// Localhost / explicit landing query always show landing
if (isset($_GET['landing']) || isset($_GET['home']) || $_SERVER['HTTP_HOST'] === 'localhost:8080') {
    $show_landing = true;
} else {
    // Smart redirect based on session
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        try {
            $role = $_SESSION['role'];
            $userId = $_SESSION['user_id'];

            if ($role === 'admin') {
                $stmt = $pdo->prepare("
                    SELECT u.user_id 
                    FROM users u 
                    LEFT JOIN admins a ON u.user_id = a.user_id 
                    WHERE u.user_id = ?
                ");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ($user) {
                    header('Location: admin/dashboard_admin/admin_dashboard.php');
                    exit();
                } else {
                    session_unset();
                    session_destroy();
                    header('Location: admin/login.php');
                    exit();
                }
            } elseif ($role === 'user') {
                $stmt = $pdo->prepare("SELECT id FROM frontend_users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ($user) {
                    header('Location: pages/user_portal.php');
                    exit();
                } else {
                    session_unset();
                    session_destroy();
                    header('Location: auth/login.php');
                    exit();
                }
            }
        } catch (Exception $e) {
            error_log("Session verification error: " . $e->getMessage());
            // Fall back to landing page
            $show_landing = true;
        }
    } else {
        // No valid session - show landing
        $show_landing = true;
    }
}

// Determine which landing page version to use
$landing_version = $config['version'] ?? 'php';

// Always use HTML landing for localhost
if ($_SERVER['HTTP_HOST'] === 'localhost:8080') {
    $landing_version = 'html';
}

// Show landing page
if ($show_landing) {
    switch ($landing_version) {
        case 'php':
        case 'html':
        default:
            include 'index.html';
            break;
    }
}
?>
