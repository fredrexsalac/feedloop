<?php
/**
 * Public Form Submission Interface
 * Handles public access to custom feedback forms via QR codes and links
 * Author: Cascade AI Assistant
 * Date: October 19, 2025
 */

// Start session for tracking (but don't require login)
session_start();

// Include database connection
require_once '../../db.php';

// Get form code from URL
$form_code = $_GET['code'] ?? '';
if (empty($form_code)) {
    // Check if form code is in the URL path
    $path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $form_code = end($path_parts);
}

if (empty($form_code)) {
    http_response_code(404);
    include 'form_not_found.php';
    exit();
}

// Get form details
try {
    $stmt = $pdo->prepare("
        SELECT cf.*, 
               COUNT(fq.question_id) as question_count
        FROM custom_forms cf
        LEFT JOIN form_questions fq ON cf.form_id = fq.form_id
        WHERE cf.form_code = ? AND cf.is_active = 1
        GROUP BY cf.form_id
    ");
    $stmt->execute([$form_code]);
    $form = $stmt->fetch();
    
    if (!$form) {
        http_response_code(404);
        include 'form_not_found.php';
        exit();
    }
    
    // Check if form has expired
    if ($form['expires_at'] && strtotime($form['expires_at']) < time()) {
        include 'form_expired.php';
        exit();
    }
    
    // Check if form has reached response limit
    if ($form['max_responses'] && $form['response_count'] >= $form['max_responses']) {
        include 'form_limit_reached.php';
        exit();
    }
    
    // Get form questions
    $stmt = $pdo->prepare("
        SELECT * FROM form_questions 
        WHERE form_id = ? 
        ORDER BY question_order ASC
    ");
    $stmt->execute([$form['form_id']]);
    $questions = $stmt->fetchAll();
    
    // Update form analytics (view count)
    $stmt = $pdo->prepare("
        UPDATE form_analytics 
        SET total_views = total_views + 1 
        WHERE form_id = ?
    ");
    $stmt->execute([$form['form_id']]);
    
} catch (Exception $e) {
    error_log("Error loading form: " . $e->getMessage());
    http_response_code(500);
    include 'form_error.php';
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $errors = [];
        
        foreach ($questions as $question) {
            if ($question['is_required']) {
                $field_name = 'question_' . $question['question_id'];
                $value = $_POST[$field_name] ?? '';
                
                if (empty($value) || (is_array($value) && empty(array_filter($value)))) {
                    $errors[] = "Please answer: " . htmlspecialchars($question['question_text']);
                }
            }
        }
        
        if (empty($errors)) {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert form response
            $stmt = $pdo->prepare("
                INSERT INTO form_responses (
                    form_id, respondent_name, respondent_email, respondent_type,
                    ip_address, user_agent, submission_source
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $respondent_name = $_POST['respondent_name'] ?? null;
            $respondent_email = $_POST['respondent_email'] ?? null;
            $respondent_type = 'anonymous';
            
            // Determine respondent type based on email domain or other criteria
            if ($respondent_email) {
                if (strpos($respondent_email, '@student.') !== false) {
                    $respondent_type = 'student';
                } elseif (strpos($respondent_email, '@staff.') !== false) {
                    $respondent_type = 'staff';
                } else {
                    $respondent_type = 'guest';
                }
            }
            
            $submission_source = 'direct_link';
            if (isset($_POST['source']) && $_POST['source'] === 'qr_code') {
                $submission_source = 'qr_code';
            }
            
            $stmt->execute([
                $form['form_id'],
                $respondent_name,
                $respondent_email,
                $respondent_type,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $submission_source
            ]);
            
            $response_id = $pdo->lastInsertId();
            
            // Insert answers
            $stmt = $pdo->prepare("
                INSERT INTO form_answers (response_id, question_id, answer_text, answer_value, selected_options)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($questions as $question) {
                $field_name = 'question_' . $question['question_id'];
                $value = $_POST[$field_name] ?? null;
                
                if ($value !== null && $value !== '') {
                    $answer_text = null;
                    $answer_value = null;
                    $selected_options = null;
                    
                    switch ($question['question_type']) {
                        case 'rating_stars':
                        case 'rating_scale':
                        case 'slider':
                        case 'number':
                            $answer_value = is_numeric($value) ? (float)$value : null;
                            $answer_text = (string)$value;
                            break;
                            
                        case 'checkbox':
                            if (is_array($value)) {
                                $selected_options = json_encode($value);
                                $answer_text = implode(', ', $value);
                            }
                            break;
                            
                        case 'radio':
                        case 'dropdown':
                            $answer_text = (string)$value;
                            $selected_options = json_encode([$value]);
                            break;
                            
                        default:
                            $answer_text = (string)$value;
                            break;
                    }
                    
                    $stmt->execute([
                        $response_id,
                        $question['question_id'],
                        $answer_text,
                        $answer_value,
                        $selected_options
                    ]);
                }
            }
            
            // Update form response count
            $stmt = $pdo->prepare("
                UPDATE custom_forms 
                SET response_count = response_count + 1 
                WHERE form_id = ?
            ");
            $stmt->execute([$form['form_id']]);
            
            // Update analytics
            $stmt = $pdo->prepare("
                UPDATE form_analytics 
                SET total_completions = total_completions + 1,
                    last_response_at = NOW()
                WHERE form_id = ?
            ");
            $stmt->execute([$form['form_id']]);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to thank you page
            header("Location: thank_you.php?code=" . urlencode($form_code));
            exit();
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        error_log("Error submitting form: " . $e->getMessage());
        $errors[] = "An error occurred while submitting your response. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?> - FeedLoop</title>
    
    <!-- Bootstrap CSS -->
    <link href="../../assets/css/homepage/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Form Styles -->
    <link rel="stylesheet" href="../../assets/css/public/form_styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="form-container">
            <!-- Form Header -->
            <div class="form-header">
                <h1><?php echo htmlspecialchars($form['title']); ?></h1>
                <?php if ($form['description']): ?>
                    <p><?php echo htmlspecialchars($form['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Form Body -->
            <div class="form-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="feedback-form">
                    <!-- Respondent Information (if not anonymous) -->
                    <?php if (!$form['allow_anonymous']): ?>
                        <div class="question-card">
                            <div class="question-text">
                                <i class="fas fa-user me-2"></i>Your Information
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name <span class="required-indicator">*</span></label>
                                    <input type="text" class="form-control" name="respondent_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="required-indicator">*</span></label>
                                    <input type="email" class="form-control" name="respondent_email" required>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="question-card">
                            <div class="question-text">
                                <i class="fas fa-user me-2"></i>Your Information (Optional)
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="respondent_name" placeholder="Leave blank to remain anonymous">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="respondent_email" placeholder="Leave blank to remain anonymous">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form Questions -->
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-text">
                                <span class="question-number"><?php echo $index + 1; ?></span>
                                <?php echo htmlspecialchars($question['question_text']); ?>
                                <?php if ($question['is_required']): ?>
                                    <span class="required-indicator">*</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php
                            $field_name = 'question_' . $question['question_id'];
                            $options = json_decode($question['options'] ?? '{}', true);
                            $validation = json_decode($question['validation_rules'] ?? '{}', true);
                            ?>
                            
                            <?php switch ($question['question_type']): 
                                case 'text': ?>
                                    <input type="text" 
                                           class="form-control" 
                                           name="<?php echo $field_name; ?>"
                                           placeholder="<?php echo htmlspecialchars($question['placeholder_text'] ?? ''); ?>"
                                           <?php echo $question['is_required'] ? 'required' : ''; ?>
                                           <?php echo isset($validation['max_length']) ? 'maxlength="' . $validation['max_length'] . '"' : ''; ?>>
                                    <?php break;
                                    
                                case 'textarea': ?>
                                    <textarea class="form-control" 
                                              name="<?php echo $field_name; ?>"
                                              rows="4"
                                              placeholder="<?php echo htmlspecialchars($question['placeholder_text'] ?? ''); ?>"
                                              <?php echo $question['is_required'] ? 'required' : ''; ?>
                                              <?php echo isset($validation['max_length']) ? 'maxlength="' . $validation['max_length'] . '"' : ''; ?>></textarea>
                                    <?php break;
                                    
                                case 'radio': ?>
                                    <?php if (isset($options['options'])): ?>
                                        <?php foreach ($options['options'] as $optIndex => $option): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" 
                                                       type="radio" 
                                                       name="<?php echo $field_name; ?>" 
                                                       value="<?php echo htmlspecialchars($option); ?>"
                                                       id="<?php echo $field_name . '_' . $optIndex; ?>"
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                <label class="form-check-label" for="<?php echo $field_name . '_' . $optIndex; ?>">
                                                    <?php echo htmlspecialchars($option); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php break;
                                    
                                case 'checkbox': ?>
                                    <?php if (isset($options['options'])): ?>
                                        <?php foreach ($options['options'] as $optIndex => $option): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="<?php echo $field_name; ?>[]" 
                                                       value="<?php echo htmlspecialchars($option); ?>"
                                                       id="<?php echo $field_name . '_' . $optIndex; ?>">
                                                <label class="form-check-label" for="<?php echo $field_name . '_' . $optIndex; ?>">
                                                    <?php echo htmlspecialchars($option); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php break;
                                    
                                case 'dropdown': ?>
                                    <select class="form-select" 
                                            name="<?php echo $field_name; ?>"
                                            <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                        <option value="">Choose an option...</option>
                                        <?php if (isset($options['options'])): ?>
                                            <?php foreach ($options['options'] as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>">
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <?php break;
                                    
                                case 'rating_stars': ?>
                                    <div class="rating-stars" data-field="<?php echo $field_name; ?>" data-max="<?php echo $options['max_rating'] ?? 5; ?>">
                                        <?php for ($i = 1; $i <= ($options['max_rating'] ?? 5); $i++): ?>
                                            <span class="star" data-value="<?php echo $i; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="<?php echo $field_name; ?>" <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                    <?php break;
                                    
                                case 'rating_scale': ?>
                                    <div class="slider-container">
                                        <input type="range" 
                                               class="slider" 
                                               name="<?php echo $field_name; ?>"
                                               min="<?php echo $question['min_value'] ?? 1; ?>"
                                               max="<?php echo $question['max_value'] ?? 10; ?>"
                                               step="<?php echo $question['step_value'] ?? 1; ?>"
                                               value="<?php echo ($question['min_value'] + $question['max_value']) / 2; ?>"
                                               oninput="updateSliderValue(this)"
                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span><?php echo $question['min_value'] ?? 1; ?></span>
                                            <span id="<?php echo $field_name; ?>_value"><?php echo ($question['min_value'] + $question['max_value']) / 2; ?></span>
                                            <span><?php echo $question['max_value'] ?? 10; ?></span>
                                        </div>
                                    </div>
                                    <?php break;
                                    
                                case 'slider': ?>
                                    <div class="slider-container">
                                        <input type="range" 
                                               class="slider" 
                                               name="<?php echo $field_name; ?>"
                                               min="<?php echo $question['min_value'] ?? 0; ?>"
                                               max="<?php echo $question['max_value'] ?? 100; ?>"
                                               step="<?php echo $question['step_value'] ?? 1; ?>"
                                               value="<?php echo ($question['min_value'] + $question['max_value']) / 2; ?>"
                                               oninput="updateSliderValue(this)"
                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span><?php echo $question['min_value'] ?? 0; ?></span>
                                            <span id="<?php echo $field_name; ?>_value"><?php echo ($question['min_value'] + $question['max_value']) / 2; ?></span>
                                            <span><?php echo $question['max_value'] ?? 100; ?></span>
                                        </div>
                                    </div>
                                    <?php break;
                                    
                                case 'email': ?>
                                    <input type="email" 
                                           class="form-control" 
                                           name="<?php echo $field_name; ?>"
                                           placeholder="<?php echo htmlspecialchars($question['placeholder_text'] ?? 'Enter email address'); ?>"
                                           <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                    <?php break;
                                    
                                case 'number': ?>
                                    <input type="number" 
                                           class="form-control" 
                                           name="<?php echo $field_name; ?>"
                                           placeholder="<?php echo htmlspecialchars($question['placeholder_text'] ?? ''); ?>"
                                           <?php echo $question['is_required'] ? 'required' : ''; ?>
                                           <?php echo $question['min_value'] !== null ? 'min="' . $question['min_value'] . '"' : ''; ?>
                                           <?php echo $question['max_value'] !== null ? 'max="' . $question['max_value'] . '"' : ''; ?>
                                           <?php echo $question['step_value'] !== null ? 'step="' . $question['step_value'] . '"' : ''; ?>>
                                    <?php break;
                                    
                                case 'date': ?>
                                    <input type="date" 
                                           class="form-control" 
                                           name="<?php echo $field_name; ?>"
                                           <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                    <?php break;
                                    
                                case 'time': ?>
                                    <input type="time" 
                                           class="form-control" 
                                           name="<?php echo $field_name; ?>"
                                           <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                    <?php break;
                                    
                            endswitch; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Hidden field to track submission source -->
                    <input type="hidden" name="source" value="<?php echo isset($_GET['qr']) ? 'qr_code' : 'direct_link'; ?>">
                    
                    <!-- Submit Button -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="footer-info">
                <p class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Your responses are secure and will be used to improve our services.
                    <br>
                    Powered by <strong>FeedLoop</strong> - Advanced Feedback Management System
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <!-- Form Interactions JS -->
    <script src="../../assets/js/public/form_interactions.js"></script>
</body>
</html>
