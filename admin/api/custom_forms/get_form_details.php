<?php
/**
 * Get Custom Form Details API Endpoint
 * Retrieves detailed information about a specific form for sharing/editing
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

// Get form ID from query parameter
$form_id = $_GET['form_id'] ?? null;

if (!$form_id || !is_numeric($form_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid form ID provided']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get form details with creator information
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.username as creator_username,
            COALESCE(a.full_name, u.username) as creator_name,
            a.position as creator_position,
            (SELECT COUNT(*) FROM form_questions fq WHERE fq.form_id = cf.form_id) as question_count
        FROM custom_forms cf
        JOIN users u ON cf.created_by = u.user_id
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check permissions - form creator or Super Admin can view details
    $current_user_stmt = $pdo->prepare("SELECT position FROM users u LEFT JOIN admins a ON u.user_id = a.user_id WHERE u.user_id = ?");
    $current_user_stmt->execute([$user_id]);
    $current_user = $current_user_stmt->fetch();
    
    if ($form['created_by'] != $user_id && $current_user['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to view this form');
    }
    
    // Calculate completion rate if there are responses
    $completion_rate = 0;
    if ($form['response_count'] > 0 && $form['question_count'] > 0) {
        // This is a simplified calculation - you might want to implement more sophisticated logic
        $completion_rate = min(100, ($form['response_count'] / max(1, $form['max_responses'] ?: 100)) * 100);
    }
    
    // Format the response
    $response_data = [
        'form_id' => $form['form_id'],
        'title' => $form['title'],
        'description' => $form['description'],
        'form_code' => $form['form_code'],
        'shareable_link' => $form['shareable_link'],
        'qr_code_path' => $form['qr_code_path'],
        'visibility' => $form['visibility'],
        'is_active' => $form['is_active'],
        'allow_anonymous' => $form['allow_anonymous'],
        'require_login' => $form['require_login'],
        'max_responses' => $form['max_responses'],
        'response_count' => $form['response_count'],
        'question_count' => $form['question_count'],
        'completion_rate' => round($completion_rate, 1),
        'expires_at' => $form['expires_at'],
        'created_at' => $form['created_at'],
        'updated_at' => $form['updated_at'],
        'creator' => [
            'username' => $form['creator_username'],
            'name' => $form['creator_name'],
            'position' => $form['creator_position']
        ]
    ];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'form' => $response_data
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Get form details error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
