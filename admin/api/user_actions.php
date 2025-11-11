<?php
session_start();
require '../../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Handle JSON POST data
$json_input = json_decode(file_get_contents('php://input'), true);
$action = $json_input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list_users':
        listUsers();
        break;
    case 'delete_user':
        deleteUser();
        break;
    case 'toggle_user_status':
        toggleUserStatus();
        break;
    case 'get_user_details':
        getUserDetails();
        break;
    case 'suspend_user':
        suspendUser();
        break;
    case 'unsuspend_user':
        unsuspendUser();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function listUsers() {
    global $pdo;
    
    try {
        // Only list admin users now
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, u.email, u.created_at, u.status,
                   a.admin_id, a.full_name, a.position
            FROM users u
            JOIN admins a ON u.user_id = a.user_id
            WHERE u.role = 'admin'
            ORDER BY a.position DESC, a.full_name ASC
        ");
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($users);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to list users: ' . $e->getMessage()]);
    }
}

function deleteUser() {
    global $pdo;
    
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    // Prevent deleting self
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['error' => 'Cannot delete your own account']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get user role
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn();
        
        if (!$role) {
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Delete from admin table (only admin users allowed now)
        if ($role === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } else {
            echo json_encode(['error' => 'Only admin users can be deleted']);
            return;
        }
        
        // Delete from users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        echo json_encode(['success' => 'User deleted successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}

function toggleUserStatus() {
    global $pdo;
    
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_status = $stmt->fetchColumn();
        
        if ($current_status === false) {
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Toggle status
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$new_status, $user_id]);
        
        echo json_encode(['success' => 'User status updated', 'new_status' => $new_status]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to update user status: ' . $e->getMessage()]);
    }
}

function getUserDetails() {
    global $pdo;
    
    $user_id = $_GET['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        // First check if user exists in users table
        $stmt = $pdo->prepare("SELECT user_id, username, email, created_at, role, status FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $base_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$base_user) {
            echo json_encode(['success' => false, 'message' => 'User not found in users table']);
            return;
        }
        
        // Get admin details (only admin users allowed now)
        if ($base_user['role'] === 'admin') {
            $stmt = $pdo->prepare("
                SELECT a.full_name, a.position
                FROM admins a 
                WHERE a.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $role_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo json_encode(['success' => false, 'message' => 'Only admin users are supported']);
            return;
        }
        
        // Get last login
        $stmt = $pdo->prepare("SELECT MAX(timestamp) as last_login FROM activity_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $login_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Combine all data
        $user = array_merge($base_user, $role_data ?: [], $login_data ?: []);
        $user['full_name'] = $user['full_name'] ?? 'N/A';
        
        echo json_encode(['success' => true, 'user' => $user]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get user details: ' . $e->getMessage()]);
    }
}

function unsuspendUser() {
    global $pdo;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Update user status to active
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Log the unsuspend action
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, admin_id, timestamp) VALUES (?, 'user_unsuspended', 'User account unsuspended by admin', ?, NOW())");
        $stmt->execute([$user_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User unsuspended successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to unsuspend user: ' . $e->getMessage()]);
    }
}

function suspendUser() {
    global $pdo;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    // Prevent suspending self
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot suspend your own account']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Update user status to suspended
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Log the suspension action
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, admin_id, timestamp) 
            VALUES (?, 'user_suspended', 'User account suspended by admin due to policy violation', ?, NOW())
        ");
        $stmt->execute([$user_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User suspended successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to suspend user: ' . $e->getMessage()]);
    }
}

?>
