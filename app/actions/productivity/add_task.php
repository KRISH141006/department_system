<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task = $_POST['task'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO tasks (user_id, task) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $task);
    $stmt->execute();

    header("Location: ../../../public/productivity/tasks.php");
    exit();
}
?>