<?php
/**
 * Get Announcement Details API
 * Retrieves detailed information about a specific announcement/form
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

session_start();
require '../db.php';

header('Content-Type: application/json');

$form_id = $_GET['id'] ?? null;

if (!$form_id || !is_numeric($form_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid announcement ID']);
    exit();
}

try {
    // Get announcement details with admin info
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.username as admin_username,
            COALESCE(a.full_name, u.username) as admin_name,
            a.position as admin_position,
            cf.response_count,
            (SELECT COUNT(*) FROM form_questions fq WHERE fq.form_id = cf.form_id) as question_count
        FROM custom_forms cf
        JOIN users u ON cf.created_by = u.user_id
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE cf.form_id = ? 
        AND cf.is_active = 1 
        AND cf.visibility IN ('public', 'department')
    ");
    $stmt->execute([$form_id]);
    $announcement = $stmt->fetch();
    
    if (!$announcement) {
        echo json_encode(['success' => false, 'message' => 'Announcement not found or not accessible']);
        exit();
    }
    
    // Get form questions if it's a survey or feedback form
    $questions = [];
    if (in_array($announcement['form_type'], ['survey', 'feedback'])) {
        $stmt = $pdo->prepare("
            SELECT question_id, question_text, question_type, options, is_required
            FROM form_questions 
            WHERE form_id = ? 
            ORDER BY question_order ASC
        ");
        $stmt->execute([$form_id]);
        $questions = $stmt->fetchAll();
    }
    
    // Check if user can participate (for surveys/feedback forms)
    $can_participate = in_array($announcement['form_type'], ['survey', 'feedback']);
    
    // If max responses is set, check if limit is reached
    if ($can_participate && $announcement['max_responses'] > 0) {
        $can_participate = $announcement['response_count'] < $announcement['max_responses'];
    }
    
    // Generate HTML for modal
    $html = generateAnnouncementHTML($announcement, $questions, $can_participate);
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'can_participate' => $can_participate,
        'announcement' => $announcement
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading announcement: ' . $e->getMessage()]);
}

/**
 * Generate HTML for announcement modal
 */
function generateAnnouncementHTML($announcement, $questions, $can_participate) {
    $type_icons = [
        'announcement' => 'fas fa-bullhorn',
        'survey' => 'fas fa-poll',
        'feedback' => 'fas fa-comments',
        'event' => 'fas fa-calendar-alt'
    ];
    
    $icon = $type_icons[$announcement['form_type']] ?? 'fas fa-file-alt';
    
    $html = '
    <div class="announcement-detail">
        <div class="announcement-detail-header mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="announcement-type-badge me-3">
                    <i class="' . $icon . ' me-2"></i>
                    ' . ucfirst($announcement['form_type']) . '
                </div>
                <div class="announcement-date text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    ' . date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) . '
                </div>
            </div>
            
            <h3 class="announcement-detail-title mb-3">' . htmlspecialchars($announcement['title']) . '</h3>
            
            <div class="admin-info-detailed mb-3">
                <div class="d-flex align-items-center">
                    <div class="admin-avatar me-3">
                        <i class="fas fa-user-tie fa-2x text-primary"></i>
                    </div>
                    <div>
                        <strong>' . htmlspecialchars($announcement['admin_name']) . '</strong>';
    
    if ($announcement['admin_position']) {
        $html .= '<br><small class="text-muted">' . htmlspecialchars($announcement['admin_position']) . '</small>';
    }
    
    $html .= '
                    </div>
                </div>
            </div>
        </div>
        
        <div class="announcement-detail-content">';
    
    if ($announcement['description']) {
        $html .= '
            <div class="description-section mb-4">
                <h5><i class="fas fa-info-circle me-2 text-info"></i>Description</h5>
                <div class="description-content">
                    ' . nl2br(htmlspecialchars($announcement['description'])) . '
                </div>
            </div>';
    }
    
    // Show statistics
    $html .= '
        <div class="announcement-stats mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-item">
                        <i class="fas fa-users text-primary"></i>
                        <span class="stat-value">' . $announcement['response_count'] . '</span>
                        <span class="stat-label">Responses</span>
                    </div>
                </div>';
    
    if ($announcement['max_responses'] > 0) {
        $html .= '
                <div class="col-md-4">
                    <div class="stat-item">
                        <i class="fas fa-limit text-warning"></i>
                        <span class="stat-value">' . $announcement['max_responses'] . '</span>
                        <span class="stat-label">Max Responses</span>
                    </div>
                </div>';
    }
    
    if (!empty($questions)) {
        $html .= '
                <div class="col-md-4">
                    <div class="stat-item">
                        <i class="fas fa-question-circle text-success"></i>
                        <span class="stat-value">' . count($questions) . '</span>
                        <span class="stat-label">Questions</span>
                    </div>
                </div>';
    }
    
    $html .= '
            </div>
        </div>';
    
    // Show questions preview if it's a survey or feedback form
    if (!empty($questions)) {
        $html .= '
        <div class="questions-preview mb-4">
            <h5><i class="fas fa-list me-2 text-success"></i>Questions Preview</h5>
            <div class="questions-list">';
        
        foreach (array_slice($questions, 0, 3) as $index => $question) {
            $html .= '
                <div class="question-preview-item">
                    <div class="question-number">' . ($index + 1) . '</div>
                    <div class="question-content">
                        <div class="question-text">' . htmlspecialchars($question['question_text']) . '</div>
                        <div class="question-type">
                            <small class="text-muted">
                                <i class="fas fa-tag me-1"></i>' . ucfirst(str_replace('_', ' ', $question['question_type'])) . '
                                ' . ($question['is_required'] ? '<span class="text-danger">*</span>' : '') . '
                            </small>
                        </div>
                    </div>
                </div>';
        }
        
        if (count($questions) > 3) {
            $html .= '
                <div class="more-questions text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-ellipsis-h me-1"></i>
                        And ' . (count($questions) - 3) . ' more questions...
                    </small>
                </div>';
        }
        
        $html .= '
            </div>
        </div>';
    }
    
    // Show participation status
    if (in_array($announcement['form_type'], ['survey', 'feedback'])) {
        if ($can_participate) {
            $html .= '
            <div class="participation-info alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>You can participate in this ' . $announcement['form_type'] . '!</strong>
                <br><small>Click the "Participate" button to get started.</small>
            </div>';
        } else {
            $reason = $announcement['max_responses'] > 0 && $announcement['response_count'] >= $announcement['max_responses'] 
                ? 'Maximum responses reached' 
                : 'Participation not available';
                
            $html .= '
            <div class="participation-info alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>' . $reason . '</strong>
            </div>';
        }
    }
    
    $html .= '
        </div>
    </div>
    
    <style>
    .announcement-type-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .announcement-detail-title {
        color: #2c3e50;
        font-weight: 700;
    }
    
    .admin-info-detailed {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        border-left: 4px solid #0d6efd;
    }
    
    .description-content {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        line-height: 1.6;
    }
    
    .announcement-stats .row {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        margin: 0;
    }
    
    .stat-item {
        text-align: center;
        padding: 0.5rem;
    }
    
    .stat-item i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .stat-value {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
    }
    
    .stat-label {
        display: block;
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .questions-preview {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
    }
    
    .question-preview-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .question-number {
        background: #0d6efd;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .question-content {
        flex-grow: 1;
    }
    
    .question-text {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }
    
    .participation-info {
        border-radius: 10px;
        border: none;
        font-weight: 500;
    }
    </style>';
    
    return $html;
}
?>
