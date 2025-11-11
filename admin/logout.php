<?php
session_start();

// Check if this is an AJAX request for logout confirmation
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'true') {
    // Clear the user's activity status in the database
    if (isset($_SESSION['user_id'])) {
        // Try to include db.php from the root directory first, then fallback to parent directory
        $dbPath = __DIR__ . '/../db.php';
        if (!file_exists($dbPath)) {
            $dbPath = __DIR__ . '/../../db.php';
        }
        
        if (file_exists($dbPath)) {
            require_once $dbPath;
            try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET last_activity = NULL,
                    session_id = NULL 
                WHERE user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            error_log("Cleared activity status for user " . $_SESSION['user_id'] . " on logout");
        } catch (Exception $e) {
                error_log("Error clearing activity status on logout: " . $e->getMessage());
            }
        } else {
            error_log("Database connection file not found at: " . $dbPath);
        }
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Return success response for AJAX
    echo json_encode(['success' => true, 'redirect' => '../login/unified_login.php']);
    exit();
}

// If not AJAX request, show logout page with modal
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: background 2s ease-in-out;
        }

        body.background-black {
            background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
            transition: background 2s ease-in-out;
        }

        body.background-green {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            transition: background 1.5s ease-in-out;
        }

        .logout-modal {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            animation: modalSlideIn 0.5s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes modalSlideOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
        }

        .logout-icon {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .logout-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .logout-message {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .countdown {
            font-size: 1.2rem;
            color: #ff6b6b;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .countdown-number {
            display: inline-block;
            animation: countdownPulse 1s ease-in-out infinite;
        }

        @keyframes countdownPulse {
            0%, 100% { transform: scale(1); color: #ff6b6b; }
            50% { transform: scale(1.2); color: #ff4757; }
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ff6b6b;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-out {
            animation: modalSlideOut 0.5s ease-in forwards;
        }
    </style>
</head>
<body>
    <div class="logout-modal" id="logoutModal">
        <div class="logout-icon">👋</div>
        <h2 class="logout-title">Logging Out</h2>
        <p class="logout-message">Thank you for using FeedLoop Admin Panel!</p>
        <div class="countdown">
            Redirecting in <span class="countdown-number" id="countdown">3</span> seconds...
        </div>
        <div class="spinner"></div>
    </div>

    <script src="../assets/js/admin/logout.js"></script>
</body>
</html>
