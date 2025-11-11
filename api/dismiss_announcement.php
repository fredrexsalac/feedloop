<?php
/**
 * Dismiss Announcement API
 * Allows logged-in users to dismiss announcements
 */

session_start();
require '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['frontend_logged_in']) || !$_SESSION['frontend_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['frontend_user_id'] ?? 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get form_id from POST data
$data = json_decode(file_get_contents('php://input'), true);
$form_id = $data['form_id'] ?? 0;

if ($form_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid form ID']);
    exit;
}

try {
    // Create table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_dismissed_announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        form_id INT NOT NULL,
        dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_form (user_id, form_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Insert dismissed announcement
    $stmt = $pdo->prepare("
        INSERT INTO user_dismissed_announcements (user_id, form_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE dismissed_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$user_id, $form_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement dismissed successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
