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

if (!$feedback_id) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID is required']);
    exit();
}

try {
    // Get feedback details for logging before deletion
    $stmt = $pdo->prepare("SELECT subject, name FROM feedback_submissions WHERE submission_id = ?");
    $stmt->execute([$feedback_id]);
    $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$feedback) {
        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        exit();
    }
    
    // Delete the feedback
    $stmt = $pdo->prepare("DELETE FROM feedback_submissions WHERE submission_id = ?");
    $stmt->execute([$feedback_id]);
    
    if ($stmt->rowCount() > 0) {
        // Log the action
        try {
            require_once '../../includes/activity_logger.php';
            $subject = $feedback['subject'] ?? 'No Subject';
            $name = $feedback['name'] ?? 'Anonymous';
            logActivity($pdo, $_SESSION['user_id'], 'feedback_deleted', "Deleted feedback #$feedback_id: '$subject' from $name");
        } catch (Exception $e) {
            // Continue even if logging fails
        }
        
        echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
