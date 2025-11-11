<?php
/**
 * Logs user activities to the database
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $action Action performed (e.g., 'login', 'logout')
 * @param string $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logActivity($pdo, $userId, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs 
            (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId,
            $action,
            $details,
            $ip,
            $userAgent
        ]);
        
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets recent activities from the log
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Number of records to return
 * @return array Array of activity records
 */
function getRecentActivities($pdo, $limit = 20) {
    try {
        $stmt = $pdo->prepare("
            SELECT al.*, u.username, u.role, a.position 
            FROM activity_logs al
            JOIN users u ON al.user_id = u.user_id
            LEFT JOIN admins a ON u.user_id = a.user_id
            ORDER BY al.timestamp DESC 
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get Activities Error: " . $e->getMessage());
        return [];
    }
}
