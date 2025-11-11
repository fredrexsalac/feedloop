<?php
/**
 * Favicon Include
 * Add this to all pages to show FeedLoop logo in browser tab
 */

// Determine the base path for the favicon
$favicon_base_path = './';

// Normalize path for various sections
if (strpos($_SERVER['PHP_SELF'], '/homepage/') !== false) {
    $favicon_base_path = '../';
} elseif (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $favicon_base_path = '../';
} elseif (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    // If inside an admin subdirectory (e.g., /admin/dashboard_admin/...), we need ../../
    $afterAdmin = substr($_SERVER['PHP_SELF'], strpos($_SERVER['PHP_SELF'], '/admin/') + strlen('/admin/'));
    if ($afterAdmin !== false && strpos($afterAdmin, '/') !== false) {
        $favicon_base_path = '../../';
    } else {
        $favicon_base_path = '../';
    }
} elseif (strpos($_SERVER['PHP_SELF'], '/auth/') !== false) {
    $favicon_base_path = '../';
} elseif (strpos($_SERVER['PHP_SELF'], '/public/') !== false) {
    $favicon_base_path = '../../';
}
?>
<!-- FeedLoop Favicon -->
<link rel="icon" type="image/jpeg" href="<?php echo $favicon_base_path; ?>assets/img/logo/feedloop.jpg">
<link rel="shortcut icon" type="image/jpeg" href="<?php echo $favicon_base_path; ?>assets/img/logo/feedloop.jpg">
<link rel="apple-touch-icon" href="<?php echo $favicon_base_path; ?>assets/img/logo/feedloop.jpg">
