<?php
/**
 * Update Custom Form API Endpoint
 * Updates form settings and basic information
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
    
    $form_id = (int)$input['form_id'];
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($input['title'])) {
        throw new Exception('Form title is required');
    }
    
    // First, verify the form exists and user has permission to edit it
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
    
    // Check permissions - form creator or Super Admin can edit
    if ($form['created_by'] != $user_id && $form['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to edit this form');
    }
    
    // Prepare update data
    $title = trim($input['title']);
    $description = trim($input['description'] ?? '');
    $visibility = $input['visibility'] ?? 'public';
    $max_responses = !empty($input['max_responses']) ? (int)$input['max_responses'] : null;
    $expires_at = !empty($input['expires_at']) ? $input['expires_at'] : null;
    $allow_anonymous = isset($input['allow_anonymous']) ? (bool)$input['allow_anonymous'] : false;
    $require_login = isset($input['require_login']) ? (bool)$input['require_login'] : false;
    $is_active = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    // Validate visibility options
    $valid_visibility = ['public', 'department', 'private'];
    if (!in_array($visibility, $valid_visibility)) {
        throw new Exception('Invalid visibility option');
    }
    
    // Validate expires_at format if provided
    if ($expires_at) {
        $expires_timestamp = strtotime($expires_at);
        if ($expires_timestamp === false) {
            throw new Exception('Invalid expiration date format');
        }
        $expires_at = date('Y-m-d H:i:s', $expires_timestamp);
    }
    
    // Update the form
    $stmt = $pdo->prepare("
        UPDATE custom_forms 
        SET title = ?, 
            description = ?, 
            visibility = ?, 
            max_responses = ?, 
            expires_at = ?, 
            allow_anonymous = ?, 
            require_login = ?, 
            is_active = ?, 
            updated_at = NOW()
        WHERE form_id = ?
    ");
    
    $stmt->execute([
        $title,
        $description,
        $visibility,
        $max_responses,
        $expires_at,
        $allow_anonymous ? 1 : 0,
        $require_login ? 1 : 0,
        $is_active ? 1 : 0,
        $form_id
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes were made to the form');
    }
    
    // Log the activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) 
        VALUES (?, 'form_updated', ?, ?, ?, NOW())
    ");
    $details = json_encode([
        'form_id' => $form_id,
        'form_title' => $title,
        'changes' => [
            'title' => $title !== $form['title'],
            'visibility' => $visibility,
            'is_active' => $is_active
        ]
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
        'message' => 'Form updated successfully',
        'data' => [
            'form_id' => $form_id,
            'title' => $title,
            'visibility' => $visibility,
            'is_active' => $is_active,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Form update error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
