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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $user_id = $_SESSION['user_id'];
        $admin_role = $_SESSION['role'];
        
        // Create user-specific settings table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS user_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            setting_key VARCHAR(255) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_setting (user_id, setting_key),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        $pdo->exec($createTable);
        
        if ($input['type'] === 'general') {
            // Save general settings for this specific user
            $admin_email = filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL);
            $session_timeout = intval($input['session_timeout']);
            $theme_mode = $input['theme_mode'] ?? 'light';
            
            if (!$admin_email) {
                throw new Exception('Invalid email address');
            }
            
            if ($session_timeout < 5 || $session_timeout > 1440) {
                throw new Exception('Session timeout must be between 5 and 1440 minutes');
            }
            
            // Validate theme mode
            $valid_themes = ['light', 'dark', 'animated', 'auto'];
            if (!in_array($theme_mode, $valid_themes)) {
                throw new Exception('Invalid theme mode');
            }
            
            // Update user's email in the admins table
            $updateEmailStmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $updateEmailStmt->execute([$admin_email, $user_id]);
            
            // Update or insert user-specific settings
            $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
            
            $stmt->execute([$user_id, 'session_timeout', $session_timeout]);
            $stmt->execute([$user_id, 'theme_mode', $theme_mode]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Your personal settings saved successfully!',
                'user_role' => $admin_role,
                'theme_applied' => $theme_mode
            ]);
            
        } elseif ($input['type'] === 'security') {
            // Save security preferences for this specific user
            $require_strong_password = $input['require_strong_password'] ? '1' : '0';
            $enable_activity_logging = $input['enable_activity_logging'] ? '1' : '0';
            $max_login_attempts = intval($input['max_login_attempts']);
            
            if ($max_login_attempts < 1 || $max_login_attempts > 20) {
                throw new Exception('Max login attempts must be between 1 and 20');
            }
            
            // Update or insert user-specific security settings
            $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
            
            $stmt->execute([$user_id, 'require_strong_password', $require_strong_password]);
            $stmt->execute([$user_id, 'enable_activity_logging', $enable_activity_logging]);
            $stmt->execute([$user_id, 'max_login_attempts', $max_login_attempts]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Your personal security settings saved successfully!',
                'user_role' => $admin_role
            ]);
        } else {
            throw new Exception('Invalid settings type');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error saving settings: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>