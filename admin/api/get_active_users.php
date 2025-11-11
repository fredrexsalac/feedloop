<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$response = [
    'success' => false,
    'users' => [],
    'count' => 0,
    'debug' => []
];

try {
    // Check if session is valid
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    // Include database connection
    require_once __DIR__ . '/../../db.php';
    
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }

    // Get active admin and user sessions (last 5 minutes) using unified users table
    $query = "
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.last_activity,
            (u.session_id = :session_id) as is_current_session,
            COALESCE(a.full_name, u.full_name, u.username) as full_name,
            'admin' as user_type,
            a.position as admin_position,
            NULL as student_info
        FROM users u
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE u.role = 'admin'
          AND u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)

        UNION ALL

        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.last_activity,
            0 as is_current_session,
            COALESCE(u.full_name, u.username) as full_name,
            'user' as user_type,
            NULL as admin_position,
            NULL as student_info
        FROM users u
        WHERE u.role = 'user'
          AND u.user_id IN (
                SELECT DISTINCT user_id 
                FROM activity_logs 
                WHERE timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                  AND user_id IS NOT NULL
          )

        ORDER BY 
            CASE 
                WHEN user_id = :user_id THEN 0 
                WHEN user_type = 'admin' THEN 1
                ELSE 2 
            END,
            last_activity DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':session_id' => session_id(),
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $response['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['count'] = count($response['users']);
    $response['success'] = true;
    $response['debug']['session_id'] = session_id();
    $response['debug']['user_id'] = $_SESSION['user_id'];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    $response['debug']['trace'] = $e->getTraceAsString();
}

echo json_encode($response);
