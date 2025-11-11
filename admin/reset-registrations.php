<?php
session_start();

// Simple reset utility for development
// In production, this should be more secure or done via database

$reset_key = $_GET['key'] ?? '';
$valid_reset_key = 'FEEDLOOP_RESET_REGISTRATIONS_2024';

if ($reset_key === $valid_reset_key) {
    // Reset all registration flags
    unset($_SESSION['admin_registered']);
    unset($_SESSION['feedback_registered']);
    unset($_SESSION['super_registered']);
    unset($_SESSION['security_questions']);
    
    $message = "✅ All registration slots have been reset and are now available again.";
    $success = true;
} else {
    $message = "❌ Invalid reset key.";
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Registrations - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>🔧 Registration Reset Utility</h2>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($reset_key) || $reset_key === ''): ?>
            <p>This utility resets all registration slots to make them available again.</p>
            <p><strong>Usage:</strong> <code>reset-registrations.php?key=RESET_KEY</code></p>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn btn-primary">← Back to Admin Portal</a>
        </div>
        
        <?php if ($success ?? false): ?>
            <div style="margin-top: 15px; font-size: 0.9rem; color: #666;">
                <strong>Registration Status:</strong><br>
                🟢 Admin: Available<br>
                🟠 Super Admin: Available
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
