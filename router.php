<?php
// FeedLoop PHP built-in server router
// Mimics Apache rewrite rules for clean URLs and directory indexes

$root = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Normalize double slashes
$uri = preg_replace('#/+#', '/', $uri);

// Serve existing files directly
$requestedPath = $root . $uri;
if ($uri !== '/' && file_exists($requestedPath) && !is_dir($requestedPath)) {
    return false; // use the built-in server handler
}

// Handle directory requests by serving index.php/index.html
if (is_dir($requestedPath)) {
    $indexPhp = rtrim($requestedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    $indexHtml = rtrim($requestedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';

    if (file_exists($indexPhp)) {
        require $indexPhp;
        return true;
    }

    if (file_exists($indexHtml)) {
        readfile($indexHtml);
        return true;
    }
}

// Attempt to route clean URLs to their .php counterparts
$candidate = $root . $uri . '.php';
if (file_exists($candidate)) {
    require $candidate;
    return true;
}

// Fallback to main index.php (acts as router/front controller)
require $root . '/index.php';
return true;
