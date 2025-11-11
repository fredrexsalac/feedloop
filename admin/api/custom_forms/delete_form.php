<?php
/**
 * Delete Custom Form API Endpoint
 * Handles deletion of custom feedback forms and all associated data
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
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First, verify the form exists and belongs to the current user (or user is Super Admin)
    $stmt = $pdo->prepare("
        SELECT cf.form_id, cf.title, cf.created_by, a.position
        FROM custom_forms cf
        LEFT JOIN admins a ON a.user_id = ?
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$user_id, $form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check permissions - form creator or Super Admin can delete
    if ($form['created_by'] != $user_id && $form['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to delete this form');
    }
    
    // Get form details for logging
    $form_title = $form['title'];
    
    // Delete in proper order to maintain referential integrity
    
    // 1. Delete form answers (responses to individual questions)
    $stmt = $pdo->prepare("
        DELETE fa FROM form_answers fa
        INNER JOIN form_responses fr ON fa.response_id = fr.response_id
        WHERE fr.form_id = ?
    ");
    $stmt->execute([$form_id]);
    $deleted_answers = $stmt->rowCount();
    
    // 2. Delete form responses
    $stmt = $pdo->prepare("DELETE FROM form_responses WHERE form_id = ?");
    $stmt->execute([$form_id]);
    $deleted_responses = $stmt->rowCount();
    
    // 3. Delete form analytics
    $stmt = $pdo->prepare("DELETE FROM form_analytics WHERE form_id = ?");
    $stmt->execute([$form_id]);
    
    // 4. Delete form questions
    $stmt = $pdo->prepare("DELETE FROM form_questions WHERE form_id = ?");
    $stmt->execute([$form_id]);
    $deleted_questions = $stmt->rowCount();
    
    // 5. Get QR code path before deleting form
    $stmt = $pdo->prepare("SELECT qr_code_path FROM custom_forms WHERE form_id = ?");
    $stmt->execute([$form_id]);
    $qr_code_path = $stmt->fetchColumn();
    
    // 6. Finally, delete the form itself
    $stmt = $pdo->prepare("DELETE FROM custom_forms WHERE form_id = ?");
    $stmt->execute([$form_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Form could not be deleted');
    }
    
    // 7. Delete QR code file if it exists
    if ($qr_code_path && file_exists('../../../' . $qr_code_path)) {
        unlink('../../../' . $qr_code_path);
    }
    
    // Log the deletion activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) 
        VALUES (?, 'form_deleted', ?, ?, ?, NOW())
    ");
    $details = json_encode([
        'form_id' => $form_id,
        'form_title' => $form_title,
        'deleted_questions' => $deleted_questions,
        'deleted_responses' => $deleted_responses,
        'deleted_answers' => $deleted_answers
    ]);
    $stmt->execute([
        $user_id,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Form deleted successfully',
        'data' => [
            'form_id' => $form_id,
            'form_title' => $form_title,
            'deleted_questions' => $deleted_questions,
            'deleted_responses' => $deleted_responses,
            'deleted_answers' => $deleted_answers
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log the error
    error_log("Form deletion error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
