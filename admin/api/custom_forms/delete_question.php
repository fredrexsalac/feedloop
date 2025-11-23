<?php
/**
 * Delete Form Question API Endpoint
 * Deletes a specific question from a custom form
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../../../db.php';

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
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
    
    if (!isset($input['question_id']) || !is_numeric($input['question_id'])) {
        throw new Exception('Invalid question ID provided');
    }
    
    $question_id = (int)$input['question_id'];
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // First, verify the question exists and user has permission to delete it
    $stmt = $pdo->prepare("
        SELECT fq.question_id, fq.question_text, fq.form_id, cf.title as form_title, cf.created_by, a.position
        FROM form_questions fq
        JOIN custom_forms cf ON fq.form_id = cf.form_id
        JOIN users u ON u.user_id = ?
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE fq.question_id = ?
    ");
    $stmt->execute([$user_id, $question_id]);
    $question = $stmt->fetch();
    
    if (!$question) {
        throw new Exception('Question not found');
    }
    
    // Check permissions - form creator or Super Admin can delete questions
    if ($question['created_by'] != $user_id && $question['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to delete this question');
    }
    
    // Delete any existing answers for this question first
    $stmt = $pdo->prepare("DELETE FROM form_answers WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $deleted_answers = $stmt->rowCount();
    
    // Delete the question
    $stmt = $pdo->prepare("DELETE FROM form_questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Question could not be deleted');
    }
    
    // Reorder remaining questions to fill the gap
    $stmt = $pdo->prepare("
        SELECT question_id, question_order 
        FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$question['form_id']]);
    $remaining_questions = $stmt->fetchAll();
    
    // Update question order
    $new_order = 1;
    foreach ($remaining_questions as $remaining_question) {
        $stmt = $pdo->prepare("UPDATE form_questions SET question_order = ? WHERE question_id = ?");
        $stmt->execute([$new_order, $remaining_question['question_id']]);
        $new_order++;
    }
    
    // Log the activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) 
        VALUES (?, 'question_deleted', ?, ?, ?, NOW())
    ");
    $details = json_encode([
        'question_id' => $question_id,
        'question_text' => $question['question_text'],
        'form_id' => $question['form_id'],
        'form_title' => $question['form_title'],
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
        'message' => 'Question deleted successfully',
        'data' => [
            'question_id' => $question_id,
            'question_text' => $question['question_text'],
            'form_id' => $question['form_id'],
            'deleted_answers' => $deleted_answers,
            'remaining_questions' => count($remaining_questions)
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log the error
    error_log("Question deletion error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
