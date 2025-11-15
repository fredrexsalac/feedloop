/**
 * Custom Forms JavaScript
 * Google Forms-inspired form builder for FeedLoop
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

// Global variables (use var to tolerate re-includes without SyntaxError)
var questionCounter = window.questionCounter || 0;
var currentFormId = window.currentFormId || null;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeCustomForms();
});

// Also initialize when script is loaded (for AJAX content)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCustomForms);
} else {
    // DOM is already loaded, initialize immediately
    initializeCustomForms();
}

/**
 * Initialize custom forms functionality
 */
function initializeCustomForms() {
    // Prevent double initialization if script is injected multiple times
    if (window.__customFormsInitialized__) {
        return;
    }
    window.__customFormsInitialized__ = true;
    // Set up event listeners
    setupEventListeners();
    
    // Initialize form visibility handler
    const visibilitySelect = document.getElementById('form-visibility');
    if (visibilitySelect) {
        visibilitySelect.addEventListener('change', handleVisibilityChange);
    }
}

/**
 * Set up event listeners
 */
function setupEventListeners() {
    // Form settings event listeners
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-type')) {
            updateQuestionOptions(e.target);
        }
    });
}

/**
 * Show create form modal
 */
function showCreateFormModal() {
    const modal = new bootstrap.Modal(document.getElementById('createFormModal'));
    resetFormBuilder();
    modal.show();
}

/**
 * Reset form builder to initial state
 */
function resetFormBuilder() {
    // Clear form settings
    document.getElementById('form-title').value = '';
    document.getElementById('form-description').value = '';
    document.getElementById('form-visibility').value = 'public';
    document.getElementById('target-audience').value = '';
    document.getElementById('allow-anonymous').checked = true;
    document.getElementById('max-responses').value = '';
    document.getElementById('expires-at').value = '';
    
    // Clear questions
    const questionsContainer = document.getElementById('questions-container');
    questionsContainer.innerHTML = `
        <div class="text-center py-4 text-muted">
            <i class="fas fa-question-circle fa-2x mb-2"></i>
            <p>No questions added yet. Click "Add Question" to get started.</p>
        </div>
    `;
    
    questionCounter = 0;
    currentFormId = null;
    
    // Hide target audience group
    document.getElementById('target-audience-group').style.display = 'none';
}

/**
 * Handle visibility change
 */
function handleVisibilityChange() {
    const visibility = document.getElementById('form-visibility').value;
    const targetAudienceGroup = document.getElementById('target-audience-group');
    
    if (visibility === 'department' || visibility === 'event') {
        targetAudienceGroup.style.display = 'block';
        const label = visibility === 'department' ? 'Department Name' : 'Event Name';
        document.querySelector('#target-audience-group label').textContent = label;
    } else {
        targetAudienceGroup.style.display = 'none';
    }
}

/**
 * Add a new question to the form
 */
function addQuestion() {
    questionCounter++;
    
    const questionsContainer = document.getElementById('questions-container');
    const template = document.getElementById('question-template');
    const questionElement = template.cloneNode(true);
    
    // Remove template ID and show element
    questionElement.id = '';
    questionElement.style.display = 'block';
    
    // Update question number
    const questionNumber = questionElement.querySelector('.question-number');
    questionNumber.textContent = questionCounter;
    
    // Set unique IDs for form elements
    const questionId = 'question-' + questionCounter;
    questionElement.setAttribute('data-question-id', questionId);
    
    // Clear placeholder content if it exists
    if (questionsContainer.querySelector('.text-center')) {
        questionsContainer.innerHTML = '';
    }
    
    // Add to container
    questionsContainer.appendChild(questionElement);
    
    // Focus on question text input
    const questionTextInput = questionElement.querySelector('.question-text');
    questionTextInput.focus();
    
    // Update question numbers
    updateQuestionNumbers();
}

/**
 * Remove a question from the form
 */
function removeQuestion(button) {
    const questionItem = button.closest('.question-item');
    questionItem.remove();
    
    // Update question numbers
    updateQuestionNumbers();
    
    // Show placeholder if no questions remain
    const questionsContainer = document.getElementById('questions-container');
    if (questionsContainer.children.length === 0) {
        questionsContainer.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-question-circle fa-2x mb-2"></i>
                <p>No questions added yet. Click "Add Question" to get started.</p>
            </div>
        `;
        questionCounter = 0;
    }
}

/**
 * Update question numbers
 */
function updateQuestionNumbers() {
    const questionItems = document.querySelectorAll('.question-item');
    questionItems.forEach((item, index) => {
        const questionNumber = item.querySelector('.question-number');
        questionNumber.textContent = index + 1;
    });
    questionCounter = questionItems.length;
}

/**
 * Update question options based on type
 */
function updateQuestionOptions(selectElement) {
    const questionItem = selectElement.closest('.question-item');
    const optionsContainer = questionItem.querySelector('.question-options');
    const questionType = selectElement.value;
    
    // Clear existing options
    optionsContainer.innerHTML = '';
    
    switch (questionType) {
        case 'radio':
        case 'checkbox':
        case 'dropdown':
            optionsContainer.style.display = 'block';
            optionsContainer.innerHTML = `
                <label class="form-label">Options</label>
                <div class="options-list">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control option-input" placeholder="Option 1">
                        <button class="btn btn-outline-danger" type="button" onclick="removeOption(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control option-input" placeholder="Option 2">
                        <button class="btn btn-outline-danger" type="button" onclick="removeOption(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input allow-other-toggle" type="checkbox" id="allowOther-${questionCounter}">
                    <label class="form-check-label" for="allowOther-${questionCounter}">Include "Other (please specify)" option</label>
                </div>
                <div class="mt-2 other-placeholder-group" style="display:none;">
                    <label class="form-label">Other placeholder</label>
                    <input type="text" class="form-control other-placeholder" placeholder="Please specify...">
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addOption(this)">
                    <i class="fas fa-plus me-1"></i>Add Option
                </button>
            `;
            // Wire toggle for 'Other' placeholder visibility
            const allowOtherToggle = optionsContainer.querySelector('.allow-other-toggle');
            const otherGroup = optionsContainer.querySelector('.other-placeholder-group');
            if (allowOtherToggle && otherGroup) {
                allowOtherToggle.addEventListener('change', function() {
                    otherGroup.style.display = this.checked ? 'block' : 'none';
                });
            }
            break;
            
        case 'rating_stars':
            optionsContainer.style.display = 'block';
            optionsContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Maximum Stars</label>
                        <select class="form-select">
                            <option value="3">3 Stars</option>
                            <option value="5" selected>5 Stars</option>
                            <option value="7">7 Stars</option>
                            <option value="10">10 Stars</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'rating_scale':
            optionsContainer.style.display = 'block';
            optionsContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Minimum Value</label>
                        <input type="number" class="form-control" value="1" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Maximum Value</label>
                        <input type="number" class="form-control" value="10" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Step</label>
                        <input type="number" class="form-control" value="1" min="0.1" step="0.1">
                    </div>
                </div>
            `;
            break;
            
        case 'slider':
            optionsContainer.style.display = 'block';
            optionsContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Minimum Value</label>
                        <input type="number" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Maximum Value</label>
                        <input type="number" class="form-control" value="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Step</label>
                        <input type="number" class="form-control" value="1" min="1">
                    </div>
                </div>
            `;
            break;
            
        case 'text':
        case 'textarea':
            optionsContainer.style.display = 'block';
            optionsContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Placeholder Text</label>
                        <input type="text" class="form-control placeholder-input" placeholder="Enter placeholder text...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Character Limit</label>
                        <input type="number" class="form-control max-length-input" min="1" placeholder="Optional">
                    </div>
                </div>
            `;
            break;
            
        default:
            optionsContainer.style.display = 'none';
            break;
    }
}

/**
 * Add option to multiple choice questions
 */
function addOption(button) {
    // Find the options list reliably even if new controls were inserted before the button
    const optionsContainer = button.closest('.question-options');
    const optionsList = optionsContainer ? optionsContainer.querySelector('.options-list') : null;
    if (!optionsList) return;
    const optionCount = optionsList.children.length + 1;
    
    const newOption = document.createElement('div');
    newOption.className = 'input-group mb-2';
    newOption.innerHTML = `
        <input type="text" class="form-control option-input" placeholder="Option ${optionCount}">
        <button class="btn btn-outline-danger" type="button" onclick="removeOption(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    optionsList.appendChild(newOption);
}

/**
 * Remove option from multiple choice questions
 */
function removeOption(button) {
    const optionItem = button.closest('.input-group');
    const optionsList = optionItem.parentNode;
    
    // Don't allow removing if only 2 options remain
    if (optionsList.children.length > 2) {
        optionItem.remove();
    } else {
        showNotification('warning', 'At least 2 options are required for multiple choice questions.');
    }
}

/**
 * Save the form
 */
function saveForm() {
    // Validate form settings
    const title = document.getElementById('form-title').value.trim();
    if (!title) {
        showNotification('error', 'Form title is required.');
        return;
    }
    
    // Get questions
    const questions = collectQuestions();
    if (questions.length === 0) {
        showNotification('error', 'At least one question is required.');
        return;
    }
    
    // Prepare form data
    const formData = {
        title: title,
        description: document.getElementById('form-description').value.trim(),
        visibility: document.getElementById('form-visibility').value,
        target_audience: document.getElementById('target-audience').value.trim(),
        allow_anonymous: document.getElementById('allow-anonymous').checked,
        max_responses: document.getElementById('max-responses').value || null,
        expires_at: document.getElementById('expires-at').value || null,
        questions: questions
    };
    
    // Show loading state
    const saveButton = document.querySelector('#createFormModal .btn-primary');
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Form...';
    saveButton.disabled = true;
    
    // Send to server
    fetch(getApiPath('create_form.php'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Form created successfully!');
            bootstrap.Modal.getInstance(document.getElementById('createFormModal')).hide();
            
            // Reload the page to show the new form
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('error', data.message || 'Failed to create form.');
        }
    })
    .catch(error => {
        console.error('Error creating form:', error);
        showNotification('error', 'An error occurred while creating the form.');
    })
    .finally(() => {
        // Restore button state
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
    });
}

/**
 * Collect questions from the form builder
 */
function collectQuestions() {
    const questions = [];
    const questionItems = document.querySelectorAll('.question-item');
    
    questionItems.forEach((item, index) => {
        const questionText = item.querySelector('.question-text').value.trim();
        const questionType = item.querySelector('.question-type').value;
        const isRequired = item.querySelector('.question-required').checked;
        
        if (!questionText) {
            return; // Skip empty questions
        }
        
        const question = {
            question_text: questionText,
            question_type: questionType,
            is_required: isRequired,
            question_order: index + 1,
            options: null,
            validation_rules: null,
            placeholder_text: null,
            min_value: null,
            max_value: null,
            step_value: null
        };
        
        // Get type-specific options
        const optionsContainer = item.querySelector('.question-options');
        
        switch (questionType) {
            case 'radio':
            case 'checkbox':
            case 'dropdown':
                const optionInputs = optionsContainer.querySelectorAll('.option-input');
                const options = Array.from(optionInputs)
                    .map(input => input.value.trim())
                    .filter(option => option !== '');

                const allowOther = !!optionsContainer.querySelector('.allow-other-toggle')?.checked;
                const otherPlaceholder = optionsContainer.querySelector('.other-placeholder')?.value?.trim() || '';

                // Always store options object even if <2, backend will validate allowing 'Other'
                question.options = JSON.stringify({ 
                    options: options,
                    allow_other: allowOther,
                    other_placeholder: otherPlaceholder || 'Please specify...'
                });
                break;
                
            case 'rating_stars':
                const maxStars = optionsContainer.querySelector('select').value;
                question.options = JSON.stringify({ max_rating: parseInt(maxStars) });
                question.max_value = parseInt(maxStars);
                break;
                
            case 'rating_scale':
            case 'slider':
                const minValue = optionsContainer.querySelector('input[type="number"]:nth-of-type(1)').value;
                const maxValue = optionsContainer.querySelector('input[type="number"]:nth-of-type(2)').value;
                const stepValue = optionsContainer.querySelector('input[type="number"]:nth-of-type(3)').value;
                
                question.min_value = parseInt(minValue);
                question.max_value = parseInt(maxValue);
                question.step_value = parseFloat(stepValue);
                question.options = JSON.stringify({
                    min_value: parseInt(minValue),
                    max_value: parseInt(maxValue),
                    step_value: parseFloat(stepValue)
                });
                break;
                
            case 'text':
            case 'textarea':
                const placeholderInput = optionsContainer.querySelector('.placeholder-input');
                const maxLengthInput = optionsContainer.querySelector('.max-length-input');
                
                if (placeholderInput && placeholderInput.value.trim()) {
                    question.placeholder_text = placeholderInput.value.trim();
                }
                
                if (maxLengthInput && maxLengthInput.value) {
                    question.validation_rules = JSON.stringify({
                        max_length: parseInt(maxLengthInput.value)
                    });
                }
                break;
        }
        
        questions.push(question);
    });
    
    return questions;
}

/**
 * Get API path with proper base URL detection
 */
function getApiPath(endpoint) {
    return `/admin/api/custom_forms/${endpoint}`;
}

/**
 * Get content path with proper base URL detection
 */
function getContentPath(page) {
    return `/admin/content/custom_forms/${page}`;
}

/**
 * Show notification
 */
function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
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

// Form management functions
function viewFormResponses(formId) {
    // Open form responses in a new tab/window
    const responsesUrl = getContentPath(`view_responses.php?form_id=${formId}`);
    window.open(responsesUrl, '_blank');
}

function shareForm(formId) {
    // Get form details first
    fetch(getApiPath(`get_form_details.php?form_id=${formId}`))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showShareModal(data.form);
            } else {
                showNotification('error', 'Failed to load form details');
            }
        })
        .catch(error => {
            console.error('Error loading form details:', error);
            showNotification('error', 'Error loading form details');
        });
}

function editForm(formId) {
    // Redirect to form editor
    const editUrl = getContentPath(`edit_form.php?form_id=${formId}`);
    window.location.href = editUrl;
}

function duplicateForm(formId) {
    if (confirm('Create a copy of this form?')) {
        fetch(getApiPath('duplicate_form.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                form_id: formId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Form duplicated successfully');
                // Reload the page to show the new form
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message || 'Failed to duplicate form');
            }
        })
        .catch(error => {
            console.error('Duplicate form error:', error);
            showNotification('error', 'An error occurred while duplicating the form');
        });
    }
}

function exportFormData(formId) {
    // Show export options modal
    showExportModal(formId);
}

function toggleFormStatus(formId, newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    const confirmMessage = `Are you sure you want to ${action} this form?`;
    
    if (confirm(confirmMessage)) {
        fetch(getApiPath('toggle_form_status.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                form_id: formId,
                is_active: newStatus === 'true'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', `Form ${action}d successfully`);
                // Reload the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('error', data.message || `Failed to ${action} form`);
            }
        })
        .catch(error => {
            console.error('Toggle form status error:', error);
            showNotification('error', `An error occurred while ${action}ing the form`);
        });
    }
}

function deleteForm(formId) {
    // Show confirmation dialog with detailed warning
    const confirmMessage = `⚠️ DELETE FORM CONFIRMATION ⚠️

This will permanently delete:
• The form and all its questions
• All submitted responses and data
• Associated analytics and statistics
• QR codes and sharing links

This action CANNOT be undone!

Are you absolutely sure you want to delete this form?`;

    if (confirm(confirmMessage)) {
        // Show loading state
        const deleteButton = document.querySelector(`button[onclick="deleteForm(${formId})"]`);
        const originalText = deleteButton ? deleteButton.innerHTML : '';
        if (deleteButton) {
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
            deleteButton.disabled = true;
        }

        // Make API call to delete form
        fetch(getApiPath('delete_form.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                form_id: formId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', `Form "${data.data.form_title}" deleted successfully`);
                
                // Remove the form row from the table
                const formRow = document.querySelector(`tr[data-form-id="${formId}"]`);
                if (formRow) {
                    formRow.style.transition = 'opacity 0.3s ease';
                    formRow.style.opacity = '0';
                    setTimeout(() => {
                        formRow.remove();
                        
                        // Check if table is now empty
                        const tableBody = document.querySelector('#forms-table tbody');
                        if (tableBody && tableBody.children.length === 0) {
                            // Show empty state
                            const emptyState = `
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Forms Created Yet</h5>
                                            <p class="text-muted">Create your first custom form to get started!</p>
                                            <button class="btn btn-primary" onclick="showCreateFormModal()">
                                                <i class="fas fa-plus me-2"></i>Create New Form
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            tableBody.innerHTML = emptyState;
                        }
                    }, 300);
                }
                
                // Update statistics if available
                updateFormStatistics();
                
            } else {
                showNotification('error', data.message || 'Failed to delete form');
                
                // Restore button state
                if (deleteButton) {
                    deleteButton.innerHTML = originalText;
                    deleteButton.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Delete form error:', error);
            showNotification('error', 'An error occurred while deleting the form');
            
            // Restore button state
            if (deleteButton) {
                deleteButton.innerHTML = originalText;
                deleteButton.disabled = false;
            }
        });
    }
}

// Helper function to update form statistics after deletion
function updateFormStatistics() {
    // Count remaining forms in the table
    const tableBody = document.querySelector('#forms-table tbody');
    const remainingForms = tableBody ? tableBody.querySelectorAll('tr[data-form-id]').length : 0;
    
    // Update all statistics cards
    const statCards = document.querySelectorAll('.stats-grid .stat-card');
    
    if (statCards.length >= 2) {
        // Update Total Forms (first card)
        const totalFormsNumber = statCards[0].querySelector('.stat-number');
        if (totalFormsNumber) {
            totalFormsNumber.textContent = remainingForms;
        }
        
        // Update Active Forms (second card)
        const activeFormsNumber = statCards[1].querySelector('.stat-number');
        if (activeFormsNumber) {
            // Count active forms (status badge with "Active" text)
            const activeForms = tableBody ? 
                tableBody.querySelectorAll('tr[data-form-id] .badge.bg-success').length : 0;
            activeFormsNumber.textContent = activeForms;
        }
        
        // If no forms remain, set all to 0
        if (remainingForms === 0) {
            statCards.forEach(card => {
                const statNumber = card.querySelector('.stat-number');
                if (statNumber) {
                    // Keep percentage format for completion rate
                    if (statNumber.textContent.includes('%')) {
                        statNumber.textContent = '0.0%';
                    } else {
                        statNumber.textContent = '0';
                    }
                }
            });
        }
    }
}

// Show share modal with form details
function showShareModal(form) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'shareFormModal';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-share-alt me-2"></i>Share Form: ${form.title}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-link me-2"></i>Direct Link</h6>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="shareableLink" value="${form.shareable_link}" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard('shareableLink')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-qrcode me-2"></i>QR Code</h6>
                            <div class="text-center" id="qrCodeContainer">
                                <div id="qrCodeLoading">
                                    <i class="fas fa-spinner fa-spin"></i> Generating QR Code...
                                </div>
                                <div id="qrCodeContent" style="display: none;">
                                    <img id="qrCodeImage" alt="QR Code" class="img-fluid" style="max-width: 150px;">
                                    <br>
                                    <button class="btn btn-sm btn-outline-primary mt-2" id="downloadQRBtn">
                                        <i class="fas fa-download me-1"></i>Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-envelope me-2"></i>Email Template</h6>
                            <textarea class="form-control" rows="4" readonly>
Subject: Please Complete: ${form.title}

Hi,

You're invited to participate in our survey: "${form.title}"

${form.description || 'Please take a few minutes to provide your feedback.'}

Access the form here: ${form.shareable_link}

Thank you for your participation!
                            </textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="copyEmailTemplate()">
                        <i class="fas fa-copy me-1"></i>Copy Email Template
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Generate QR code after modal is shown
    generateQRCodeForModal(form);
    
    // Remove modal from DOM when hidden
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

// Show export modal
function showExportModal(formId) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'exportFormModal';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-download me-2"></i>Export Form Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat">
                            <option value="csv">CSV (Comma Separated Values)</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="pdf">PDF Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Include</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeResponses" checked>
                            <label class="form-check-label" for="includeResponses">
                                Response Data
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeAnalytics">
                            <label class="form-check-label" for="includeAnalytics">
                                Analytics Summary
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeTimestamps" checked>
                            <label class="form-check-label" for="includeTimestamps">
                                Submission Timestamps
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="performExport(${formId})">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Remove modal from DOM when hidden
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}

// Helper functions for modals
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');
    showNotification('success', 'Copied to clipboard!');
}

function copyEmailTemplate() {
    const textarea = document.querySelector('#exportFormModal textarea, #shareFormModal textarea');
    if (textarea) {
        textarea.select();
        document.execCommand('copy');
        showNotification('success', 'Email template copied to clipboard!');
    }
}

function downloadQRCode(qrPath) {
    if (qrPath) {
        const link = document.createElement('a');
        link.href = qrPath;
        link.download = 'form-qr-code.png';
        link.click();
    } else {
        showNotification('error', 'QR code not available');
    }
}

function performExport(formId) {
    const format = document.getElementById('exportFormat').value;
    const includeResponses = document.getElementById('includeResponses').checked;
    const includeAnalytics = document.getElementById('includeAnalytics').checked;
    const includeTimestamps = document.getElementById('includeTimestamps').checked;
    
    // Create download URL
    const params = new URLSearchParams({
        form_id: formId,
        format: format,
        include_responses: includeResponses,
        include_analytics: includeAnalytics,
        include_timestamps: includeTimestamps
    });
    
    const exportUrl = getApiPath(`export_form_data.php?${params.toString()}`);
    
    // Trigger download
    window.open(exportUrl, '_blank');
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportFormModal'));
    modal.hide();
    
    showNotification('success', 'Export started! Download will begin shortly.');
}

/**
 * Generate QR code for the share modal using online QR API
 */
function generateQRCodeForModal(form) {
    const qrCodeLoading = document.getElementById('qrCodeLoading');
    const qrCodeContent = document.getElementById('qrCodeContent');
    const qrCodeImage = document.getElementById('qrCodeImage');
    const downloadBtn = document.getElementById('downloadQRBtn');
    
    if (!form.shareable_link) {
        qrCodeLoading.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> No shareable link available';
        return;
    }
    
    // Generate QR code using QR Server API (online service)
    const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    const qrParams = new URLSearchParams({
        size: '300x300',
        data: form.shareable_link,
        format: 'png',
        bgcolor: 'FFFFFF',
        color: '000000',
        qzone: '2',
        margin: '10'
    });
    
    const qrCodeUrl = qrApiUrl + '?' + qrParams.toString();
    
    // Create new image element to test if QR code loads
    const testImg = new Image();
    testImg.onload = function() {
        // QR code loaded successfully
        qrCodeImage.src = qrCodeUrl;
        qrCodeLoading.style.display = 'none';
        qrCodeContent.style.display = 'block';
        
        // Set up download functionality
        downloadBtn.onclick = function() {
            downloadQRCode(qrCodeUrl, form.form_code || 'qr-code');
        };
    };
    
    testImg.onerror = function() {
        // QR code failed to load
        qrCodeLoading.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> Failed to generate QR code';
    };
    
    testImg.src = qrCodeUrl;
}

/**
 * Download QR code image
 */
function downloadQRCode(qrCodeUrl, filename) {
    const link = document.createElement('a');
    link.href = qrCodeUrl;
    link.download = (filename || 'qr-code') + '.png';
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('success', 'QR code download started!');
}

/**
 * Generate comprehensive form report with charts and analysis
 */
async function generateFormReport(formId) {
    try {
        showNotification('info', 'Generating comprehensive report...');
        
        // Fetch report data
        const response = await fetch(getApiPath(`get_form_report_data.php?form_id=${formId}`));
        const result = await response.json();
        
        if (!result.success) {
            showNotification('error', result.message || 'Failed to fetch report data');
            return;
        }
        
        const reportData = result.data;
        
        // Show preview modal
        const modal = new bootstrap.Modal(document.getElementById('reportPreviewModal'));
        modal.show();
        
        // Generate report preview
        await generateReportPreview(reportData);
        
        // Set up download button
        document.getElementById('downloadReportBtn').onclick = function() {
            downloadReportPDF(reportData);
        };
        
    } catch (error) {
        console.error('Error generating report:', error);
        showNotification('error', 'Error generating report: ' + error.message);
    }
}

/**
 * Generate report preview HTML with charts
 */
async function generateReportPreview(data) {
    const container = document.getElementById('reportPreviewContent');
    
    // Create report HTML
    const reportHTML = `
        <div class="report-page" style="background: white; max-width: 210mm; margin: 20px auto; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
            <!-- Cover Page -->
            <div class="report-cover text-center" style="min-height: 297mm; display: flex; flex-direction: column; justify-content: center; padding: 60px 0;">
                <img src="/assets/img/logo/logo.jpg" alt="FeedLoop" style="max-width: 200px; margin: 0 auto 40px;">
                <h1 style="color: #fd7e14; font-size: 42px; font-weight: bold; margin-bottom: 20px;">Form Results Report</h1>
                <h2 style="color: #333; font-size: 28px; margin-bottom: 40px;">${data.form.title}</h2>
                <div style="border-top: 3px solid #fd7e14; border-bottom: 3px solid #fd7e14; padding: 30px 0; margin: 40px 0;">
                    <p style="font-size: 18px; color: #666; margin: 10px 0;">Comprehensive Analysis & Insights</p>
                    <p style="font-size: 16px; color: #999; margin: 10px 0;">Generated: ${new Date(data.generated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    <p style="font-size: 16px; color: #999; margin: 10px 0;">By: ${data.generated_by}</p>
                </div>
                <div style="margin-top: 60px;">
                    <p style="font-size: 14px; color: #999;">Total Responses: <strong>${data.form.response_count}</strong></p>
                    <p style="font-size: 14px; color: #999;">Completion Rate: <strong>${data.analytics?.completion_rate || 0}%</strong></p>
                </div>
            </div>
            
            <!-- Page Break -->
            <div style="page-break-after: always;"></div>
            
            <!-- Executive Summary -->
            <div class="report-section" style="margin-bottom: 40px;">
                <h2 style="color: #fd7e14; border-bottom: 3px solid #fd7e14; padding-bottom: 10px; margin-bottom: 30px;">
                    <i class="fas fa-chart-line me-2"></i>Executive Summary
                </h2>
                <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
                    <h3 style="color: #333; font-size: 20px; margin-bottom: 20px;">Key Findings</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #fd7e14;">
                                <h4 style="color: #fd7e14; font-size: 32px; margin: 0;">${data.form.response_count}</h4>
                                <p style="color: #666; margin: 5px 0 0 0;">Total Responses Collected</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #28a745;">
                                <h4 style="color: #28a745; font-size: 32px; margin: 0;">${data.analytics?.completion_rate || 0}%</h4>
                                <p style="color: #666; margin: 5px 0 0 0;">Completion Rate</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #17a2b8;">
                                <h4 style="color: #17a2b8; font-size: 32px; margin: 0;">${Math.round(data.completion_stats.avg_time / 60)}</h4>
                                <p style="color: #666; margin: 5px 0 0 0;">Avg. Completion Time (minutes)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                                <h4 style="color: #ffc107; font-size: 32px; margin: 0;">${data.questions.length}</h4>
                                <p style="color: #666; margin: 5px 0 0 0;">Total Questions</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h3 style="color: #333; font-size: 20px; margin: 30px 0 20px;">Abstract</h3>
                <p style="color: #666; line-height: 1.8; text-align: justify;">
                    This comprehensive report presents a detailed analysis of the feedback form titled "<strong>${data.form.title}</strong>". 
                    The study collected <strong>${data.form.response_count} responses</strong> from various respondents, achieving a completion rate of 
                    <strong>${data.analytics?.completion_rate || 0}%</strong>. The form consisted of <strong>${data.questions.length} questions</strong> 
                    designed to gather ${data.form.description || 'valuable feedback and insights'}. 
                    ${data.form.description ? 'The primary objective was to ' + data.form.description.toLowerCase() + '.' : ''}
                    This report employs statistical analysis, data visualization, and qualitative assessment to provide actionable insights 
                    derived from the collected responses. The findings presented herein offer a comprehensive understanding of respondent 
                    perspectives and can inform strategic decision-making processes.
                </p>
            </div>
            
            <!-- Page Break -->
            <div style="page-break-after: always;"></div>
            
            <!-- Response Trends Chart -->
            <div class="report-section" style="margin-bottom: 40px;">
                <h2 style="color: #fd7e14; border-bottom: 3px solid #fd7e14; padding-bottom: 10px; margin-bottom: 30px;">
                    <i class="fas fa-chart-area me-2"></i>Response Trends Over Time
                </h2>
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <canvas id="trendsChart" width="800" height="400"></canvas>
                </div>
                <p style="color: #666; margin-top: 20px; line-height: 1.8; text-align: justify;">
                    <strong>Analysis:</strong> The response trend chart illustrates the distribution of form submissions over time. 
                    ${data.daily_trends.length > 0 ? 
                        `Peak response activity was observed on ${new Date(data.daily_trends[0].date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} 
                        with ${data.daily_trends[0].count} submissions. ` : ''}
                    This temporal analysis helps identify patterns in respondent engagement and can inform optimal timing for future data collection efforts.
                </p>
            </div>
            
            <!-- Respondent Distribution -->
            <div class="report-section" style="margin-bottom: 40px;">
                <h2 style="color: #fd7e14; border-bottom: 3px solid #fd7e14; padding-bottom: 10px; margin-bottom: 30px;">
                    <i class="fas fa-users me-2"></i>Respondent Demographics
                </h2>
                <div class="row">
                    <div class="col-md-6">
                        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <canvas id="respondentChart" width="400" height="400"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background: #f8f9fa; padding: 30px; border-radius: 10px;">
                            <h3 style="color: #333; font-size: 18px; margin-bottom: 20px;">Distribution Breakdown</h3>
                            ${data.respondent_distribution.map(item => `
                                <div style="margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #666; font-weight: 500;">${item.respondent_type || 'Unknown'}</span>
                                        <span style="color: #fd7e14; font-weight: bold; font-size: 20px;">${item.count}</span>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <div style="background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div style="background: #fd7e14; height: 100%; width: ${(item.count / data.form.response_count * 100)}%;"></div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
                <p style="color: #666; margin-top: 20px; line-height: 1.8; text-align: justify;">
                    <strong>Analysis:</strong> The respondent distribution provides insights into the demographic composition of participants. 
                    Understanding the respondent profile is crucial for contextualizing the findings and ensuring representative sampling.
                </p>
            </div>
            
            <!-- Page Break -->
            <div style="page-break-after: always;"></div>
            
            <!-- Question Analysis -->
            <div class="report-section">
                <h2 style="color: #fd7e14; border-bottom: 3px solid #fd7e14; padding-bottom: 10px; margin-bottom: 30px;">
                    <i class="fas fa-chart-bar me-2"></i>Detailed Question Analysis
                </h2>
                ${generateQuestionAnalysisHTML(data.question_analysis)}
            </div>
            
            <!-- Footer on every page -->
            <div style="position: fixed; bottom: 20px; right: 40px; left: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #999; font-size: 12px;">© 2025 FeedLoop - Feedback Management System</span>
                    <span style="color: #999; font-size: 12px;" class="page-number"></span>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = reportHTML;
    
    // Generate charts after DOM is ready
    setTimeout(() => {
        generateTrendsChart(data.daily_trends);
        generateRespondentChart(data.respondent_distribution);
        generateQuestionCharts(data.question_analysis);
    }, 100);
}

/**
 * Generate question analysis HTML
 */
function generateQuestionAnalysisHTML(questionAnalysis) {
    return questionAnalysis.map((q, index) => `
        <div style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); ${index > 0 && index % 2 === 0 ? 'page-break-before: always;' : ''}">
            <h3 style="color: #333; font-size: 20px; margin-bottom: 20px;">
                <span style="color: #fd7e14; font-weight: bold;">Q${index + 1}.</span> ${q.question_text}
            </h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <span style="color: #666; font-size: 14px;">Question Type: <strong>${formatQuestionType(q.question_type)}</strong></span>
                <span style="color: #666; font-size: 14px; margin-left: 20px;">Total Responses: <strong>${q.total_responses}</strong></span>
            </div>
            
            ${generateQuestionSpecificAnalysis(q, index)}
        </div>
    `).join('');
}

/**
 * Generate question-specific analysis based on type
 */
function generateQuestionSpecificAnalysis(q, index) {
    if (q.distribution) {
        // Multiple choice or rating distribution
        const entries = Object.entries(q.distribution);
        return `
            <div class="row">
                <div class="col-md-6">
                    <canvas id="questionChart${index}" width="400" height="300"></canvas>
                </div>
                <div class="col-md-6">
                    <h4 style="color: #333; font-size: 16px; margin-bottom: 15px;">Response Distribution</h4>
                    ${entries.map(([key, value]) => `
                        <div style="margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="color: #666;">${key}</span>
                                <span style="color: #fd7e14; font-weight: bold;">${value} (${((value/q.total_responses)*100).toFixed(1)}%)</span>
                            </div>
                            <div style="background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: #fd7e14; height: 100%; width: ${(value/q.total_responses)*100}%;"></div>
                            </div>
                        </div>
                    `).join('')}
                    ${q.most_common ? `<p style="color: #28a745; margin-top: 20px; font-weight: 500;"><i class="fas fa-star me-2"></i>Most Common: ${q.most_common}</p>` : ''}
                </div>
            </div>
            <p style="color: #666; margin-top: 20px; line-height: 1.8; text-align: justify;">
                <strong>Interpretation:</strong> The data reveals ${q.most_common ? `that "${q.most_common}" was the most frequently selected response, ` : ''}
                indicating ${generateInsight(q)}. This distribution pattern suggests meaningful trends in respondent preferences.
            </p>
        `;
    } else if (q.average !== undefined) {
        // Numeric analysis
        return `
            <div class="row">
                <div class="col-md-3">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #fd7e14;">
                        <h4 style="color: #fd7e14; font-size: 28px; margin: 0;">${q.average.toFixed(2)}</h4>
                        <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Average</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #28a745;">
                        <h4 style="color: #28a745; font-size: 28px; margin: 0;">${q.max}</h4>
                        <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Maximum</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #17a2b8;">
                        <h4 style="color: #17a2b8; font-size: 28px; margin: 0;">${q.min}</h4>
                        <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Minimum</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #ffc107;">
                        <h4 style="color: #ffc107; font-size: 28px; margin: 0;">${q.median}</h4>
                        <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Median</p>
                    </div>
                </div>
            </div>
            <p style="color: #666; margin-top: 20px; line-height: 1.8; text-align: justify;">
                <strong>Statistical Analysis:</strong> The average rating of ${q.average.toFixed(2)} indicates ${q.average >= 4 ? 'highly positive' : q.average >= 3 ? 'moderately positive' : 'mixed'} 
                feedback from respondents. The range between ${q.min} and ${q.max} demonstrates ${q.max - q.min <= 2 ? 'consistent' : 'varied'} responses across the sample.
            </p>
        `;
    } else if (q.sample_responses) {
        // Text responses
        return `
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="color: #333; font-size: 16px; margin-bottom: 15px;">Sample Responses</h4>
                ${q.sample_responses.slice(0, 5).map(response => `
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid #fd7e14;">
                        <p style="color: #666; margin: 0; font-style: italic;">"${response}"</p>
                    </div>
                `).join('')}
                <p style="color: #999; margin-top: 15px; font-size: 14px;">
                    <i class="fas fa-info-circle me-2"></i>Average response length: ${Math.round(q.avg_length)} characters
                </p>
            </div>
            <p style="color: #666; margin-top: 20px; line-height: 1.8; text-align: justify;">
                <strong>Qualitative Analysis:</strong> The open-ended responses provide rich, contextual insights. 
                The average response length of ${Math.round(q.avg_length)} characters suggests ${q.avg_length > 100 ? 'detailed and thoughtful' : 'concise'} 
                feedback from participants.
            </p>
        `;
    }
    return '<p style="color: #999;">No detailed analysis available for this question type.</p>';
}

/**
 * Generate insight based on question data
 */
function generateInsight(q) {
    const topValue = Object.values(q.distribution)[0];
    const percentage = (topValue / q.total_responses * 100).toFixed(1);
    
    if (percentage > 70) {
        return 'strong consensus among respondents';
    } else if (percentage > 50) {
        return 'a clear majority preference';
    } else if (percentage > 30) {
        return 'moderate agreement among participants';
    } else {
        return 'diverse perspectives across the respondent base';
    }
}

/**
 * Format question type for display
 */
function formatQuestionType(type) {
    const types = {
        'text': 'Short Text',
        'textarea': 'Long Text',
        'radio': 'Multiple Choice (Single)',
        'checkbox': 'Multiple Choice (Multiple)',
        'dropdown': 'Dropdown',
        'rating_stars': 'Star Rating',
        'rating_scale': 'Rating Scale',
        'slider': 'Slider',
        'email': 'Email',
        'number': 'Number',
        'date': 'Date',
        'time': 'Time'
    };
    return types[type] || type;
}

/**
 * Generate trends chart
 */
function generateTrendsChart(dailyTrends) {
    const ctx = document.getElementById('trendsChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyTrends.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
            datasets: [{
                label: 'Responses',
                data: dailyTrends.map(d => d.count),
                borderColor: '#fd7e14',
                backgroundColor: 'rgba(253, 126, 20, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Daily Response Submissions',
                    font: { size: 16, weight: 'bold' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

/**
 * Generate respondent distribution chart
 */
function generateRespondentChart(distribution) {
    const ctx = document.getElementById('respondentChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: distribution.map(d => d.respondent_type || 'Unknown'),
            datasets: [{
                data: distribution.map(d => d.count),
                backgroundColor: [
                    '#fd7e14',
                    '#28a745',
                    '#17a2b8',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, font: { size: 12 } }
                },
                title: {
                    display: true,
                    text: 'Respondent Type Distribution',
                    font: { size: 16, weight: 'bold' }
                }
            }
        }
    });
}

/**
 * Generate charts for individual questions
 */
function generateQuestionCharts(questionAnalysis) {
    questionAnalysis.forEach((q, index) => {
        if (q.distribution) {
            const ctx = document.getElementById(`questionChart${index}`);
            if (!ctx) return;
            
            const entries = Object.entries(q.distribution);
            
            new Chart(ctx, {
                type: entries.length > 5 ? 'bar' : 'pie',
                data: {
                    labels: entries.map(([key]) => key),
                    datasets: [{
                        data: entries.map(([, value]) => value),
                        backgroundColor: [
                            '#fd7e14', '#28a745', '#17a2b8', '#ffc107', 
                            '#dc3545', '#6c757d', '#007bff', '#e83e8c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: entries.length > 5 ? 'top' : 'right',
                            labels: { padding: 10, font: { size: 11 } }
                        }
                    }
                }
            });
        }
    });
}

/**
 * Download report as PDF
 */
async function downloadReportPDF(data) {
    try {
        showNotification('info', 'Generating PDF... This may take a moment.');
        
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        const reportElement = document.querySelector('.report-page');
        
        // Use html2canvas to capture the report
        const canvas = await html2canvas(reportElement, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff'
        });
        
        const imgData = canvas.toDataURL('image/png');
        const imgWidth = 210; // A4 width in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        
        let heightLeft = imgHeight;
        let position = 0;
        let pageNumber = 1;
        
        // Add first page
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= 297; // A4 height in mm
        
        // Add subsequent pages if needed
        while (heightLeft > 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pageNumber++;
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= 297;
        }
        
        // Add page numbers
        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(10);
            pdf.setTextColor(150);
            pdf.text(`Page ${i} of ${totalPages}`, 190, 287, { align: 'right' });
        }
        
        // Save PDF
        const filename = `${data.form.title.replace(/[^a-z0-9]/gi, '_')}_Report_${new Date().toISOString().split('T')[0]}.pdf`;
        pdf.save(filename);
        
        showNotification('success', 'PDF report downloaded successfully!');
        
    } catch (error) {
        console.error('Error generating PDF:', error);
        showNotification('error', 'Error generating PDF: ' + error.message);
    }
}
