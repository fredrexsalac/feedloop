<?php
// Session Manager - Handles session security and validation

class SessionManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Invalidate all existing sessions for a user when they log in
     * Works for all user types: Admin, Super Admin
     */
    public function invalidateUserSessions($user_id) {
        try {
            // Generate new session token
            $new_token = bin2hex(random_bytes(32));
            
            // Check if users table has the required columns, if not, just return token
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM users LIKE 'active_session_token'");
            $stmt->execute();
            $column_exists = $stmt->fetch();
            
            if ($column_exists) {
                // Update user's session tracking to invalidate old sessions
                $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW(), active_session_token = ? WHERE user_id = ?");
                $stmt->execute([$new_token, $user_id]);
            } else {
                // Column doesn't exist, just log the session change
                error_log("Session token column not found, using session-only invalidation for user_id: $user_id");
            }
            
            return $new_token;
        } catch (Exception $e) {
            error_log("Session invalidation error: " . $e->getMessage());
            // Return a token anyway for session management
            return bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Validate if current session is still valid
     * Works for all user types: Admin, Super Admin
     */
    public function validateSession($user_id, $session_token) {
        try {
            // Check if the column exists first
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM users LIKE 'active_session_token'");
            $stmt->execute();
            $column_exists = $stmt->fetch();
            
            if ($column_exists) {
                $stmt = $this->pdo->prepare("SELECT active_session_token FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if (!$user || $user['active_session_token'] !== $session_token) {
                    return false;
                }
            } else {
                // If column doesn't exist, validate based on session existence only
                $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                if (!$stmt->fetch()) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log session activity
     */
    public function logSessionActivity($user_id, $action, $details = '') {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())");
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt->execute([$user_id, $action, $details, $ip]);
        } catch (Exception $e) {
            error_log("Session logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Force logout by clearing session token
     */
    public function forceLogout($user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET active_session_token = NULL WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Clear PHP session
            session_unset();
            session_destroy();
            
            return true;
        } catch (Exception $e) {
            error_log("Force logout error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Check if session is valid - use in protected pages
 */
function checkSessionValidity() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    // Optional: Add session timeout check
    $session_timeout = 3600; // 1 hour
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Redirect to login if session invalid
 */
function requireValidSession($redirect_to_login = true) {
    if (!checkSessionValidity()) {
        if ($redirect_to_login) {
            session_unset();
            session_destroy();
            header("Location: ../login/unified_login.php");
            exit();
        }
        return false;
    }
    return true;
}
?>
