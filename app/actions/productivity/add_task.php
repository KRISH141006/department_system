<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task = $_POST['task'];
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $user_id = $_SESSION['user_id'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $priority_id = !empty($_POST['priority_id']) ? $_POST['priority_id'] : null;
    $new_category_name = !empty($_POST['new_category']) ? trim($_POST['new_category']) : null;

    // Handle new category creation
    if ($new_category_name) {
        // Check if category already exists for this user to avoid duplicates
        $check_stmt = $conn->prepare("SELECT id FROM task_categories WHERE user_id = ? AND name = ?");
        $check_stmt->bind_param("is", $user_id, $new_category_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($row = $check_result->fetch_assoc()) {
            $category_id = $row['id'];
        } else {
            $cat_stmt = $conn->prepare("INSERT INTO task_categories (user_id, name) VALUES (?, ?)");
            $cat_stmt->bind_param("is", $user_id, $new_category_name);
            $cat_stmt->execute();
            $category_id = $cat_stmt->insert_id;
        }
    }

    $stmt = $conn->prepare("INSERT INTO tasks (user_id, task, deadline, category_id, priority_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $user_id, $task, $deadline, $category_id, $priority_id);
    $stmt->execute();

    header("Location: ../../../public/productivity/tasks.php");
    exit();
}
?>