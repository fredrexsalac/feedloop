<?php
/**
 * Create Custom Form API Endpoint
 * Handles creation of new custom feedback forms
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
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
    if (empty($input['title'])) {
        throw new Exception('Form title is required');
    }
    
    if (empty($input['questions']) || !is_array($input['questions'])) {
        throw new Exception('At least one question is required');
    }
    
    // Validate questions
    foreach ($input['questions'] as $question) {
        if (empty($question['question_text'])) {
            throw new Exception('All questions must have text');
        }
        
        if (empty($question['question_type'])) {
            throw new Exception('All questions must have a type');
        }
        
        // Validate multiple choice questions have options
        if (in_array($question['question_type'], ['radio', 'checkbox', 'dropdown'])) {
            $optionsObj = json_decode($question['options'] ?? '{}', true) ?: [];
            $list = isset($optionsObj['options']) && is_array($optionsObj['options']) ? array_values(array_filter($optionsObj['options'], fn($v)=>trim((string)$v)!=='')) : [];
            $allowOther = !empty($optionsObj['allow_other']);
            // Accept if 2+ explicit options OR at least 1 option with 'Other' enabled
            if (!(count($list) >= 2 || ($allowOther && count($list) >= 1))) {
                throw new Exception('Multiple choice questions must include at least two options, or one option plus "Other" enabled');
            }
            // Normalize placeholder
            if ($allowOther && empty($optionsObj['other_placeholder'])) {
                $optionsObj['other_placeholder'] = 'Please specify...';
                $question['options'] = json_encode($optionsObj);
            }
        }
    }
    
    // Generate unique form code
    $form_code = generateUniqueFormCode($pdo);
    
    // Generate shareable link (force HTTPS)
    $base_url = 'https://' . $_SERVER['HTTP_HOST'];
    $shareable_link = $base_url . '/feedloop/public/form/' . $form_code;
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert form
    $stmt = $pdo->prepare("
        INSERT INTO custom_forms (
            title, description, created_by, visibility, target_audience, 
            department, event_name, form_code, shareable_link, 
            allow_anonymous, require_login, max_responses, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $department = null;
    $event_name = null;
    $target_audience = $input['target_audience'] ?? null;
    
    // Set department or event name based on visibility
    if ($input['visibility'] === 'department') {
        $department = $target_audience;
    } elseif ($input['visibility'] === 'event') {
        $event_name = $target_audience;
    }
    
    $expires_at = null;
    if (!empty($input['expires_at'])) {
        $expires_at = date('Y-m-d H:i:s', strtotime($input['expires_at']));
    }
    
    $max_responses = !empty($input['max_responses']) ? (int)$input['max_responses'] : null;
    
    $stmt->execute([
        $input['title'],
        $input['description'] ?? null,
        $_SESSION['user_id'],
        $input['visibility'] ?? 'public',
        $target_audience,
        $department,
        $event_name,
        $form_code,
        $shareable_link,
        $input['allow_anonymous'] ?? true,
        !($input['allow_anonymous'] ?? true), // require_login is opposite of allow_anonymous
        $max_responses,
        $expires_at
    ]);
    
    $form_id = $pdo->lastInsertId();
    
    // Insert questions
    $stmt = $pdo->prepare("
        INSERT INTO form_questions (
            form_id, question_text, question_type, is_required, question_order,
            options, validation_rules, placeholder_text, min_value, max_value, step_value
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($input['questions'] as $question) {
        $stmt->execute([
            $form_id,
            $question['question_text'],
            $question['question_type'],
            $question['is_required'] ?? false,
            $question['question_order'],
            $question['options'],
            $question['validation_rules'],
            $question['placeholder_text'],
            $question['min_value'],
            $question['max_value'],
            $question['step_value']
        ]);
    }
    
    // Initialize analytics
    $stmt = $pdo->prepare("
        INSERT INTO form_analytics (form_id, total_views, total_starts, total_completions)
        VALUES (?, 0, 0, 0)
    ");
    $stmt->execute([$form_id]);
    
    // Generate QR code (placeholder for now)
    $qr_code_path = generateQRCode($form_code, $shareable_link);
    
    // Update form with QR code path
    if ($qr_code_path) {
        $stmt = $pdo->prepare("UPDATE custom_forms SET qr_code_path = ? WHERE form_id = ?");
        $stmt->execute([$qr_code_path, $form_id]);
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'form_created', ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        "Created custom form: {$input['title']} (ID: {$form_id})",
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Form created successfully',
        'data' => [
            'form_id' => $form_id,
            'form_code' => $form_code,
            'shareable_link' => $shareable_link,
            'qr_code_path' => $qr_code_path
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate unique form code
 */
function generateUniqueFormCode($pdo) {
    do {
        // Generate 8-character alphanumeric code
        $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        
        // Check if code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_forms WHERE form_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn() > 0;
        
    } while ($exists);
    
    return $code;
}

/**
 * Generate QR code for the form
 * This is a placeholder - you would integrate with a QR code library
 */
function generateQRCode($form_code, $shareable_link) {
    try {
        // Create QR codes directory if it doesn't exist
        $qr_dir = '../../../assets/img/qr_codes/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        
        // For now, we'll create a placeholder file
        // In a real implementation, you would use a QR code library like:
        // - PHP QR Code library
        // - Google Charts API
        // - QR Server API
        
        $qr_filename = $form_code . '.png';
        $qr_path = $qr_dir . $qr_filename;
        
        // Create a simple placeholder image (1x1 transparent PNG)
        $placeholder_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        file_put_contents($qr_path, $placeholder_data);
        
        return 'assets/img/qr_codes/' . $qr_filename;
        
    } catch (Exception $e) {
        // QR code generation failed, but don't fail the entire form creation
        error_log("QR code generation failed: " . $e->getMessage());
        return null;
    }
}
?>
