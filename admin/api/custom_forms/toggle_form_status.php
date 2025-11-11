<?php
/**
 * Toggle Custom Form Status API Endpoint
 * Activates or deactivates custom feedback forms
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../../../db.php';

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['form_id']) || !is_numeric($input['form_id'])) {
        throw new Exception('Invalid form ID provided');
    }
    
    if (!isset($input['is_active'])) {
        throw new Exception('Status parameter is required');
    }
    
    $form_id = (int)$input['form_id'];
    $is_active = $input['is_active'] ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    // First, verify the form exists and belongs to the current user (or user is Super Admin)
    $stmt = $pdo->prepare("
        SELECT cf.form_id, cf.title, cf.created_by, u.position
        FROM custom_forms cf
        JOIN users u ON u.user_id = ?
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$user_id, $form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check permissions - form creator or Super Admin can toggle status
    if ($form['created_by'] != $user_id && $form['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to modify this form');
    }
    
    // Update form status
    $stmt = $pdo->prepare("UPDATE custom_forms SET is_active = ?, updated_at = NOW() WHERE form_id = ?");
    $stmt->execute([$is_active, $form_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Form status could not be updated');
    }
    
    // Log the activity
    $action = $is_active ? 'activated' : 'deactivated';
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) 
        VALUES (?, 'form_status_changed', ?, ?, ?, NOW())
    ");
    $details = json_encode([
        'form_id' => $form_id,
        'form_title' => $form['title'],
        'new_status' => $is_active ? 'active' : 'inactive',
        'action' => $action
    ]);
    $stmt->execute([
        $user_id,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Form {$action} successfully",
        'data' => [
            'form_id' => $form_id,
            'form_title' => $form['title'],
            'is_active' => $is_active,
            'status' => $is_active ? 'active' : 'inactive'
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Form status toggle error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
