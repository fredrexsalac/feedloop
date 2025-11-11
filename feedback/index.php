<?php
session_start();
require '../db.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['frontend_logged_in']) && $_SESSION['frontend_logged_in'];

if (!$is_logged_in) {
    header("Location: ../frontend_login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Pre-fill user information
$user_name = $_SESSION['frontend_full_name'] ?? '';
$user_email = $_SESSION['frontend_email'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_anonymous = isset($_POST['anonymous']) ? true : false;
    
    if ($is_anonymous) {
        // Generate anonymous username with random numbers
        $random_numbers = mt_rand(100000, 999999);
        $name = "anonymous" . $random_numbers;
        // Use the user's actual email for backend purposes
        $email = $user_email;
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
    }
    
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $feedback_category = $_POST['feedback_category'] ?? '';
    $user_type = $_POST['user_type'] ?? 'student';
    
    // Basic validation - Category is now required
    if (empty($feedback_category)) {
        $error_message = "Please select a feedback category.";
    } elseif (($is_anonymous && (empty($subject) || empty($message))) || 
        (!$is_anonymous && (empty($name) || empty($email) || empty($subject) || empty($message)))) {
        $error_message = "All fields are required.";
    } elseif (!$is_anonymous && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Add frontend_user_id and user_type columns if they don't exist
            try {
                $pdo->exec("ALTER TABLE feedback_submissions ADD COLUMN frontend_user_id INT NULL AFTER id");
            } catch (Exception $e) {
                // Column might already exist, continue
            }
            
            try {
                $pdo->exec("ALTER TABLE feedback_submissions ADD COLUMN user_type VARCHAR(20) DEFAULT 'student' AFTER category");
            } catch (Exception $e) {
                // Column might already exist, continue
            }
            
            // Add feedback_category column if it doesn't exist
            try {
                $pdo->exec("ALTER TABLE feedback_submissions ADD COLUMN feedback_category VARCHAR(255) NOT NULL DEFAULT 'System Feedback' AFTER category");
            } catch (Exception $e) {
                // Column might already exist, continue
            }
            
            // Insert feedback into database with new category system (no status field)
            $frontend_user_id = $_SESSION['frontend_user_id'];
            $stmt = $pdo->prepare("INSERT INTO feedback_submissions (frontend_user_id, name, email, subject, message, feedback_category, user_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$frontend_user_id, $name, $email, $subject, $message, $feedback_category, $user_type]);
            
            $success_message = "Thank you for your feedback! Your submission has been securely recorded and anonymized. The appropriate department will review your feedback.";
            
            // Clear form data
            $name = $email = $subject = $message = '';
            $feedback_category = '';
            $user_type = 'student';
            
        } catch (Exception $e) {
            error_log("Feedback submission error: " . $e->getMessage());
            $error_message = "Sorry, there was an error submitting your feedback. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background 0.5s ease;
        }
        
        /* Student theme - Blue background */
        body.student-theme {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        /* Teacher theme - Yellow background */
        body.teacher-theme {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .feedback-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .feedback-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 50px;
        }
        
        .feedback-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .feedback-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .feedback-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .feedback-form {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #fff;
            text-decoration: underline;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .category-option {
            position: relative;
        }
        
        .category-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .category-option label {
            display: block;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: 500;
        }
        
        .category-option input[type="radio"]:checked + label {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        /* User type selection styles */
        .user-type-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .user-type-option {
            position: relative;
        }
        
        .user-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .user-type-option label {
            display: block;
            padding: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            background: white;
        }
        
        .user-type-option input[type="radio"]:checked + label {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: scale(1.02);
        }
        
        .user-type-option label:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
    </style>
</head>
<body>
    <div class="feedback-container">
        <div class="feedback-card">
            <div class="feedback-header">
                <h1><i class="fas fa-comments"></i> Submit Feedback</h1>
                <p>We value your input! Share your thoughts, suggestions, or report issues.</p>
            </div>
            
            <div class="feedback-form">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($name ?? $user_name); ?>" 
                               placeholder="Enter your full name" required>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="anonymous" name="anonymous">
                            <label class="form-check-label" for="anonymous">Submit anonymously</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($email ?? $user_email); ?>" 
                               placeholder="Enter your email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tag"></i> I am a
                        </label>
                        <div class="user-type-grid">
                            <div class="user-type-option">
                                <input type="radio" id="student" name="user_type" value="student" 
                                       <?php echo ($user_type ?? 'student') === 'student' ? 'checked' : ''; ?>>
                                <label for="student">
                                    <i class="fas fa-graduation-cap"></i><br>Student
                                </label>
                            </div>
                            <div class="user-type-option">
                                <input type="radio" id="teacher" name="user_type" value="teacher"
                                       <?php echo ($user_type ?? '') === 'teacher' ? 'checked' : ''; ?>>
                                <label for="teacher">
                                    <i class="fas fa-chalkboard-teacher"></i><br>Teacher
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FeedLoop v2.0: New Categorized Feedback System -->
                    <div class="form-group">
                        <label for="feedback_category" class="form-label">
                            <i class="fas fa-tags"></i> Feedback Category <span class="text-danger">*</span>
                        </label>
                        <select id="feedback_category" name="feedback_category" class="form-control" required>
                            <option value="">-- Select a Category --</option>
                            <option value="Department Feedback" <?php echo ($feedback_category ?? '') === 'Department Feedback' ? 'selected' : ''; ?>>
                                Department Feedback
                            </option>
                            <option value="Instructor Feedback" <?php echo ($feedback_category ?? '') === 'Instructor Feedback' ? 'selected' : ''; ?>>
                                Instructor Feedback
                            </option>
                            <option value="Event Feedback" <?php echo ($feedback_category ?? '') === 'Event Feedback' ? 'selected' : ''; ?>>
                                Event Feedback
                            </option>
                            <option value="Dean/Office Feedback" <?php echo ($feedback_category ?? '') === 'Dean/Office Feedback' ? 'selected' : ''; ?>>
                                Dean/Office Feedback
                            </option>
                            <option value="System Feedback" <?php echo ($feedback_category ?? '') === 'System Feedback' ? 'selected' : ''; ?>>
                                System Feedback
                            </option>
                            <option value="Community-Based Issues" <?php echo ($feedback_category ?? '') === 'Community-Based Issues' ? 'selected' : ''; ?>>
                                Community-Based Issues
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            Select the category that best describes your feedback for proper routing to the appropriate department.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" class="form-label">
                            <i class="fas fa-heading"></i> Subject
                        </label>
                        <input type="text" id="subject" name="subject" class="form-control" 
                               value="<?php echo htmlspecialchars($subject ?? ''); ?>" 
                               placeholder="Brief description of your feedback" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">
                            <i class="fas fa-message"></i> Message
                        </label>
                        <textarea id="message" name="message" class="form-control" 
                                  placeholder="Please provide detailed information about your feedback..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>
        
        <div class="text-center">
            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Homepage
            </a>
        </div>
    </div>
    
    <!-- External JavaScript -->
    <script src="../assets/js/feedback/feedback_form.js"></script>
    <script src="../assets/js/feedback/anonymous_handler.js"></script>
</body>
</html>
