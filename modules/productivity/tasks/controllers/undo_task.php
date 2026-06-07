<?php
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/middleware/auth.php';

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];

    $stmt = $conn->prepare("UPDATE tasks SET status = 'todo', completed_at = NULL WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();

    $redirect = isset($_GET['redirect']) && $_GET['redirect'] === 'assigned' ? 'assigned_tasks.php' : 'tasks.php';
    $base = isset($_GET['redirect']) && $_GET['redirect'] === 'assigned'
        ? '$base_path/academics/assigned_tasks'
        : '$base_path/productivity/index';
    header("Location: " . $base . $redirect);
    exit();
}
