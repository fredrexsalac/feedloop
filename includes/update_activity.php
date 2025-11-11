<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only proceed if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../db.php'; // Go up one directory from /includes to find db.php
    
    try {
        // Debug log
        error_log("Updating activity for user " . $_SESSION['user_id'] . " with session " . session_id());
        
        // Check if columns exist, if not add them
        $pdo->exec("
            SET @dbname = DATABASE();
            SET @tablename = 'users';
            SET @columnname1 = 'last_activity';
            SET @columnname2 = 'session_id';
            
            SET @preparedStatement = (SELECT IF(
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE (table_name = @tablename)
                AND (table_schema = @dbname)
                AND (column_name = @columnname1)) = 0,
                CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname1, ' TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ADD INDEX idx_last_activity (', @columnname1, ');'),
                'SELECT 1;'
            ));
            
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
            
            SET @preparedStatement = (SELECT IF(
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE (table_name = @tablename)
                AND (table_schema = @dbname)
                AND (column_name = @columnname2)) = 0,
                CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname2, ' VARCHAR(255) NULL DEFAULT NULL;'),
                'SELECT 1;'
            ));
            
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");

        // Update user's last activity time and session ID
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_activity = NOW(),
                session_id = ?
            WHERE user_id = ?
        ");
        $stmt->execute([session_id(), $_SESSION['user_id']]);
        
        // Log successful update for debugging
        error_log("Successfully updated activity for user " . $_SESSION['user_id'] . 
                 ". Rows affected: " . $stmt->rowCount());
        
        // Verify the update
        $checkStmt = $pdo->prepare("SELECT last_activity, session_id FROM users WHERE user_id = ?");
        $checkStmt->execute([$_SESSION['user_id']]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Current activity status - Last Activity: " . ($result['last_activity'] ?? 'NULL') . 
                 ", Session ID: " . ($result['session_id'] ?? 'NULL'));
    } catch (Exception $e) {
        // Log error but don't expose to user
        error_log("Error updating user activity: " . $e->getMessage());
    }
}
