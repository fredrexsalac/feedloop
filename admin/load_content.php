<?php
session_start();
require '../db.php';

// Check if user is logged in and is an admin (any admin role)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit();
}

$page = $_GET['page'] ?? '';

switch ($page) {
    case 'manage_admins':
        include 'content/user_management/manage_admins_content.php';
        break;
    case 'view_feedback':
        include 'content/feedback_management/view_feedback_content.php';
        break;
    case 'custom_forms':
        include 'content/custom_forms/custom_forms_content.php';
        break;
    
    // FeedLoop v2.0: Category-specific feedback pages
    case 'feedback_management/department_feedback':
        include 'content/feedback_management/department_feedback.php';
        break;
    case 'feedback_management/instructor_feedback':
        include 'content/feedback_management/instructor_feedback.php';
        break;
    case 'feedback_management/event_feedback':
        include 'content/feedback_management/event_feedback.php';
        break;
    case 'feedback_management/system_feedback':
        include 'content/feedback_management/system_feedback.php';
        break;
    
    case 'settings':
        include 'content/system_settings/settings_content.php';
        break;
    default:
        echo '<div class="alert alert-warning">Page not found.</div>';
}
?>
