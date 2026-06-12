<?php
require_once __DIR__ . '/shared/config/db.php';
$sql = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT 'male' AFTER role";
if ($conn->query($sql)) {
    echo "Column added successfully";
} else {
    echo "Error or column already exists: " . $conn->error;
}
unlink(__FILE__);
