<?php
// shared/config/db.php
require_once __DIR__ . '/env.php';

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$name = $_ENV['DB_NAME'] ?? 'dept_system';
$port = $_ENV['DB_PORT'] ?? 3307;

if ($host === 'localhost') {
    $host = '127.0.0.1';
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    // Handle error quietly
} else {
    $conn->set_charset('utf8mb4');
}
