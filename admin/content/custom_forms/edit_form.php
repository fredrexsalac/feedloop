<?php
/**
 * Edit Custom Form
 * Interface for editing existing custom forms and their questions
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

session_start();

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../admin_login.php');
    exit();
}

// Include database connection
require_once '../../../db.php';

// Get form ID
$form_id = $_GET['form_id'] ?? null;

if (!$form_id || !is_numeric($form_id)) {
    header('Location: ../../../admin/super_admin/super_admin_dashboard.php?page=custom_forms');
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get form details and verify permissions
    $stmt = $pdo->prepare("
        SELECT cf.*, a.position,
               COALESCE(a.full_name, u.username) as creator_name
        FROM custom_forms cf
        JOIN users u ON cf.created_by = u.user_id
        LEFT JOIN admins a ON u.user_id = a.user_id
        WHERE cf.form_id = ?
    ");
    $stmt->execute([$form_id]);
    $form = $stmt->fetch();
    
    if (!$form) {
        throw new Exception('Form not found');
    }
    
    // Check permissions
    $current_user_stmt = $pdo->prepare("SELECT position FROM users u LEFT JOIN admins a ON u.user_id = a.user_id WHERE u.user_id = ?");
    $current_user_stmt->execute([$user_id]);
    $current_user = $current_user_stmt->fetch();
    
    if ($form['created_by'] != $user_id && $current_user['position'] !== 'Super Admin') {
        throw new Exception('You do not have permission to edit this form');
    }
    
    // Get form questions
    $stmt = $pdo->prepare("
        SELECT * FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$form_id]);
    $questions = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Form - <?php echo htmlspecialchars($form['title'] ?? 'Unknown Form'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="../../../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .form-builder {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .question-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .question-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .question-type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .drag-handle {
            cursor: move;
            color: #6c757d;
        }
        .drag-handle:hover {
            color: #007bff;
        }
        .form-preview {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="form-builder">
    <div class="container-fluid py-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <a href="../../../admin/super_admin/super_admin_dashboard.php?page=custom_forms" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Forms
            </a>
        <?php else: ?>
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1">
                                <i class="fas fa-edit me-2 text-primary"></i>
                                Edit Form
                            </h1>
                            <h2 class="h5 text-muted mb-0"><?php echo htmlspecialchars($form['title']); ?></h2>
                            <small class="text-muted">Created by <?php echo htmlspecialchars($form['creator_name']); ?></small>
                        </div>
                        <div>
                            <a href="../../../admin/super_admin/super_admin_dashboard.php?page=custom_forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Forms
                            </a>
                            <button class="btn btn-success" onclick="saveForm()">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <button class="btn btn-info" onclick="previewForm()">
                                <i class="fas fa-eye me-2"></i>Preview
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Form Settings Panel -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cog me-2"></i>
                                Form Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="formSettingsForm">
                                <input type="hidden" id="formId" value="<?php echo $form_id; ?>">
                                
                                <div class="mb-3">
                                    <label for="formTitle" class="form-label">Form Title</label>
                                    <input type="text" class="form-control" id="formTitle" value="<?php echo htmlspecialchars($form['title']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="formDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="formDescription" rows="3"><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="formVisibility" class="form-label">Visibility</label>
                                    <select class="form-select" id="formVisibility">
                                        <option value="public" <?php echo $form['visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                                        <option value="department" <?php echo $form['visibility'] === 'department' ? 'selected' : ''; ?>>Department Only</option>
                                        <option value="private" <?php echo $form['visibility'] === 'private' ? 'selected' : ''; ?>>Private</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="maxResponses" class="form-label">Max Responses</label>
                                    <input type="number" class="form-control" id="maxResponses" value="<?php echo $form['max_responses'] ?? ''; ?>" min="1">
                                    <small class="form-text text-muted">Leave empty for unlimited responses</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="expiresAt" class="form-label">Expires At</label>
                                    <input type="datetime-local" class="form-control" id="expiresAt" value="<?php echo $form['expires_at'] ? date('Y-m-d\TH:i', strtotime($form['expires_at'])) : ''; ?>">
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="allowAnonymous" <?php echo $form['allow_anonymous'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allowAnonymous">
                                        Allow Anonymous Responses
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requireLogin" <?php echo $form['require_login'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="requireLogin">
                                        Require Login
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="isActive" <?php echo $form['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isActive">
                                        Form is Active
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Add Question Panel -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>
                                Add Question
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="questionType" class="form-label">Question Type</label>
                                <select class="form-select" id="questionType">
                                    <option value="text">Text Input</option>
                                    <option value="textarea">Long Text</option>
                                    <option value="radio">Multiple Choice (Single)</option>
                                    <option value="checkbox">Multiple Choice (Multiple)</option>
                                    <option value="select">Dropdown</option>
                                    <option value="number">Number</option>
                                    <option value="email">Email</option>
                                    <option value="date">Date</option>
                                    <option value="rating">Rating Scale</option>
                                </select>
                            </div>
                            
                            <button class="btn btn-primary w-100" onclick="addQuestion()">
                                <i class="fas fa-plus me-2"></i>Add Question
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Questions List -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Form Questions (<?php echo count($questions); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="questionsList">
                                <?php if (empty($questions)): ?>
                                    <div class="text-center py-5" id="noQuestionsMessage">
                                        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Questions Yet</h5>
                                        <p class="text-muted">Add questions using the panel on the left.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($questions as $index => $question): ?>
                                        <div class="question-card card" data-question-id="<?php echo $question['question_id']; ?>">
                                            <div class="card-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                        <span class="badge question-type-badge bg-primary me-2">
                                                            <?php echo strtoupper($question['question_type']); ?>
                                                        </span>
                                                        <span class="fw-bold">Question <?php echo $index + 1; ?></span>
                                                        <?php if ($question['is_required']): ?>
                                                            <span class="badge bg-danger ms-2">Required</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editQuestion(<?php echo $question['question_id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteQuestion(<?php echo $question['question_id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-2"><strong><?php echo htmlspecialchars($question['question_text']); ?></strong></p>
                                                <?php if ($question['options']): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">Options:</small>
                                                        <ul class="list-unstyled ms-3">
                                                            <?php 
                                                            $options = json_decode($question['options'], true);
                                                            if ($options) {
                                                                foreach ($options as $option) {
                                                                    echo '<li><small>• ' . htmlspecialchars($option) . '</small></li>';
                                                                }
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($question['placeholder_text']): ?>
                                                    <small class="text-muted">Placeholder: <?php echo htmlspecialchars($question['placeholder_text']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="../../../assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let questionCounter = <?php echo count($questions); ?>;
        
        function saveForm() {
            const formData = {
                form_id: document.getElementById('formId').value,
                title: document.getElementById('formTitle').value,
                description: document.getElementById('formDescription').value,
                visibility: document.getElementById('formVisibility').value,
                max_responses: document.getElementById('maxResponses').value || null,
                expires_at: document.getElementById('expiresAt').value || null,
                allow_anonymous: document.getElementById('allowAnonymous').checked,
                require_login: document.getElementById('requireLogin').checked,
                is_active: document.getElementById('isActive').checked
            };
            
            fetch('../../api/custom_forms/update_form.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Form updated successfully!');
                } else {
                    showNotification('error', data.message || 'Failed to update form');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred while updating the form');
            });
        }
        
        function addQuestion() {
            const questionType = document.getElementById('questionType').value;
            // This would open a modal to add a new question
            // For now, we'll show a placeholder
            showNotification('info', 'Question editor modal will be implemented');
        }
        
        function editQuestion(questionId) {
            // This would open a modal to edit the question
            showNotification('info', 'Question editor modal will be implemented');
        }
        
        function deleteQuestion(questionId) {
            if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                fetch('../../api/custom_forms/delete_question.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ question_id: questionId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', 'Question deleted successfully!');
                        // Remove the question card from the UI
                        const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
                        if (questionCard) {
                            questionCard.remove();
                        }
                        
                        // Check if no questions remain
                        const remainingQuestions = document.querySelectorAll('.question-card');
                        if (remainingQuestions.length === 0) {
                            document.getElementById('questionsList').innerHTML = `
                                <div class="text-center py-5" id="noQuestionsMessage">
                                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Questions Yet</h5>
                                    <p class="text-muted">Add questions using the panel on the left.</p>
                                </div>
                            `;
                        }
                    } else {
                        showNotification('error', data.message || 'Failed to delete question');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred while deleting the question');
                });
            }
        }
        
        function previewForm() {
            const formCode = '<?php echo $form['form_code']; ?>';
            const previewUrl = `../../../public/form/?code=${formCode}`;
            window.open(previewUrl, '_blank');
        }
        
        function showNotification(type, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
