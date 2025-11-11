<?php
session_start();
require '../../db.php';

header('Content-Type: application/json');

// Check if user is logged in admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$feedback_id = $_GET['id'] ?? null;

if (!$feedback_id) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM feedback_submissions WHERE submission_id = ?");
    $stmt->execute([$feedback_id]);
    $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$feedback) {
        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        exit();
    }
    
    // Generate HTML for modal
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-user me-2"></i>Submitter Information</h6>
            <p><strong>Name:</strong> ' . htmlspecialchars($feedback['name'] ?? 'Anonymous') . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($feedback['email'] ?? 'Not provided') . '</p>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-info-circle me-2"></i>Submission Details</h6>
            <p><strong>Category:</strong> <span class="badge bg-info">' . htmlspecialchars($feedback['category'] ?? 'General') . '</span></p>
            <p><strong>Status:</strong> <span class="badge ' . (($feedback['status'] ?? 'pending') == 'resolved' ? 'bg-success' : 'bg-warning') . '">' . ucfirst($feedback['status'] ?? 'pending') . '</span></p>
            <p><strong>Submitted:</strong> ' . date('F j, Y \a\t g:i A', strtotime($feedback['created_at'])) . '</p>
        </div>
    </div>
    <hr>
    <h6><i class="fas fa-envelope me-2"></i>Subject</h6>
    <p class="fw-bold">' . htmlspecialchars($feedback['subject'] ?? 'No Subject') . '</p>
    
    <h6><i class="fas fa-comment me-2"></i>Message</h6>
    <div class="border rounded p-3 bg-light">
        ' . nl2br(htmlspecialchars($feedback['message'] ?? 'No message provided')) . '
    </div>';
    
    // Add admin response if exists
    if (!empty($feedback['admin_response'])) {
        $html .= '
        <hr>
        <h6><i class="fas fa-reply me-2"></i>Admin Response</h6>
        <div class="border rounded p-3 bg-success bg-opacity-10">
            ' . nl2br(htmlspecialchars($feedback['admin_response'])) . '
        </div>
        <small class="text-muted">
            <i class="fas fa-clock me-1"></i>Responded on: ' . date('F j, Y \a\t g:i A', strtotime($feedback['admin_response_date'])) . '
        </small>';
    }
    
    echo json_encode([
        'success' => true,
        'feedback' => $feedback,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
