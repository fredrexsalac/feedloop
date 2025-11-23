<?php
/**
 * Create Custom Form Question API Endpoint
 * Handles creation of new questions for custom forms
 * Author: Cascade AI Assistant
 * Date: November 23, 2025
 */

// Set session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Include database connection
require_once '../../../db.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['form_id']) || !is_numeric($input['form_id'])) {
        throw new Exception('Invalid form ID provided');
    }
    
    if (empty($input['question_text'])) {
        throw new Exception('Question text is required');
    }
    
    if (empty($input['question_type'])) {
        throw new Exception('Question type is required');
    }
    
    $form_id = (int)$input['form_id'];
    $question_text = $input['question_text'];
    $question_type = $input['question_type'];
    $is_required = isset($input['is_required']) ? (bool)$input['is_required'] : false;
    $options = $input['options'] ?? null;
    $validation_rules = $input['validation_rules'] ?? null;
    $placeholder_text = $input['placeholder_text'] ?? null;
    $min_value = $input['min_value'] ?? null;
    $max_value = $input['max_value'] ?? null;
    $step_value = $input['step_value'] ?? null;
    
    // Verify form exists and user has permission
    $stmt = $pdo->prepare("SELECT created_by FROM custom_forms WHERE form_id = ?");
    $stmt->execute([$form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check if user is the creator
    $user_id = $_SESSION['user_id'];
    if ($form['created_by'] != $user_id) {
        throw new Exception('You do not have permission to add questions to this form');
    }
    
    // Get the next question order
    $stmt = $pdo->prepare("SELECT MAX(question_order) as max_order FROM form_questions WHERE form_id = ?");
    $stmt->execute([$form_id]);
    $result = $stmt->fetch();
    $next_order = ($result['max_order'] ?? 0) + 1;
    
    // Insert the new question
    $stmt = $pdo->prepare("
        INSERT INTO form_questions (
            form_id, question_text, question_type, is_required, question_order,
            options, validation_rules, placeholder_text, min_value, max_value, step_value
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $form_id,
        $question_text,
        $question_type,
        $is_required ? 1 : 0,
        $next_order,
        $options,
        $validation_rules,
        $placeholder_text,
        $min_value,
        $max_value,
        $step_value
    ]);
    
    $question_id = $pdo->lastInsertId();
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'question_created', ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        "Created question for form ID: {$form_id}",
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Return success response with the created question data
    echo json_encode([
        'success' => true,
        'message' => 'Question created successfully',
        'data' => [
            'question_id' => $question_id,
            'form_id' => $form_id,
            'question_text' => $question_text,
            'question_type' => $question_type,
            'is_required' => $is_required,
            'options' => $options,
            'validation_rules' => $validation_rules,
            'placeholder_text' => $placeholder_text,
            'min_value' => $min_value,
            'max_value' => $max_value,
            'step_value' => $step_value,
            'question_order' => $next_order
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
