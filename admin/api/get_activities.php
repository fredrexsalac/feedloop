<?php
require_once '../../includes/activity_logger.php';
require_once '../../db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $activities = getRecentActivities($pdo, $limit);
    
    // Format the response
    $response = [
        'status' => 'success',
        'data' => array_map(function($activity) {
            return [
                'id' => $activity['id'],
                'username' => htmlspecialchars($activity['username']),
                'role' => htmlspecialchars($activity['role']),
                'position' => $activity['position'] ? htmlspecialchars($activity['position']) : 'N/A',
                'action' => htmlspecialchars($activity['action']),
                'details' => $activity['details'] ? htmlspecialchars($activity['details']) : '',
                'timestamp' => date('M d, Y h:i A', strtotime($activity['timestamp'])),
                'ip' => $activity['ip_address'],
                'device' => $activity['user_agent']
            ];
        }, $activities)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch activities',
        'error' => $e->getMessage()
    ]);
}
