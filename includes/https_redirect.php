<?php
/**
 * HTTPS Redirect and Security Headers
 * Forces HTTPS connection and adds security headers
 * Author: Cascade AI Assistant
 * Date: October 24, 2025
 */

// Check if SSL is available on the server
function isSSLAvailable() {
    // Check if HTTPS is already active
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        return true;
    }
    
    // Check if SSL port is available (basic check)
    $host = $_SERVER['HTTP_HOST'];
    $port = 443;
    
    // Simple socket test (with timeout to prevent hanging)
    $connection = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        return true;
    }
    
    return false;
}

// Force HTTPS redirect
function forceHTTPS() {
    // Check if not already HTTPS
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        // Only redirect if SSL is available
        if (isSSLAvailable()) {
            // Get the current URL
            $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            
            // Redirect to HTTPS
            header("Location: $redirectURL", true, 301);
            exit();
        }
    }
}

// Add security headers
function addSecurityHeaders() {
    // Strict Transport Security (HSTS)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    
    // Prevent content type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Frame options (prevent clickjacking)
    header('X-Frame-Options: SAMEORIGIN');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api.qrserver.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https: blob:; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api.qrserver.com;");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Remove server signature
    header_remove('X-Powered-By');
}

// Apply HTTPS and security
function enableHTTPSSecurity($forceRedirect = true) {
    if ($forceRedirect) {
        forceHTTPS();
    }
    addSecurityHeaders();
}

// Auto-apply if this file is included
if (!defined('HTTPS_SECURITY_APPLIED')) {
    define('HTTPS_SECURITY_APPLIED', true);
    // Disable HTTPS redirect for localhost development
    // Enable only security headers, no HTTPS redirect
    $isLocalhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:80', '127.0.0.1:80']);
    enableHTTPSSecurity(!$isLocalhost); // Only force HTTPS on production
}
?>
