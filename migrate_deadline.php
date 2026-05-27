<?php
require_once __DIR__ . '/app/config/db.php';

$sql = "ALTER TABLE tasks ADD COLUMN deadline DATETIME NULL AFTER task";

if ($conn->query($sql) === TRUE) {
    echo "Table tasks updated successfully";
} else {
    echo "Error updating table: " . $conn->error;
}

$conn->close();
?>