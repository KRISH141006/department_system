<?php
require_once __DIR__ . '/app/config/db.php';

$sql1 = "CREATE TABLE IF NOT EXISTS task_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#000000',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// First check if priority_id column already exists to avoid errors on re-run
$check_sql = "SHOW COLUMNS FROM tasks LIKE 'priority_id'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    $sql2 = "ALTER TABLE tasks ADD COLUMN priority_id INT NULL AFTER category_id";
    $sql3 = "ALTER TABLE tasks ADD CONSTRAINT fk_priority FOREIGN KEY (priority_id) REFERENCES task_priorities(id) ON DELETE SET NULL";
} else {
    $sql2 = "SELECT 1"; // No-op
    $sql3 = "SELECT 1"; // No-op
}

if ($conn->query($sql1) === TRUE) {
    echo "Table task_priorities created successfully.\n";
} else {
    echo "Error creating table task_priorities: " . $conn->error . "\n";
}

if (isset($sql2)) {
    if ($conn->query($sql2) === TRUE) {
        echo "Column priority_id added to tasks successfully.\n";
    } else {
        echo "Error adding priority_id: " . $conn->error . "\n";
    }

    if ($conn->query($sql3) === TRUE) {
        echo "Foreign key for priority_id added successfully.\n";
    } else {
        echo "Error adding foreign key for priority_id: " . $conn->error . "\n";
    }
} else {
    echo "Priority column already exists.\n";
}

$conn->close();
?>
