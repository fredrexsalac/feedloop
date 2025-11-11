<?php
session_start();
require '../../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $user_id = $_SESSION['user_id'];
        $admin_position = $_SESSION['position'] ?? 'Admin';
        
        // Get dynamic theme based on admin role/position
        function getAdminTheme($position) {
            $position_lower = strtolower($position);
            
            if (strpos($position_lower, 'super') !== false) {
                return 'animated'; // Super Admin gets animated theme
            } else {
                return 'light'; // Regular Admin gets light theme
            }
        }
        
        $default_theme = getAdminTheme($admin_position);
        
        // Get user's saved theme preference
        $stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'theme_mode'");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        $theme = $result ? $result['setting_value'] : $default_theme;
        
        echo json_encode([
            'success' => true, 
            'theme' => $theme,
            'default_theme' => $default_theme,
            'user_role' => $_SESSION['role'],
            'user_position' => $admin_position
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error getting theme: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
