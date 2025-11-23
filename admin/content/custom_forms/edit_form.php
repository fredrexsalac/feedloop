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
                                        <div 
                                            class="question-card card"
                                            data-question-id="<?php echo $question['question_id']; ?>"
                                            data-question-type="<?php echo htmlspecialchars($question['question_type']); ?>"
                                            data-question-required="<?php echo (int)$question['is_required']; ?>"
                                            data-question-text="<?php echo htmlspecialchars($question['question_text'], ENT_QUOTES); ?>"
                                            data-question-options="<?php echo htmlspecialchars($question['options'] ?? '', ENT_QUOTES); ?>"
                                            data-question-placeholder="<?php echo htmlspecialchars($question['placeholder_text'] ?? '', ENT_QUOTES); ?>"
                                            data-question-validation="<?php echo htmlspecialchars($question['validation_rules'] ?? '', ENT_QUOTES); ?>"
                                            data-question-min="<?php echo htmlspecialchars($question['min_value'] ?? '', ENT_QUOTES); ?>"
                                            data-question-max="<?php echo htmlspecialchars($question['max_value'] ?? '', ENT_QUOTES); ?>"
                                            data-question-step="<?php echo htmlspecialchars($question['step_value'] ?? '', ENT_QUOTES); ?>"
                                        >
                                            <div class="card-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-grip-vertical drag-handle me-2"></i>
                                                        <span class="badge question-type-badge bg-primary me-2 question-type-label">
                                                            <?php echo strtoupper($question['question_type']); ?>
                                                        </span>
                                                        <span class="fw-bold">Question <?php echo $index + 1; ?></span>
                                                        <span class="badge bg-danger ms-2 required-indicator" style="<?php echo $question['is_required'] ? '' : 'display:none;'; ?>">Required</span>
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
                                                <p class="mb-2 question-text-preview"><strong><?php echo htmlspecialchars($question['question_text']); ?></strong></p>
                                                <div class="question-options-preview">
                                                    <?php if ($question['options']): ?>
                                                        <div class="mt-2">
                                                            <small class="text-muted">Options:</small>
                                                            <ul class="list-unstyled ms-3">
                                                                <?php 
                                                                $options = json_decode($question['options'], true);
                                                                if (is_array($options)) {
                                                                    $optionList = $options['options'] ?? $options;
                                                                    if (is_array($optionList)) {
                                                                        foreach ($optionList as $option) {
                                                                            echo '<li><small>• ' . htmlspecialchars($option) . '</small></li>';
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="question-meta-preview">
                                                    <?php if ($question['placeholder_text']): ?>
                                                        <small class="text-muted d-block">Placeholder: <span class="placeholder-preview"><?php echo htmlspecialchars($question['placeholder_text']); ?></span></small>
                                                    <?php else: ?>
                                                        <small class="text-muted d-block placeholder-preview" style="display:none;"></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Editor Modal -->
            <div class="modal fade" id="questionEditorModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-question-circle me-2"></i>Edit Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="questionEditorForm">
                                <input type="hidden" id="editQuestionId">
                                <div class="mb-3">
                                    <label for="questionModalText" class="form-label">Question Text</label>
                                    <input type="text" class="form-control" id="questionModalText" required>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="questionModalType" class="form-label">Question Type</label>
                                        <select class="form-select" id="questionModalType">
                                            <option value="text">Short Text</option>
                                            <option value="textarea">Long Text</option>
                                            <option value="radio">Multiple Choice (Single)</option>
                                            <option value="checkbox">Multiple Choice (Multiple)</option>
                                            <option value="dropdown">Dropdown</option>
                                            <option value="number">Number</option>
                                            <option value="email">Email</option>
                                            <option value="date">Date</option>
                                            <option value="time">Time</option>
                                            <option value="rating_stars">Star Rating</option>
                                            <option value="rating_scale">Rating Scale</option>
                                            <option value="slider">Slider</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="questionModalRequired">
                                            <label class="form-check-label" for="questionModalRequired">Required</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="textOptionsGroup" class="mt-4 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Placeholder Text</label>
                                            <input type="text" class="form-control" id="questionModalPlaceholder" placeholder="Enter placeholder text">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Max Characters</label>
                                            <input type="number" class="form-control" id="questionModalMaxLength" min="1" placeholder="Optional">
                                        </div>
                                    </div>
                                </div>

                                <div id="choiceOptionsGroup" class="mt-4 d-none">
                                    <label class="form-label">Options <small class="text-muted">(one per line)</small></label>
                                    <textarea class="form-control" id="questionModalChoiceOptions" rows="4" placeholder="Option 1&#10;Option 2"></textarea>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="questionModalAllowOther">
                                        <label class="form-check-label" for="questionModalAllowOther">Include "Other" option</label>
                                    </div>
                                    <div class="mt-2" id="questionModalOtherPlaceholderGroup" style="display:none;">
                                        <label class="form-label">"Other" Placeholder</label>
                                        <input type="text" class="form-control" id="questionModalOtherPlaceholder" placeholder="Please specify...">
                                    </div>
                                </div>

                                <div id="rangeOptionsGroup" class="mt-4 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Minimum Value</label>
                                            <input type="number" class="form-control" id="questionModalMinValue" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Maximum Value</label>
                                            <input type="number" class="form-control" id="questionModalMaxValue" value="10">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Step</label>
                                            <input type="number" class="form-control" id="questionModalStepValue" value="1">
                                        </div>
                                    </div>
                                </div>

                                <div id="ratingOptionsGroup" class="mt-4 d-none">
                                    <label class="form-label">Maximum Stars</label>
                                    <select class="form-select" id="questionModalRatingMax">
                                        <option value="3">3</option>
                                        <option value="5">5</option>
                                        <option value="7">7</option>
                                        <option value="10">10</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveQuestionModalBtn">
                                <i class="fas fa-save me-2"></i>Save Question
                            </button>
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
        let questionModalInstance;
        let activeQuestionCard = null;

        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('questionEditorModal');
            if (modalEl) {
                questionModalInstance = new bootstrap.Modal(modalEl);
            }

            document.getElementById('questionModalType').addEventListener('change', (event) => {
                toggleQuestionEditorSections(event.target.value);
            });

            document.getElementById('questionModalAllowOther').addEventListener('change', (event) => {
                const group = document.getElementById('questionModalOtherPlaceholderGroup');
                if (group) {
                    group.style.display = event.target.checked ? 'block' : 'none';
                }
            });

            document.getElementById('saveQuestionModalBtn').addEventListener('click', saveQuestionChanges);
        });

        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 5000);
        }

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
            .then(response => {
                if (!response.ok) {
                    throw new Error('Update form request failed');
                }
                return response.json();
            })
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
            // Placeholder until full builder parity is shipped
            showNotification('info', 'Adding new questions from this page is coming soon.');
        }

        function editQuestion(questionId) {
            const questionCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
            if (!questionCard) {
                showNotification('error', 'Question data not found.');
                return;
            }

            activeQuestionCard = questionCard;
            resetQuestionModal();

            document.getElementById('editQuestionId').value = questionId;
            const questionText = questionCard.dataset.questionText || '';
            const questionType = questionCard.dataset.questionType || 'text';
            const isRequired = questionCard.dataset.questionRequired === '1';
            const placeholder = questionCard.dataset.questionPlaceholder || '';
            const validation = safeJsonParse(questionCard.dataset.questionValidation || null);
            const optionsData = safeJsonParse(questionCard.dataset.questionOptions || null);

            document.getElementById('questionModalText').value = questionText;
            document.getElementById('questionModalType').value = questionType;
            document.getElementById('questionModalRequired').checked = isRequired;
            toggleQuestionEditorSections(questionType);

            switch (questionType) {
                case 'radio':
                case 'checkbox':
                case 'dropdown':
                    document.getElementById('questionModalChoiceOptions').value = (optionsData?.options || []).join('\n');
                    document.getElementById('questionModalAllowOther').checked = !!optionsData?.allow_other;
                    document.getElementById('questionModalOtherPlaceholder').value = optionsData?.other_placeholder || '';
                    document.getElementById('questionModalOtherPlaceholderGroup').style.display = optionsData?.allow_other ? 'block' : 'none';
                    break;
                case 'rating_stars':
                    document.getElementById('questionModalRatingMax').value = optionsData?.max_rating || questionCard.dataset.questionMax || 5;
                    break;
                case 'rating_scale':
                case 'slider':
                    document.getElementById('questionModalMinValue').value = questionCard.dataset.questionMin || optionsData?.min_value || 0;
                    document.getElementById('questionModalMaxValue').value = questionCard.dataset.questionMax || optionsData?.max_value || 10;
                    document.getElementById('questionModalStepValue').value = questionCard.dataset.questionStep || optionsData?.step_value || 1;
                    break;
                case 'text':
                case 'textarea':
                    document.getElementById('questionModalPlaceholder').value = placeholder;
                    document.getElementById('questionModalMaxLength').value = validation?.max_length || '';
                    break;
            }

            questionModalInstance.show();
        }

        function resetQuestionModal() {
            document.getElementById('questionEditorForm').reset();
            document.getElementById('questionModalOtherPlaceholderGroup').style.display = 'none';
            toggleQuestionEditorSections(document.getElementById('questionModalType').value);
        }

        function toggleQuestionEditorSections(type) {
            const sections = {
                text: document.getElementById('textOptionsGroup'),
                choice: document.getElementById('choiceOptionsGroup'),
                range: document.getElementById('rangeOptionsGroup'),
                rating: document.getElementById('ratingOptionsGroup')
            };
            Object.values(sections).forEach(section => section.classList.add('d-none'));

            if (type === 'text' || type === 'textarea' || type === 'number' || type === 'email' || type === 'date' || type === 'time') {
                sections.text.classList.remove('d-none');
            } else if (['radio','checkbox','dropdown'].includes(type)) {
                sections.choice.classList.remove('d-none');
            } else if (['rating_scale','slider'].includes(type)) {
                sections.range.classList.remove('d-none');
            } else if (type === 'rating_stars') {
                sections.rating.classList.remove('d-none');
            }
        }

        function safeJsonParse(value) {
            if (!value) return null;
            try {
                return JSON.parse(value);
            } catch (error) {
                return null;
            }
        }

        function saveQuestionChanges() {
            const questionId = parseInt(document.getElementById('editQuestionId').value, 10);
            const questionText = document.getElementById('questionModalText').value.trim();
            const questionType = document.getElementById('questionModalType').value;
            const isRequired = document.getElementById('questionModalRequired').checked;

            if (!questionText) {
                showNotification('error', 'Question text is required.');
                return;
            }

            const payload = buildQuestionPayload(questionId, questionText, questionType, isRequired);
            const saveBtn = document.getElementById('saveQuestionModalBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

            fetch('../../api/custom_forms/update_question.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Update question request failed');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    applyQuestionChangesToCard(activeQuestionCard, payload);
                    questionModalInstance.hide();
                    showNotification('success', 'Question updated successfully!');
                } else {
                    showNotification('error', data.message || 'Failed to update question');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred while updating the question');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            });
        }

        function buildQuestionPayload(questionId, questionText, questionType, isRequired) {
            let options = null;
            let validation = null;
            let placeholder = null;
            let minValue = null;
            let maxValue = null;
            let stepValue = null;

            switch (questionType) {
                case 'radio':
                case 'checkbox':
                case 'dropdown':
                    const choices = document.getElementById('questionModalChoiceOptions').value
                        .split('\n')
                        .map(opt => opt.trim())
                        .filter(opt => opt !== '');
                    const allowOther = document.getElementById('questionModalAllowOther').checked;
                    const otherPlaceholder = document.getElementById('questionModalOtherPlaceholder').value.trim();
                    options = JSON.stringify({
                        options: choices,
                        allow_other: allowOther,
                        other_placeholder: allowOther ? (otherPlaceholder || 'Please specify...') : ''
                    });
                    break;
                case 'rating_stars':
                    const maxStars = parseInt(document.getElementById('questionModalRatingMax').value, 10) || 5;
                    options = JSON.stringify({ max_rating: maxStars });
                    maxValue = maxStars;
                    break;
                case 'rating_scale':
                case 'slider':
                    minValue = document.getElementById('questionModalMinValue').value;
                    maxValue = document.getElementById('questionModalMaxValue').value;
                    stepValue = document.getElementById('questionModalStepValue').value;
                    options = JSON.stringify({
                        min_value: Number(minValue),
                        max_value: Number(maxValue),
                        step_value: Number(stepValue)
                    });
                    break;
                case 'text':
                case 'textarea':
                case 'number':
                case 'email':
                case 'date':
                case 'time':
                    placeholder = document.getElementById('questionModalPlaceholder').value.trim() || null;
                    const maxLength = document.getElementById('questionModalMaxLength').value;
                    if (maxLength) {
                        validation = JSON.stringify({ max_length: parseInt(maxLength, 10) });
                    }
                    break;
            }

            return {
                question_id: questionId,
                question_text: questionText,
                question_type: questionType,
                is_required: isRequired,
                options,
                validation_rules: validation,
                placeholder_text: placeholder,
                min_value: minValue,
                max_value: maxValue,
                step_value: stepValue
            };
        }

        function applyQuestionChangesToCard(card, payload) {
            if (!card) return;
            card.dataset.questionText = payload.question_text;
            card.dataset.questionType = payload.question_type;
            card.dataset.questionRequired = payload.is_required ? '1' : '0';
            card.dataset.questionOptions = payload.options || '';
            card.dataset.questionPlaceholder = payload.placeholder_text || '';
            card.dataset.questionValidation = payload.validation_rules || '';
            card.dataset.questionMin = payload.min_value ?? '';
            card.dataset.questionMax = payload.max_value ?? '';
            card.dataset.questionStep = payload.step_value ?? '';

            const typeBadge = card.querySelector('.question-type-label');
            if (typeBadge) {
                typeBadge.textContent = payload.question_type.toUpperCase();
            }

            const requiredBadge = card.querySelector('.required-indicator');
            if (requiredBadge) {
                requiredBadge.style.display = payload.is_required ? '' : 'none';
            }

            const questionTextEl = card.querySelector('.question-text-preview strong');
            if (questionTextEl) {
                questionTextEl.textContent = payload.question_text;
            }

            const placeholderPreview = card.querySelector('.placeholder-preview');
            if (placeholderPreview) {
                if (payload.placeholder_text) {
                    placeholderPreview.textContent = payload.placeholder_text;
                    placeholderPreview.parentElement.style.display = 'block';
                } else {
                    placeholderPreview.parentElement.style.display = 'none';
                }
            }

            renderQuestionOptionsPreview(card, payload);
        }

        function renderQuestionOptionsPreview(card, payload) {
            const container = card.querySelector('.question-options-preview');
            if (!container) return;
            container.innerHTML = '';

            const optionsData = safeJsonParse(payload.options);
            if (['radio','checkbox','dropdown'].includes(payload.question_type)) {
                const options = optionsData?.options || [];
                if (options.length === 0) {
                    container.innerHTML = '<small class="text-muted">No options configured.</small>';
                    return;
                }
                const label = document.createElement('small');
                label.className = 'text-muted';
                label.textContent = 'Options:';
                const list = document.createElement('ul');
                list.className = 'list-unstyled ms-3';
                options.forEach(option => {
                    const li = document.createElement('li');
                    li.innerHTML = `<small>• ${option}</small>`;
                    list.appendChild(li);
                });
                container.appendChild(label);
                container.appendChild(list);
            } else if (payload.question_type === 'rating_stars') {
                const max = optionsData?.max_rating || payload.max_value || 5;
                container.innerHTML = `<small class="text-muted">Max rating: ${max} star(s)</small>`;
            } else if (payload.question_type === 'rating_scale' || payload.question_type === 'slider') {
                const min = optionsData?.min_value ?? Number(payload.min_value ?? 0);
                const max = optionsData?.max_value ?? Number(payload.max_value ?? 10);
                const step = optionsData?.step_value ?? Number(payload.step_value ?? 1);
                container.innerHTML = `<small class="text-muted">Range: ${min} – ${max} (step ${step})</small>`;
            }
        }

        function deleteQuestion(questionId) {
            if (!confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                return;
            }
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
                    const questionCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
                    if (questionCard) {
                        questionCard.remove();
                    }
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

        function previewForm() {
            const formCode = '<?php echo $form['form_code']; ?>';
            const previewUrl = `../../../public/form/?code=${formCode}`;
            window.open(previewUrl, '_blank');
        }
    </script>
</body>
</html>
