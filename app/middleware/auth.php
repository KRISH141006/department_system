<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $proj_root = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
    $base_path = str_replace($doc_root, '', $proj_root);
    $base_path = '/' . ltrim(str_replace('\\', '/', $base_path), '/');
    $base_path = rtrim($base_path, '/');
    
    header("Location: $base_path/public/login.php");
    exit();
}

/**
 * Check if the current user has a specific permission
 * @param string $permission The name of the permission to check
 * @return bool
 */
if (!function_exists('has_permission')) {
    function has_permission($permission) {
        return isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions']);
    }
}
