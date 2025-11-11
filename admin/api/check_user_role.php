<?php
session_start();
require_once '../../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit();
}

try {
    // Check if user exists and get their role and position
    $stmt = $pdo->prepare("SELECT u.role, a.position as admin_position 
                          FROM users u 
                          LEFT JOIN admins a ON u.user_id = a.user_id 
                          WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Determine theme based on role and position
    $theme = 'theme-admin'; // default for admin users
    
    if ($user['role'] === 'admin') {
        if ($user['admin_position']) {
            $position = strtolower($user['admin_position']);
            
            if (strpos($position, 'super') !== false) {
                $theme = 'theme-super-admin';
            } else {
                $theme = 'theme-admin';
            }
        } else {
            $theme = 'theme-admin';
        }
    } else {
        // Only admin roles are allowed to login
        echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can log in.']);
        exit();
    }
    
    echo json_encode([
        'success' => true, 
        'theme' => $theme,
        'role' => $user['role'],
        'position' => $user['admin_position'] ?? null
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
