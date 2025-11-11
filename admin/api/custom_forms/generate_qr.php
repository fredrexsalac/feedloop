<?php
/**
 * QR Code Generation API
 * Generates QR codes for custom forms using QR Server API
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
    // Get form ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $form_id = $input['form_id'] ?? null;
    
    if (!$form_id) {
        throw new Exception('Form ID is required');
    }
    
    // Get form details
    $stmt = $pdo->prepare("SELECT * FROM custom_forms WHERE form_id = ? AND created_by = ?");
    $stmt->execute([$form_id, $_SESSION['user_id']]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found or access denied');
    }
    
    // Generate QR code
    $qr_code_path = generateQRCodeForForm($form['form_code'], $form['shareable_link']);
    
    if ($qr_code_path) {
        // Update form with QR code path
        $stmt = $pdo->prepare("UPDATE custom_forms SET qr_code_path = ? WHERE form_id = ?");
        $stmt->execute([$qr_code_path, $form_id]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, 'qr_code_generated', ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            "Generated QR code for form: {$form['title']} (ID: {$form_id})",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code_path' => $qr_code_path,
                'qr_code_url' => getBaseUrl() . '/' . $qr_code_path
            ]
        ]);
    } else {
        throw new Exception('Failed to generate QR code');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate QR code for form using QR Server API
 */
function generateQRCodeForForm($form_code, $shareable_link) {
    try {
        // Create QR codes directory if it doesn't exist
        $qr_dir = '../../../assets/img/qr_codes/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        
        $qr_filename = $form_code . '.png';
        $qr_path = $qr_dir . $qr_filename;
        
        // Use QR Server API to generate QR code
        $qr_api_url = 'https://api.qrserver.com/v1/create-qr-code/';
        $qr_params = http_build_query([
            'size' => '300x300',
            'data' => $shareable_link,
            'format' => 'png',
            'bgcolor' => 'FFFFFF',
            'color' => '000000',
            'qzone' => '2',
            'margin' => '10'
        ]);
        
        $qr_url = $qr_api_url . '?' . $qr_params;
        
        // Download QR code image
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'FeedLoop QR Generator'
            ]
        ]);
        
        $qr_data = file_get_contents($qr_url, false, $context);
        
        if ($qr_data === false) {
            throw new Exception('Failed to download QR code from API');
        }
        
        // Save QR code to file
        if (file_put_contents($qr_path, $qr_data) === false) {
            throw new Exception('Failed to save QR code file');
        }
        
        return 'assets/img/qr_codes/' . $qr_filename;
        
    } catch (Exception $e) {
        error_log("QR code generation failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get base URL for the application
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
    return $protocol . '://' . $host . $path;
}
?>
