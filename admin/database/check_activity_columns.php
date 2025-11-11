<?php
require '../../db.php';

try {
    // Check if last_activity column exists
    $stmt = $pdo->query("
        SELECT COUNT(*) as column_exists
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'last_activity'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['column_exists'] == 0) {
        // Add the missing columns
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN last_activity TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ADD COLUMN session_id VARCHAR(255) NULL DEFAULT NULL,
            ADD INDEX idx_last_activity (last_activity);
        ");
        echo "Added last_activity and session_id columns to users table.\n";
    } else {
        echo "last_activity column already exists.\n";
    }
    
    // Show current active users
    $stmt = $pdo->query("
        SELECT u.user_id, u.username, u.last_activity, 
               COALESCE(a.full_name, s.full_name) as full_name,
               CASE 
                   WHEN a.user_id IS NOT NULL THEN 'admin' 
                   WHEN s.user_id IS NOT NULL THEN 'student' 
                   ELSE 'unknown' 
               END as user_type
        FROM users u
        LEFT JOIN admins a ON u.user_id = a.user_id
        LEFT JOIN students s ON u.user_id = s.user_id
        WHERE u.last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ORDER BY u.last_activity DESC
    ");
    
    $activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nActive Users (last 15 minutes):\n";
    echo str_repeat("-", 80) . "\n";
    echo sprintf("%-20s %-20s %-20s %s\n", "Username", "Name", "Type", "Last Activity");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($activeUsers as $user) {
        echo sprintf("%-20s %-20s %-20s %s\n",
            $user['username'],
            $user['full_name'],
            $user['user_type'],
            $user['last_activity']
        );
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nScript completed. Check your admin dashboard to see the changes.\n";
