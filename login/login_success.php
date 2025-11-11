<?php
session_start();

// Check if coming from login
if (!isset($_SESSION['login_redirect'])) {
    header('Location: unified_login.php');
    exit();
}

// Get session data
$redirect_url = $_SESSION['login_redirect'];
$username = $_SESSION['login_username'];

// Check if user is super admin based on position
$is_super_admin = ($_SESSION['position'] ?? '') === 'Super Admin';
$role = $is_super_admin ? 'super_admin' : ($_SESSION['role'] ?? 'admin');

// Clear the temporary session data
unset($_SESSION['login_redirect']);
unset($_SESSION['login_username']);
// Don't unset login_role here as we're using the main session role
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Successful - FeedLoop</title>
    <link rel="stylesheet" href="../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/login/login_modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Role-based background colors */
        body[data-role="super_admin"] { 
            background-color: #FF8C00; /* Orange */
            color: white;
        }
        body[data-role="admin"] { 
            background-color: #00b894; /* Green */
            background-color: #9acd32; /* Yellow-Green */
            color: #333;
        }
        
        /* Fade to black animation */
        @keyframes fadeToBlack {
        }
        
        body.fade-out {
            animation: fadeToBlack 0.5s ease-out forwards;
        }
        
        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
    </style>
    <script>
        // Store session data in localStorage for better MS Edge compatibility
        sessionStorage.setItem("user_id", "<?php echo $_SESSION['user_id']; ?>");
        sessionStorage.setItem("username", "<?php echo htmlspecialchars($username); ?>");
        sessionStorage.setItem("role", "<?php echo htmlspecialchars($role); ?>");
        
        document.addEventListener('DOMContentLoaded', function() {
            const modalContent = document.querySelector('.modal-content');
            const countdownElement = document.getElementById("countdown");
            let countdown = 3;
            
            // Show initial count immediately
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            
            // Start countdown after slide-in animation
            setTimeout(() => {
                const timer = setInterval(() => {
                    countdown--;
                    
                    // Update the countdown display
                    if (countdownElement) {
                        countdownElement.textContent = countdown;
                    }
                    
                    // When countdown reaches 0, start fade out and slide out
                    if (countdown <= 0) {
                        clearInterval(timer);
                        // Start fade out effect
                        document.body.classList.add('fade-out');
                        // Then slide out modal
                        setTimeout(() => {
                            modalContent.classList.add('slide-out');
                            // Redirect after slide out completes
                            setTimeout(() => {
                                window.location.href = "<?php echo htmlspecialchars($redirect_url); ?>";
                            }, 500);
                        }, 500);
                    }
                }, 1000); // Update every second
            }, 500); // Small delay to ensure smooth animation
        });
    </script>
</head>
<body data-role="<?php echo htmlspecialchars($role); ?>">
    <!-- Debug output - remove after testing -->
    <div style="display:none;">
        Session Role: <?php var_dump($_SESSION['role']); ?>
        Login Role: <?php var_dump($role); ?>
    </div>
    <!-- Login Success Modal -->
    <div id="loginSuccessModal" class="modal" style="display: flex;">
        <div class="modal-content">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>Welcome to Your
                <?php 
                $roleDisplay = [
                    'super_admin' => 'Super Admin',
                    'admin' => 'Admin'
                ][$role] ?? 'Account';
                echo $roleDisplay;
                ?> Account
            </h2>
            <p>Login successful. Redirecting to dashboard...</p>
            <div class="countdown">
                Redirecting in <span id="countdown" style="display:inline-block; min-width:1em; text-align:center;">3</span> seconds...
            </div>
        </div>
    </div>
</body>
</html>
