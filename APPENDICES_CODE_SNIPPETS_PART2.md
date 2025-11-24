# Appendices: FeedLoop System Code Snippets and Technical Documentation
## Part 2: Frontend JavaScript and Activity Logging

---

## APPENDIX D: Frontend JavaScript Behavior Code Snippets

### Add New Question Modal
**File:** `admin/content/custom_forms/edit_form.php` (Lines 527-534)  
**Behavior:** Opens question editor modal for creating new questions

```javascript
function addQuestion() {
    activeQuestionCard = null;
    resetQuestionModal();
    document.getElementById('editQuestionId').value = '';
    document.getElementById('questionModalType').value = 'text';
    toggleQuestionEditorSections('text');
    questionModalInstance.show();
}
```

**UI Behavior:**
- Clears active question card reference (indicates new question)
- Resets all form fields in modal
- Clears question ID field (null = new question)
- Sets default question type to 'text'
- Shows text input section
- Displays the Bootstrap modal

---

### Edit Question Modal Population
**File:** `admin/content/custom_forms/edit_form.php` (Lines 536-585)  
**Behavior:** Loads existing question data into editor modal

```javascript
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
```

**Data Loading Behavior:**
- Finds question card in DOM by question ID
- Sets active question card reference (indicates edit mode)
- Populates question ID field
- Extracts all question data from card attributes
- Safely parses JSON data
- Populates modal fields based on question type
- Shows/hides type-specific option sections
- Displays the modal with pre-filled data

---

### Dynamic Question Type Section Toggle
**File:** `admin/content/custom_forms/edit_form.php` (Lines 593-611)  
**Behavior:** Shows/hides form sections based on selected question type

```javascript
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
```

**UI Behavior:**
- Gets references to all option section divs
- Hides all sections by adding Bootstrap 'd-none' class
- Shows appropriate section based on question type
- Text types show text options (placeholder, max length)
- Choice types show options input
- Range types show min/max/step inputs
- Rating types show max rating input

---

### Save Question Changes (Create or Update)
**File:** `admin/content/custom_forms/edit_form.php` (Lines 622-684)  
**Behavior:** Sends question data to appropriate API endpoint

```javascript
function saveQuestionChanges() {
    const questionIdInput = document.getElementById('editQuestionId').value;
    const isNewQuestion = !questionIdInput;
    const questionId = isNewQuestion ? null : parseInt(questionIdInput, 10);
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

    const endpoint = isNewQuestion ? '../../api/custom_forms/create_question.php' : '../../api/custom_forms/update_question.php';
    const formId = document.getElementById('formId').value;

    const requestPayload = isNewQuestion 
        ? { ...payload, form_id: formId }
        : payload;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestPayload)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Request failed with status ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (isNewQuestion) {
                // Add new question to the list
                addNewQuestionToList(data.data);
                showNotification('success', 'Question added successfully!');
            } else {
                applyQuestionChangesToCard(activeQuestionCard, payload);
                showNotification('success', 'Question updated successfully!');
            }
            questionModalInstance.hide();
        } else {
            showNotification('error', data.message || 'Failed to save question');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while saving the question');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}
```

**API Behavior:**
- Determines if creating new question or updating existing
- Validates question text is not empty
- Builds payload with all question properties
- Disables save button and shows loading spinner
- Selects endpoint based on create vs update
- Adds form_id for new questions
- Sends POST request with JSON payload
- Handles response and updates UI accordingly
- For new questions: adds to list and shows success
- For updates: applies changes to card and shows success
- Catches errors and displays user-friendly messages
- Restores button state in finally block

---

### Build Question Payload with Type-Specific Data
**File:** `admin/content/custom_forms/edit_form.php` (Lines 686-752)  
**Behavior:** Constructs question data object with type-specific properties

```javascript
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
```

**Data Serialization Behavior:**
- Creates payload object with base properties
- For choice types: collects options, parses "allow other" setting
- For rating stars: gets max rating value
- For range types: gets min, max, step values
- For text types: gets placeholder and max length validation
- Converts numeric values to appropriate types
- Returns complete payload object for API submission

---

### Delete Question with Confirmation
**File:** `admin/content/custom_forms/edit_form.php` (Lines 829-858)  
**Behavior:** Deletes question after user confirmation

```javascript
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
                // Show empty state message
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
```

**Deletion Behavior:**
- Shows confirmation dialog to user
- Returns early if user cancels
- Sends DELETE request to API endpoint
- Parses JSON response
- Removes question card from DOM on success
- Shows success notification
- Checks if any questions remain
- Shows error notification on failure

---

## Activity Logging Code Snippets

### Log Activity Function
**File:** `includes/activity_logger.php` (Lines 11-34)  
**Behavior:** Records user actions to audit trail

```php
function logActivity($pdo, $userId, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs 
            (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId,
            $action,
            $details,
            $ip,
            $userAgent
        ]);
        
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}
```

**Logging Behavior:**
- Captures user IP address from `$_SERVER['REMOTE_ADDR']`
- Captures user agent (browser/device info) from `$_SERVER['HTTP_USER_AGENT']`
- Inserts record into activity_logs table
- Logs action type (e.g., 'form_created', 'question_updated')
- Stores additional details as JSON or text
- Returns success/failure boolean
- Catches database exceptions and logs errors

---

### Form Creation Activity Log
**File:** `admin/api/custom_forms/create_form.php` (Lines 188-198)  
**Behavior:** Records form creation event with details

```php
// Log activity
$stmt = $pdo->prepare("
    INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
    VALUES (?, 'form_created', ?, ?, ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    "Created custom form: {$input['title']} (ID: {$form_id})",
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null
]);
```

**Audit Trail Behavior:**
- Logs 'form_created' action
- Includes form title and ID in details
- Records user ID from session
- Captures IP address and user agent
- Executes prepared statement for security

---

### Question Creation Activity Log
**File:** `admin/api/custom_forms/create_question.php` (Lines 123-133)  
**Behavior:** Records question creation with form reference

```php
// Log activity
$stmt = $pdo->prepare("
    INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
    VALUES (?, 'question_created', ?, ?, ?)
");
$stmt->execute([
    $user_id,
    "Created question for form ID: {$form_id}",
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null
]);
```

**Audit Trail Behavior:**
- Logs 'question_created' action
- Includes form ID in details for traceability
- Records user ID, IP, and user agent
- Enables audit trail for form modifications

---

### Question Deletion Activity Log with JSON Details
**File:** `admin/api/custom_forms/delete_question.php` (Lines 95-112)  
**Behavior:** Records question deletion with comprehensive details

```php
// Log the activity
$stmt = $pdo->prepare("
    INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, timestamp) 
    VALUES (?, 'question_deleted', ?, ?, ?, NOW())
");
$details = json_encode([
    'question_id' => $question_id,
    'question_text' => $question['question_text'],
    'form_id' => $question['form_id'],
    'form_title' => $question['form_title'],
    'deleted_answers' => $deleted_answers
]);
$stmt->execute([
    $user_id,
    $details,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);
```

**Audit Trail Behavior:**
- Logs 'question_deleted' action
- Stores comprehensive JSON details including:
  - Question ID and text (for recovery reference)
  - Form ID and title (for context)
  - Number of deleted answers (impact assessment)
- Records user ID, IP, and user agent
- Includes timestamp for audit trail
- Enables full traceability of deletions

---

## Error Handling and Transaction Management

### Transaction Rollback on Error
**File:** `admin/api/custom_forms/create_form.php` (Lines 215-226)  
**Behavior:** Rolls back all changes if any step fails

```php
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
```

**Error Handling Behavior:**
- Catches any exception thrown during form creation
- Checks if transaction is active
- Rolls back all database changes
- Returns HTTP 400 (Bad Request) status
- Returns error message in JSON format
- Prevents partial data insertion

---

### Question Deletion Transaction Rollback
**File:** `admin/api/custom_forms/delete_question.php` (Lines 130-145)  
**Behavior:** Rolls back deletion if any step fails

```php
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log the error
    error_log("Question deletion error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
```

**Error Handling Behavior:**
- Catches deletion exceptions
- Rolls back transaction if active
- Logs error to server error log
- Returns HTTP 400 status
- Returns error message to client
- Ensures data consistency

---

## Summary of Code Behaviors

| Behavior | File | Type | Purpose |
|----------|------|------|---------|
| Session Security | create_form.php | PHP | Prevents XSS, CSRF, and session hijacking |
| Authorization | create_form.php | PHP | Ensures only admins can create forms |
| Input Validation | create_form.php | PHP | Validates form and question data |
| Unique Code Generation | create_form.php | PHP | Generates shareable form codes |
| Transaction Management | create_form.php | PHP | Ensures atomic form creation |
| Permission Checking | create_question.php | PHP | Verifies form ownership |
| Question Ordering | create_question.php | PHP | Maintains sequential question order |
| Type Validation | update_question.php | PHP | Validates question types |
| Cascade Deletion | delete_question.php | PHP | Deletes answers when question deleted |
| Reordering | delete_question.php | PHP | Maintains question sequence |
| Modal Management | edit_form.php | JavaScript | Opens/closes question editor |
| Data Loading | edit_form.php | JavaScript | Populates modal with question data |
| Dynamic UI | edit_form.php | JavaScript | Shows/hides type-specific options |
| API Communication | edit_form.php | JavaScript | Sends/receives data from API |
| Activity Logging | activity_logger.php | PHP | Records user actions for audit |
| Error Handling | All files | PHP/JS | Graceful error recovery |
