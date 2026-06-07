<?php
session_start();

// Load the routing map
$routes = require_once __DIR__ . '/routes.php';

// Determine the base path of the project (e.g., /php/department_system)
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$proj_root = str_replace('\\', '/', __DIR__);
$base_path = str_replace($doc_root, '', $proj_root);
$base_path = '/' . ltrim($base_path, '/');
$base_path = rtrim($base_path, '/');

// Parse the request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base path from the request URI to get the relative path
if (strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Ensure the request URI starts with a slash
$request_uri = '/' . ltrim($request_uri, '/');

// Redirect root to dashboard or login
if ($request_uri === '/' || $request_uri === '/index.php') {
    if (isset($_SESSION['user_id'])) {
        header("Location: $base_path/dashboard");
    } else {
        header("Location: $base_path/login");
    }
    exit;
}

// Match the route
if (isset($routes[$request_uri])) {
    $target_file = __DIR__ . '/' . ltrim($routes[$request_uri], '/');
    if (file_exists($target_file)) {
        require_once $target_file;
        exit;
    } else {
        http_response_code(500);
        echo "Module file not found: " . htmlspecialchars($target_file);
        exit;
    }
}

// If no route matched, send 404
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($request_uri);
exit;
