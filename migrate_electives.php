<?php
require_once __DIR__ . '/app/includes/env.php';

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$name = $_ENV['DB_NAME'] ?? 'department_system';

$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "<h2>Cleaning Up Elective System...</h2>";

// 1. Ensure is_elective exists
$check1 = $conn->query("SHOW COLUMNS FROM faculty_subjects LIKE 'is_elective'");
if ($check1->num_rows == 0) {
    $conn->query("ALTER TABLE faculty_subjects ADD COLUMN is_elective TINYINT DEFAULT 0");
    echo "✅ Added 'is_elective' to faculty_subjects.<br>";
}

// 2. Ensure status column exists in student_electives
$check2 = $conn->query("SHOW COLUMNS FROM student_electives LIKE 'status'");
if ($check2->num_rows == 0) {
    $conn->query("ALTER TABLE student_electives ADD COLUMN status ENUM('pending', 'enrolled', 'rejected') DEFAULT 'pending'");
    echo "✅ Added 'status' to student_electives.<br>";
}

// 3. Optional: Remove the problematic column if it was accidentally created
$check3 = $conn->query("SHOW COLUMNS FROM faculty_subjects LIKE 'enrollment_closed'");
if ($check3->num_rows > 0) {
    $conn->query("ALTER TABLE faculty_subjects DROP COLUMN enrollment_closed");
    echo "🧹 Removed unused 'enrollment_closed' column.<br>";
}

echo "<br><strong>System Cleaned!</strong> You can now save subjects without errors.";
