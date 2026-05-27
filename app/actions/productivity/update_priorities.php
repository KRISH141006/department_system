<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $priorities = $_POST['priorities']; // Array of [id => [name, color]]

    foreach ($priorities as $id => $data) {
        $name = trim($data['name']);
        $color = trim($data['color']);
        
        $stmt = $conn->prepare("UPDATE task_priorities SET name = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $name, $color, $id, $user_id);
        $stmt->execute();
    }

    header("Location: ../../../public/productivity/tasks.php?success=Priorities updated");
    exit();
}
?>
