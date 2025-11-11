<?php
session_start();
require '../db.php'; // Include database connection

// Check if user is logged in and is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['position'] !== 'Super Admin') {
    header("Location: ../../login/unified_login.php");
    exit();
}

// Get admin ID from URL
$admin_id = $_GET['id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, get the user_id from the admins table
    $stmt = $pdo->prepare("SELECT user_id FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    $user_id = $admin['user_id'];
    
    // Delete from admins table
    $stmt = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    
    // Delete from users table
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect back to manage admins page
    header("Location: manage_admins.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error deleting admin: " . $e->getMessage();
}
?>
