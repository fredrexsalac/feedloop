<?php
session_start();
require '../../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_admin':
        addAdmin();
        break;
    case 'edit_admin':
        editAdmin();
        break;
    case 'delete_admin':
        deleteAdmin();
        break;
    case 'get_admin':
        getAdmin();
        break;
    case 'list_admins':
        listAdmins();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function addAdmin() {
    global $pdo;
    
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $position = $_POST['position'] ?? 'Admin';
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            return;
        }
        
        // Insert into users table
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$username, $email, $hashed_password]);
        $user_id = $pdo->lastInsertId();
        
        // Insert into admins table
        $stmt = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $position]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Admin added successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to add admin: ' . $e->getMessage()]);
    }
}

function editAdmin() {
    global $pdo;
    
    $admin_id = $_POST['admin_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($admin_id) || empty($full_name) || empty($position) || empty($email) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get user_id from admin_id
        $stmt = $pdo->prepare("SELECT user_id FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $user_id = $stmt->fetchColumn();
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
            return;
        }
        
        // Check if username or email already exists for other users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            return;
        }
        
        // Update users table
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $user_id]);
        }
        
        // Update admins table
        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, position = ? WHERE admin_id = ?");
        $stmt->execute([$full_name, $position, $admin_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update admin: ' . $e->getMessage()]);
    }
}

function deleteAdmin() {
    global $pdo;
    
    $admin_id = $_POST['admin_id'] ?? '';
    
    if (empty($admin_id)) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    try {
        // Get user_id from admin_id and check if admin exists
        $stmt = $pdo->prepare("SELECT user_id FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $user_id = $stmt->fetchColumn();
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
            return;
        }
        
        // Prevent deleting self
        if ($user_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Get admin details for logging before deletion
        $stmt = $pdo->prepare("SELECT a.full_name, u.username FROM admins a JOIN users u ON a.user_id = u.user_id WHERE a.admin_id = ?");
        $stmt->execute([$admin_id]);
        $admin_details = $stmt->fetch();
        
        // Delete from admins table first
        $stmt = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        
        // Delete from users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Log the deletion activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) VALUES (?, 'admin_deleted', ?, ?, ?, NOW())");
        $details = json_encode([
            'deleted_admin_id' => $admin_id,
            'deleted_user_id' => $user_id,
            'deleted_admin_name' => $admin_details['full_name'] ?? 'Unknown',
            'deleted_username' => $admin_details['username'] ?? 'Unknown'
        ]);
        $stmt->execute([
            $_SESSION['user_id'],
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to delete admin: ' . $e->getMessage()]);
    }
}

function getAdmin() {
    global $pdo;
    
    $admin_id = $_GET['admin_id'] ?? '';
    
    if (empty($admin_id)) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.admin_id, a.full_name, a.position, u.username, u.email, u.created_at
            FROM admins a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.admin_id = ?
        ");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo json_encode(['success' => true, 'data' => $admin]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get admin: ' . $e->getMessage()]);
    }
}

function listAdmins() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.admin_id, a.full_name, a.position, u.username, u.email, u.created_at
            FROM admins a
            JOIN users u ON a.user_id = u.user_id
            ORDER BY a.position DESC, a.full_name ASC
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $admins]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to list admins: ' . $e->getMessage()]);
    }
}
?>
