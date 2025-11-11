<?php
session_start();
require '../db.php';

$step = $_GET['step'] ?? 1;
$error_message = '';
$success_message = '';

// Security Questions
$security_questions = [
    "What is the name of your first pet?",
    "What city were you born in?", 
    "What is your mother's maiden name?"
];

// Step 1: Security Questions
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 1) {
    $answers = [];
    $all_answered = true;
    
    foreach ($security_questions as $index => $question) {
        $answer = trim($_POST["answer_$index"] ?? '');
        if (empty($answer)) {
            $all_answered = false;
            break;
        }
        $answers[] = strtolower($answer);
    }
    
    if ($all_answered) {
        $_SESSION['security_answers'] = $answers;
        header('Location: register.php?step=2');
        exit();
    } else {
        $error_message = "Please answer all security questions.";
    }
}

// Step 2: Registration Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 2) {
    if (!isset($_SESSION['security_answers'])) {
        header('Location: register.php?step=1');
        exit();
    }
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $position = 'Admin'; // Default to Admin
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = "Username already exists.";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = "Email already exists.";
                } else {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    // Insert into users table
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                    $stmt->execute([$username, $email, $hashed_password]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Insert into admins table
                    $stmt = $pdo->prepare("INSERT INTO admins (user_id, full_name, position) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $full_name, $position]);
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Clear session
                    unset($_SESSION['security_answers']);
                    
                    $success_message = "Admin account created successfully! You can now login.";
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $error_message = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 3px solid #28a745;
        }
        
        .register-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .register-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .register-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            display: block;
            text-align: center;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #fff;
            text-decoration: underline;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e1e5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: 600;
            color: #666;
        }
        
        .step.active {
            background: #28a745;
            color: white;
        }
        
        .step.completed {
            background: #20c997;
            color: white;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> Admin Registration</h1>
                <p><?php echo $step == 1 ? 'Security Verification' : 'Account Information'; ?></p>
            </div>
            
            <div class="register-form">
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <div style="margin-top: 15px; text-align: center;">
                            <a href="unified_login.php" class="btn-register" style="display: inline-block; width: auto; padding: 8px 20px; text-decoration: none;">
                                Go to Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($step == 1 && !$success_message): ?>
                    <h4 style="margin-bottom: 20px; color: #333;">Security Questions</h4>
                    <p style="color: #666; margin-bottom: 25px;">Please answer these security questions to proceed with registration.</p>
                    
                    <form method="POST" action="register.php?step=1">
                        <?php foreach ($security_questions as $index => $question): ?>
                            <div class="form-group">
                                <label for="answer_<?php echo $index; ?>" class="form-label">
                                    <?php echo ($index + 1) . ". " . $question; ?>
                                </label>
                                <input type="text" id="answer_<?php echo $index; ?>" name="answer_<?php echo $index; ?>" 
                                       class="form-control" required>
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" class="btn-register" id="continueBtn" disabled style="background-color: #6c757d !important; cursor: not-allowed;">
                            <i class="fas fa-arrow-right"></i> Continue to Registration
                        </button>
                    </form>
                    
                    <script>
                    // Validate security questions form
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.querySelector('form[action*="step=1"]');
                        if (form) {
                            const continueBtn = document.getElementById('continueBtn');
                            const inputs = form.querySelectorAll('input[type="text"]');
                            
                            // List of inappropriate words to filter (basic filter)
                            const inappropriateWords = [
                                'fuck', 'shit', 'bitch', 'ass', 'damn', 'hell', 'bastard', 'dick', 'pussy', 'cock',
                                'sex', 'porn', 'nude', 'rape', 'kill', 'murder', 'suicide', 'gore', 'blood', 'death',
                                'puta', 'gago', 'tangina', 'bobo', 'tanga', 'ulol', 'putangina', 'tarantado'
                            ];
                            
                            function containsInappropriateContent(text) {
                                const lowerText = text.toLowerCase();
                                return inappropriateWords.some(word => lowerText.includes(word));
                            }
                            
                            function validateForm() {
                                let allFilled = true;
                                let hasInappropriate = false;
                                
                                inputs.forEach(input => {
                                    const value = input.value.trim();
                                    
                                    // Check if empty
                                    if (value === '') {
                                        allFilled = false;
                                        input.style.borderColor = '';
                                    }
                                    // Check for inappropriate content
                                    else if (containsInappropriateContent(value)) {
                                        hasInappropriate = true;
                                        input.style.borderColor = '#dc3545';
                                        input.style.borderWidth = '2px';
                                    } else {
                                        input.style.borderColor = '#28a745';
                                        input.style.borderWidth = '2px';
                                    }
                                });
                                
                                // Enable button only if all filled and no inappropriate content
                                if (allFilled && !hasInappropriate) {
                                    continueBtn.disabled = false;
                                    continueBtn.style.setProperty('background-color', '#28a745', 'important');
                                    continueBtn.style.cursor = 'pointer';
                                } else {
                                    continueBtn.disabled = true;
                                    continueBtn.style.setProperty('background-color', '#6c757d', 'important');
                                    continueBtn.style.cursor = 'not-allowed';
                                }
                            }
                            
                            // Check on input
                            inputs.forEach(input => {
                                input.addEventListener('input', validateForm);
                                input.addEventListener('blur', validateForm);
                            });
                            
                            // Show warning on form submit
                            form.addEventListener('submit', function(e) {
                                let hasInappropriate = false;
                                inputs.forEach(input => {
                                    if (containsInappropriateContent(input.value.trim())) {
                                        hasInappropriate = true;
                                    }
                                });
                                
                                if (hasInappropriate) {
                                    e.preventDefault();
                                    alert('⚠️ Please remove inappropriate or offensive content from your answers.');
                                    return false;
                                }
                            });
                            
                            // Initial check
                            validateForm();
                        }
                    });
                    </script>
                <?php endif; ?>
                
                <?php if ($step == 2 && !$success_message): ?>
                    <h4 style="margin-bottom: 20px; color: #333;">Create Admin Account</h4>
                    
                    <form method="POST" action="register.php?step=2">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-check"></i> Create Admin Account
                        </button>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="register.php?step=1" style="color: #28a745; text-decoration: none;">
                                <i class="fas fa-arrow-left"></i> Back to Security Questions
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="unified_login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
</body>
</html>
