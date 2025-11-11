<?php
session_start();

// Clear unified session variables (new system)
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['full_name']);
unset($_SESSION['email']);
unset($_SESSION['role']);
unset($_SESSION['logged_in']);

// Clear legacy frontend session variables (old system)
unset($_SESSION['frontend_user_id']);
unset($_SESSION['frontend_username']);
unset($_SESSION['frontend_full_name']);
unset($_SESSION['frontend_email']);
unset($_SESSION['frontend_logged_in']);

// Unset all session variables and destroy the session
session_unset();
session_destroy();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Redirect to landing page (index.html) with cache-busting query
header("Location: ../index.html?logout=1&ts=" . time());
exit();
?>
