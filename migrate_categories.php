<?php
require_once __DIR__ . '/app/config/db.php';

$sql1 = "CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$sql2 = "ALTER TABLE tasks ADD COLUMN category_id INT NULL AFTER deadline";
$sql3 = "ALTER TABLE tasks ADD FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL";

if ($conn->query($sql1) === TRUE && $conn->query($sql2) === TRUE && $conn->query($sql3) === TRUE) {
    echo "Task categories system initialized successfully";
} else {
    echo "Error updating table: " . $conn->error;
}

$conn->close();
?>