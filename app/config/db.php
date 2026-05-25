<?php
// app/config/db.php
require_once __DIR__ . '/../includes/env.php';

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$name = $_ENV['DB_NAME'] ?? 'department_system';

if ($host === 'localhost') {
    $host = '127.0.0.1';
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    // Handle error quietly
} else {
    $conn->set_charset('utf8mb4');
}