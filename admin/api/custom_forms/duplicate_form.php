<?php
/**
 * Duplicate Custom Form API Endpoint
 * Creates a copy of an existing form with all its questions
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
    
    $original_form_id = (int)$input['form_id'];
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get original form details
    $stmt = $pdo->prepare("
        SELECT cf.*, u.position
        FROM custom_forms cf
        JOIN users u ON u.user_id = ?
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$user_id, $original_form_id]);
    $original_form = $stmt->fetch();
    
    if (!$original_form) {
        throw new Exception('Original form not found');
    }
    
    // Check permissions - form creator or Super Admin can duplicate
    if ($original_form['created_by'] != $user_id && $original_form['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to duplicate this form');
    }
    
    // Generate unique form code for the duplicate
    $new_form_code = 'FORM_' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Check if form code already exists (very unlikely but just in case)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_forms WHERE form_code = ?");
    $stmt->execute([$new_form_code]);
    if ($stmt->fetchColumn() > 0) {
        $new_form_code = 'FORM_' . strtoupper(substr(md5(uniqid() . time()), 0, 8));
    }
    
    // Create new form (duplicate)
    $new_title = $original_form['title'] . ' (Copy)';
    // Force HTTPS for shareable links
    $base_url = 'https://' . $_SERVER['HTTP_HOST'];
    $new_shareable_link = $base_url . '/feedloop/public/form/' . $new_form_code;
    
    $stmt = $pdo->prepare("
        INSERT INTO custom_forms (
            title, description, created_by, visibility, target_audience, department, 
            event_name, form_code, shareable_link, is_active, allow_anonymous, 
            require_login, max_responses, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $new_title,
        $original_form['description'],
        $user_id, // Set current user as creator of the duplicate
        $original_form['visibility'],
        $original_form['target_audience'],
        $original_form['department'],
        $original_form['event_name'],
        $new_form_code,
        $new_shareable_link,
        0, // Set as inactive by default
        $original_form['allow_anonymous'],
        $original_form['require_login'],
        $original_form['max_responses'],
        $original_form['expires_at']
    ]);
    
    $new_form_id = $pdo->lastInsertId();
    
    // Copy all questions from original form
    $stmt = $pdo->prepare("
        SELECT * FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$original_form_id]);
    $questions = $stmt->fetchAll();
    
    $question_count = 0;
    foreach ($questions as $question) {
        $stmt = $pdo->prepare("
            INSERT INTO form_questions (
                form_id, question_text, question_type, options, is_required, 
                question_order, validation_rules, placeholder_text
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $new_form_id,
            $question['question_text'],
            $question['question_type'],
            $question['options'],
            $question['is_required'],
            $question['question_order'],
            $question['validation_rules'] ?? null,
            $question['placeholder_text'] ?? null
        ]);
        
        $question_count++;
    }
    
    // Generate QR code for the new form (optional - you might want to implement this)
    // For now, we'll leave qr_code_path as NULL
    
    // Log the duplication activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) 
        VALUES (?, 'form_duplicated', ?, ?, ?, NOW())
    ");
    $details = json_encode([
        'original_form_id' => $original_form_id,
        'original_form_title' => $original_form['title'],
        'new_form_id' => $new_form_id,
        'new_form_title' => $new_title,
        'questions_copied' => $question_count
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
        'message' => 'Form duplicated successfully',
        'data' => [
            'original_form_id' => $original_form_id,
            'new_form_id' => $new_form_id,
            'new_form_title' => $new_title,
            'new_form_code' => $new_form_code,
            'questions_copied' => $question_count,
            'shareable_link' => $new_shareable_link
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log the error
    error_log("Form duplication error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
