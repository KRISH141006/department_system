<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $task_id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE tasks SET status = 'todo', completed_at = NULL WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();

    if (isset($_GET['redirect']) && $_GET['redirect'] === 'assigned') {
        header("Location: $base_path/academics/assigned_tasks");
    } else {
        header("Location: $base_path/productivity/tasks");
    }
    exit();
}
