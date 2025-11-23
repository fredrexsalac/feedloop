<?php
/**
 * Update Form Question API Endpoint
 * Updates a specific question belonging to a custom form
 * Author: Cascade AI Assistant
 * Date: October 25, 2025
 */

session_start();
header('Content-Type: application/json');

require_once '../../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }

    if (!isset($input['question_id']) || !is_numeric($input['question_id'])) {
        throw new Exception('Invalid question ID provided');
    }

    $question_id = (int)$input['question_id'];
    $question_text = trim($input['question_text'] ?? '');
    $question_type = trim($input['question_type'] ?? '');

    if ($question_text === '' || $question_type === '') {
        throw new Exception('Question text and type are required');
    }

    $allowed_types = [
        'text', 'textarea', 'radio', 'checkbox', 'dropdown',
        'number', 'email', 'date', 'time', 'rating_stars',
        'rating_scale', 'slider'
    ];

    if (!in_array($question_type, $allowed_types, true)) {
        throw new Exception('Unsupported question type provided');
    }

    $user_id = $_SESSION['user_id'];

    // Verify permission
    $stmt = $pdo->prepare("
        SELECT fq.question_id, fq.form_id, cf.created_by, a.position
        FROM form_questions fq
        JOIN custom_forms cf ON fq.form_id = cf.form_id
        LEFT JOIN admins a ON cf.created_by = a.user_id
        WHERE fq.question_id = ?
    ");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        throw new Exception('Question not found');
    }

    // Only creator or super admin can update
    $currentAdminStmt = $pdo->prepare('SELECT position FROM admins WHERE user_id = ?');
    $currentAdminStmt->execute([$user_id]);
    $currentAdmin = $currentAdminStmt->fetch();
    $currentPosition = $currentAdmin['position'] ?? null;

    if ($question['created_by'] != $user_id && $currentPosition !== 'Super Admin') {
        throw new Exception('You do not have permission to update this question');
    }

    $is_required = !empty($input['is_required']) ? 1 : 0;

    $options = $input['options'] ?? null;
    $validation_rules = $input['validation_rules'] ?? null;
    $placeholder_text = $input['placeholder_text'] ?? null;

    $min_value = isset($input['min_value']) && $input['min_value'] !== '' ? $input['min_value'] : null;
    $max_value = isset($input['max_value']) && $input['max_value'] !== '' ? $input['max_value'] : null;
    $step_value = isset($input['step_value']) && $input['step_value'] !== '' ? $input['step_value'] : null;

    $stmt = $pdo->prepare("
        UPDATE form_questions
        SET question_text = ?,
            question_type = ?,
            is_required = ?,
            options = ?,
            validation_rules = ?,
            placeholder_text = ?,
            min_value = ?,
            max_value = ?,
            step_value = ?
        WHERE question_id = ?
    ");

    $stmt->execute([
        $question_text,
        $question_type,
        $is_required,
        $options,
        $validation_rules,
        $placeholder_text,
        $min_value,
        $max_value,
        $step_value,
        $question_id
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Question could not be updated');
    }

    // Log activity
    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp)
        VALUES (?, 'question_updated', ?, ?, ?, NOW())
    ");
    $logStmt->execute([
        $user_id,
        json_encode([
            'question_id' => $question_id,
            'form_id' => $question['form_id'],
            'question_type' => $question_type
        ]),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Question updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
