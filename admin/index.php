<?php
// Redirect to unified login with directory-aware path handling
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if ($basePath === '' || $basePath === '.') {
    $basePath = '';
}
header('Location: ' . $basePath . '/unified_login.php');
exit();

// Security Questions Configuration - Randomized
$question_pools = [
    // System name questions
    [
        "What is the name of the feedback management system?" => "feedloop",
        "What is the brand name of this platform?" => "feedloop",
        "What is the system called?" => "feedloop"
    ],
    // Year questions  
    [
        "What year was this system developed?" => "2024",
        "In which year was FeedLoop created?" => "2024",
        "What is the development year of this platform?" => "2024"
    ],
    // Color/Theme questions - Randomized for all account types
    [
        "What is the primary color theme for regular admin accounts?" => "green",
        "What color represents the standard admin dashboard?" => "green",
        "Which color is used for regular admin interfaces?" => "green",
        "What color theme distinguishes regular admins?" => "green",
        "What is the primary color theme for super admin accounts?" => "orange",
        "What color represents the super admin dashboard?" => "orange",
        "Which color is used for super admin interfaces?" => "orange",
        "What color theme distinguishes super admins?" => "orange",
        "What is the primary color theme for student accounts?" => "blue",
        "What color represents the student dashboard?" => "blue",
        "Which color is used for student interfaces?" => "blue",
        "What color theme distinguishes student accounts?" => "blue"
    ]
];

// Generate or retrieve security questions from session
if (!isset($_SESSION['security_questions']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    // Randomly select one question from each pool
    $security_questions = [];
    foreach ($question_pools as $pool) {
        $random_key = array_rand($pool);
        $security_questions[$random_key] = $pool[$random_key];
    }
    $_SESSION['security_questions'] = $security_questions;
} else {
    // Use the questions from session for validation
    $security_questions = $_SESSION['security_questions'];
}

$show_registration_links = false;
$error_message = '';

// Check if security questions are being answered
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $all_correct = true;
    $debug_info = [];
    
    // Debug: Log all POST data
    error_log("DEBUG - All POST data: " . print_r($_POST, true));
    error_log("DEBUG - Session questions: " . print_r($security_questions, true));
    
    foreach ($security_questions as $question => $correct_answer) {
        $field_name = md5($question);
        $user_answer = trim(strtolower($_POST[$field_name] ?? ''));
        $expected = strtolower($correct_answer);
        
        $debug_info[] = [
            'question' => $question,
            'field_name' => $field_name,
            'expected' => $expected,
            'received' => $user_answer,
            'match' => ($user_answer === $expected)
        ];
        
        if ($user_answer !== $expected) {
            $all_correct = false;
        }
    }
    
    // Log detailed debug info
    error_log("DEBUG - Validation details: " . print_r($debug_info, true));
    error_log("DEBUG - All correct: " . ($all_correct ? 'YES' : 'NO'));
    
    if ($all_correct) {
        $show_registration_links = true;
        // Clear the session questions after successful validation
        unset($_SESSION['security_questions']);
        error_log("DEBUG - SUCCESS: Registration links should now show");
    } else {
        $error_message = "Incorrect answers. Access denied.";
        // Log failed attempt
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        error_log("Failed admin registration access attempt from IP: $ip at " . date('Y-m-d H:i:s'));
        
        // Clear session questions to generate new ones
        unset($_SESSION['security_questions']);
    }
}

// Check if user is already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard_admin/admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/admin/admin_access_portal.css">
</head>
<body>
    <div class="access-container">
        <div class="logo-container">
            <img src="../assets/img/logo/logo.jpg" alt="FeedLoop Logo" class="logo">
        </div>
        
        <h1 class="access-title">🔐 Admin Access Portal</h1>
        <p class="access-subtitle">Authorized Personnel Only</p>
        
        <?php if (!$show_registration_links): ?>
            <div class="security-notice">
                <strong>🛡️ Security Verification Required</strong><br>
                Answer the following questions to proceed.
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <strong>❌ Access Denied!</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- DEBUG: Show expected answers (remove in production) -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                <div class="alert alert-warning">
                    <strong>🐛 DEBUG MODE</strong><br>
                    Expected answers:<br>
                    <?php foreach ($security_questions as $question => $answer): ?>
                        <small><strong><?php echo htmlspecialchars($question); ?></strong><br>
                        Answer: <code><?php echo htmlspecialchars($answer); ?></code><br><br></small>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php foreach ($security_questions as $question => $answer): ?>
                    <div class="mb-3">
                        <label class="form-label"><?php echo htmlspecialchars($question); ?></label>
                        <input type="text" class="form-control" name="<?php echo md5($question); ?>" required>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn btn-verify">
                    🔍 Verify Access
                </button>
            </form>
        <?php else: ?>
            <div class="registration-links">
                <h4 style="color: var(--success-color); margin-bottom: 20px;">✅ Access Granted</h4>
                <p style="margin-bottom: 20px;">Choose registration type:</p>
                
                <?php
                // Check if registrations are still available (single-use system)
                $admin_available = !isset($_SESSION['admin_registered']) || $_SESSION['admin_registered'] !== true;
                $feedback_available = !isset($_SESSION['feedback_registered']) || $_SESSION['feedback_registered'] !== true;
                $super_available = !isset($_SESSION['super_registered']) || $_SESSION['super_registered'] !== true;
                ?>
                
                <?php if ($admin_available): ?>
                <a href="admin-registration.php?auth=<?php echo base64_encode('FL2024_ADMIN_REG_SECURE_TOKEN_XYZ789'); ?>&type=admin" class="reg-link">
                    🟢 Register Admin Account
                    <small style="display: block; margin-top: 5px; opacity: 0.8;">Standard administrative access (Green Theme)</small>
                </a>
                <?php else: ?>
                <div class="reg-link" style="opacity: 0.5; cursor: not-allowed; background: #f8f9fa;">
                    🟢 Admin Registration - <strong>Already Used</strong>
                    <small style="display: block; margin-top: 5px; opacity: 0.8;">This registration slot has been claimed</small>
                </div>
                <?php endif; ?>
                
                
                <?php if ($super_available): ?>
                <a href="super-admin-registration.php?auth=<?php echo base64_encode('FL2024_SUPER_ADMIN_REG_TOKEN_ABC456'); ?>&type=super" class="reg-link">
                    🟠 Register Super Admin
                    <small style="display: block; margin-top: 5px; opacity: 0.8;">Full system control (Orange Theme)</small>
                </a>
                <?php else: ?>
                <div class="reg-link" style="opacity: 0.5; cursor: not-allowed; background: #f8f9fa;">
                    🟠 Super Admin Registration - <strong>Already Used</strong>
                    <small style="display: block; margin-top: 5px; opacity: 0.8;">This registration slot has been claimed</small>
                </div>
                <?php endif; ?>
                
                <?php if (!$admin_available && !$feedback_available && !$super_available): ?>
                <div class="alert alert-warning" style="margin-top: 20px;">
                    <strong>⚠️ All Registration Slots Used</strong><br>
                    All available registration slots have been claimed. Contact the system administrator to reset registration availability.
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="login-link">
            <p><a href="login.php">🔐 Admin Login</a></p>
            <p><a href="../">← Back to Homepage</a></p>
        </div>
    </div>
</body>
</html>
