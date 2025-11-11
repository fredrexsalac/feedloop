<?php
session_start();
require '../../db.php';
require '../../includes/email_system.php';

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
$feedback_id = $input['feedback_id'] ?? null;
$response_message = trim($input['response_message'] ?? '');

if (!$feedback_id || !$response_message) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID and response message are required']);
    exit();
}

try {
    // Initialize email system
    $email_system = new EmailSystem($pdo);
    
    // Create notifications table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        feedback_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        admin_name VARCHAR(100) NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (feedback_id) REFERENCES feedback_submissions(id) ON DELETE CASCADE
    )");
    
    // Get feedback details and user info
    $stmt = $pdo->prepare("SELECT fs.*, fu.username, fu.full_name, fu.email 
                          FROM feedback_submissions fs
                          LEFT JOIN frontend_users fu ON fs.frontend_user_id = fu.id
                          WHERE fs.submission_id = ?");
    $stmt->execute([$feedback_id]);
    $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$feedback) {
        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        exit();
    }
    
    // Get admin name
    $stmt = $pdo->prepare("SELECT u.username, a.position 
                          FROM users u 
                          JOIN admins a ON u.user_id = a.user_id 
                          WHERE u.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_name = $admin ? $admin['username'] . ' (' . $admin['position'] . ')' : 'Administrator';
    
    // Add response to feedback_submissions table
    $stmt = $pdo->prepare("UPDATE feedback_submissions 
                          SET admin_response = ?, 
                              admin_response_date = NOW(), 
                              status = 'resolved',
                              updated_at = NOW()
                          WHERE submission_id = ?");
    $stmt->execute([$response_message, $feedback_id]);
    
    // Create notification for the user (if they have an account)
    if ($feedback['frontend_user_id']) {
        $notification_title = "Response to your feedback: " . substr($feedback['subject'], 0, 50);
        if (strlen($feedback['subject']) > 50) {
            $notification_title .= "...";
        }
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, feedback_id, title, message, admin_name) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $feedback['frontend_user_id'], 
            $feedback_id, 
            $notification_title, 
            $response_message, 
            $admin_name
        ]);
        
        // Send email notification if user has email
        if (!empty($feedback['email'])) {
            try {
                $email_system->sendFeedbackResponse(
                    $feedback['email'], 
                    $feedback['subject'], 
                    $response_message, 
                    $feedback_id
                );
            } catch (Exception $e) {
                // Continue even if email fails - notification is still created
                error_log("Email notification failed: " . $e->getMessage());
            }
        }
    }
    
    // Log the action
    try {
        require_once '../../includes/activity_logger.php';
        logActivity($pdo, $_SESSION['user_id'], 'feedback_response', "Responded to feedback #$feedback_id");
    } catch (Exception $e) {
        // Continue even if logging fails
    }
    
    echo json_encode(['success' => true, 'message' => 'Response sent successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
