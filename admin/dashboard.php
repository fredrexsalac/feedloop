<?php
/**
 * Redirect from old dashboard to new admin dashboard
 */
session_start();

// Redirect to new admin dashboard location
header('Location: dashboard_admin/admin_dashboard.php');
exit();
