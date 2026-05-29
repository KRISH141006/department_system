<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];

    $stmt = $conn->prepare("UPDATE tasks SET is_completed = 1 WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();

    $redirect = isset($_GET['redirect']) && $_GET['redirect'] === 'assigned' ? 'assigned_tasks.php' : 'tasks.php';
    header("Location: ../../../public/productivity/" . $redirect);
    exit();
}
?>