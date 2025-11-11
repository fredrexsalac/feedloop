<?php
session_start();
require '../../db.php';

header('Content-Type: application/json');

// Check if user is logged in admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$feedback_id = $input['id'] ?? null;
$status = $input['status'] ?? null;

if (!$feedback_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID and status are required']);
    exit();
}

if (!in_array($status, ['pending', 'resolved'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE feedback_submissions SET status = ?, updated_at = NOW() WHERE submission_id = ?");
    $stmt->execute([$status, $feedback_id]);
    
    if ($stmt->rowCount() > 0) {
        // Log the action
        try {
            require_once '../../includes/activity_logger.php';
            logActivity($pdo, $_SESSION['user_id'], 'feedback_status_update', "Updated feedback #$feedback_id status to $status");
        } catch (Exception $e) {
            // Continue even if logging fails
        }
        
        echo json_encode(['success' => true, 'message' => 'Feedback status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Feedback not found or no changes made']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
