<?php
/**
 * HTTPS Redirect and Security Headers
 * Forces HTTPS connection and adds security headers
 * Author: Cascade AI Assistant
 * Date: October 24, 2025
 */

// Determine if the current request is already using HTTPS (directly or via proxy)
function isSecureRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($forwardedProto !== '' && stripos($forwardedProto, 'https') !== false) {
        return true;
    }

    $forwardedSsl = $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '';
    if ($forwardedSsl !== '' && strtolower($forwardedSsl) === 'on') {
        return true;
    }

    return false;
}

// Force HTTPS redirect when appropriate
function forceHTTPS(): void {
    if (isSecureRequest()) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $redirectURL = 'https://' . $host . $uri;
    header("Location: $redirectURL", true, 301);
    exit();
}

// Decide whether HTTPS redirect should be applied (default disabled for proxy deployments)
function shouldForceHTTPSRedirect(): bool {
    if (defined('FEEDLOOP_FORCE_HTTPS')) {
        return (bool)FEEDLOOP_FORCE_HTTPS;
    }

    $envValue = getenv('FEEDLOOP_FORCE_HTTPS');
    if ($envValue !== false) {
        return filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
    }

    return false;
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
    enableHTTPSSecurity(shouldForceHTTPSRedirect());
}
?>
