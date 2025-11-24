# Appendices: FeedLoop System Code Snippets and Technical Documentation
## Part 1: Authentication, Form Creation, and Question Management

---

## APPENDIX A: Authentication and Authorization Code Snippets

### Session Security Configuration
**File:** `admin/api/custom_forms/create_form.php` (Lines 9-18)  
**Behavior:** Implements secure session handling with HTTPOnly cookies, CSRF protection, and SameSite attribute

```php
// Set session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

**Security Behavior:**
- `session.cookie_httponly`: Prevents JavaScript from accessing session cookies (XSS protection)
- `session.use_only_cookies`: Ensures session IDs transmitted only via cookies (not URL)
- `session.cookie_secure`: Enables HTTPS-only transmission when HTTPS is active
- `session.cookie_samesite`: Prevents CSRF attacks by restricting cross-site cookie transmission

---

### Admin Role Verification
**File:** `admin/api/custom_forms/create_form.php` (Lines 26-34)  
**Behavior:** Validates user authentication and authorization before allowing form creation

```php
// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}
```

**Authorization Behavior:**
- Checks if `user_id` exists in session (user is logged in)
- Verifies user role is exactly 'admin' (case-sensitive)
- Returns HTTP 401 status code for unauthorized access
- Prevents further execution with `exit()`
- Returns JSON error response for API consistency

---

### HTTP Method Validation
**File:** `admin/api/custom_forms/create_form.php` (Lines 36-44)  
**Behavior:** Restricts API endpoint to POST requests only

```php
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}
```

**API Behavior:**
- Validates HTTP method is POST (prevents GET, PUT, DELETE, etc.)
- Returns HTTP 405 (Method Not Allowed) status code
- Protects against unintended request methods
- Ensures consistency with RESTful API design

---

## APPENDIX B: Form Creation and Validation Code Snippets

### Input Validation and JSON Parsing
**File:** `admin/api/custom_forms/create_form.php` (Lines 46-61)  
**Behavior:** Validates JSON input and required form fields

```php
try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['title'])) {
        throw new Exception('Form title is required');
    }
    
    if (empty($input['questions']) || !is_array($input['questions'])) {
        throw new Exception('At least one question is required');
    }
```

**Validation Behavior:**
- Reads raw JSON from request body
- Decodes JSON to associative array
- Validates form title is not empty
- Validates questions array exists and is not empty
- Throws exceptions for validation failures (caught by try-catch)

---

### Question Validation with Type-Specific Rules
**File:** `admin/api/custom_forms/create_form.php` (Lines 63-88)  
**Behavior:** Validates each question's content and type-specific requirements

```php
// Validate questions
foreach ($input['questions'] as $question) {
    if (empty($question['question_text'])) {
        throw new Exception('All questions must have text');
    }
    
    if (empty($question['question_type'])) {
        throw new Exception('All questions must have a type');
    }
    
    // Validate multiple choice questions have options
    if (in_array($question['question_type'], ['radio', 'checkbox', 'dropdown'])) {
        $optionsObj = json_decode($question['options'] ?? '{}', true) ?: [];
        $list = isset($optionsObj['options']) && is_array($optionsObj['options']) 
            ? array_values(array_filter($optionsObj['options'], fn($v)=>trim((string)$v)!=='')) 
            : [];
        $allowOther = !empty($optionsObj['allow_other']);
        
        // Accept if 2+ explicit options OR at least 1 option with 'Other' enabled
        if (!(count($list) >= 2 || ($allowOther && count($list) >= 1))) {
            throw new Exception('Multiple choice questions must include at least two options, or one option plus "Other" enabled');
        }
        
        // Normalize placeholder
        if ($allowOther && empty($optionsObj['other_placeholder'])) {
            $optionsObj['other_placeholder'] = 'Please specify...';
            $question['options'] = json_encode($optionsObj);
        }
    }
}
```

**Validation Behavior:**
- Loops through each question in the array
- Validates question text is not empty
- Validates question type is specified
- For multiple choice questions (radio, checkbox, dropdown):
  - Parses options JSON
  - Filters out empty options
  - Requires either 2+ options OR 1 option with "Other" enabled
  - Auto-fills default "Other" placeholder if missing

---

### Unique Form Code Generation
**File:** `admin/api/custom_forms/create_form.php` (Lines 231-244)  
**Behavior:** Generates unique 8-character alphanumeric form codes

```php
function generateUniqueFormCode($pdo) {
    do {
        // Generate 8-character alphanumeric code
        $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        
        // Check if code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_forms WHERE form_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn() > 0;
        
    } while ($exists);
    
    return $code;
}
```

**Code Generation Behavior:**
- Generates random 8-character code from A-Z and 0-9
- Converts to uppercase for consistency
- Checks database for existing code
- Repeats generation until unique code is found
- Uses prepared statement to prevent SQL injection
- Returns unique form code for shareable link

---

### Transaction-Safe Form Insertion
**File:** `admin/api/custom_forms/create_form.php` (Lines 97-177)  
**Behavior:** Inserts form, questions, and analytics in a single atomic transaction

```php
// Start transaction
$pdo->beginTransaction();

// Insert form
$stmt = $pdo->prepare("
    INSERT INTO custom_forms (
        title, description, created_by, visibility, target_audience, 
        department, event_name, form_code, shareable_link, 
        allow_anonymous, require_login, max_responses, expires_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $input['title'],
    $input['description'] ?? null,
    $_SESSION['user_id'],
    $input['visibility'] ?? 'public',
    $target_audience,
    $department,
    $event_name,
    $form_code,
    $shareable_link,
    $allow_anonymous ? 1 : 0,
    $require_login ? 1 : 0,
    $max_responses,
    $expires_at
]);

$form_id = $pdo->lastInsertId();

// Insert questions
$stmt = $pdo->prepare("
    INSERT INTO form_questions (
        form_id, question_text, question_type, is_required, question_order,
        options, validation_rules, placeholder_text, min_value, max_value, step_value
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($input['questions'] as $question) {
    $stmt->execute([
        $form_id,
        $question['question_text'],
        $question['question_type'],
        $question['is_required'] ?? false,
        $question['question_order'],
        $question['options'],
        $question['validation_rules'],
        $question['placeholder_text'],
        $question['min_value'],
        $question['max_value'],
        $question['step_value']
    ]);
}

// Initialize analytics
$stmt = $pdo->prepare("
    INSERT INTO form_analytics (form_id, total_views, total_starts, total_completions)
    VALUES (?, 0, 0, 0)
");
$stmt->execute([$form_id]);

// Commit transaction
$pdo->commit();
```

**Transaction Behavior:**
- Begins database transaction (all-or-nothing execution)
- Inserts form record with all settings
- Gets newly inserted form ID
- Inserts all questions in a loop
- Initializes analytics record with zero counts
- Commits all changes atomically
- If any step fails, entire transaction rolls back

---

## APPENDIX C: Question Management Code Snippets

### Create Question with Permission Check
**File:** `admin/api/custom_forms/create_question.php` (Lines 78-91)  
**Behavior:** Verifies form ownership before allowing question creation

```php
// Verify form exists and user has permission
$stmt = $pdo->prepare("SELECT created_by FROM custom_forms WHERE form_id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

if (!$form) {
    throw new Exception('Form not found');
}

// Check if user is the creator
$user_id = $_SESSION['user_id'];
if ($form['created_by'] != $user_id) {
    throw new Exception('You do not have permission to add questions to this form');
}
```

**Permission Behavior:**
- Queries database for form by form_id
- Checks if form exists
- Compares form creator with current user
- Throws exception if user is not the creator
- Prevents unauthorized question additions

---

### Auto-Calculate Question Order
**File:** `admin/api/custom_forms/create_question.php` (Lines 93-97)  
**Behavior:** Automatically assigns next sequential order to new questions

```php
// Get the next question order
$stmt = $pdo->prepare("SELECT MAX(question_order) as max_order FROM form_questions WHERE form_id = ?");
$stmt->execute([$form_id]);
$result = $stmt->fetch();
$next_order = ($result['max_order'] ?? 0) + 1;
```

**Ordering Behavior:**
- Queries for maximum question_order in the form
- Adds 1 to get next order value
- Handles NULL case (first question gets order 1)
- Ensures questions maintain proper sequence

---

### Update Question with Type Validation
**File:** `admin/api/custom_forms/update_question.php` (Lines 44-74)  
**Behavior:** Validates question type and verifies creator permission

```php
$allowed_types = [
    'text', 'textarea', 'radio', 'checkbox', 'dropdown',
    'number', 'email', 'date', 'time', 'rating_stars',
    'rating_scale', 'slider'
];

if (!in_array($question_type, $allowed_types, true)) {
    throw new Exception('Unsupported question type provided');
}

$user_id = $_SESSION['user_id'];

// Verify permission
$stmt = $pdo->prepare("
    SELECT fq.question_id, fq.form_id, cf.created_by, a.position
    FROM form_questions fq
    JOIN custom_forms cf ON fq.form_id = cf.form_id
    LEFT JOIN admins a ON cf.created_by = a.user_id
    WHERE fq.question_id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    throw new Exception('Question not found');
}

// Only creator can update
if ($question['created_by'] != $user_id) {
    throw new Exception('You do not have permission to update this question');
}
```

**Update Behavior:**
- Validates question type against whitelist of 12 allowed types
- Prevents invalid question types from being saved
- Queries for question and its associated form
- Verifies current user is the form creator
- Throws exception if user lacks permission
- Uses strict type checking in validation

---

### Delete Question with Cascade and Reordering
**File:** `admin/api/custom_forms/delete_question.php` (Lines 64-93)  
**Behavior:** Deletes question, removes associated answers, and reorders remaining questions

```php
// Delete any existing answers for this question first
$stmt = $pdo->prepare("DELETE FROM form_answers WHERE question_id = ?");
$stmt->execute([$question_id]);
$deleted_answers = $stmt->rowCount();

// Delete the question
$stmt = $pdo->prepare("DELETE FROM form_questions WHERE question_id = ?");
$stmt->execute([$question_id]);

if ($stmt->rowCount() === 0) {
    throw new Exception('Question could not be deleted');
}

// Reorder remaining questions to fill the gap
$stmt = $pdo->prepare("
    SELECT question_id, question_order 
    FROM form_questions 
    WHERE form_id = ? 
    ORDER BY question_order ASC
");
$stmt->execute([$question['form_id']]);
$remaining_questions = $stmt->fetchAll();

// Update question order
$new_order = 1;
foreach ($remaining_questions as $remaining_question) {
    $stmt = $pdo->prepare("UPDATE form_questions SET question_order = ? WHERE question_id = ?");
    $stmt->execute([$new_order, $remaining_question['question_id']]);
    $new_order++;
}
```

**Deletion Behavior:**
- Deletes all form_answers associated with the question (cascade delete)
- Counts deleted answers for logging
- Deletes the question record
- Verifies deletion was successful (rowCount > 0)
- Fetches all remaining questions in order
- Reassigns sequential order numbers (1, 2, 3, etc.)
- Maintains question sequence integrity after deletion
