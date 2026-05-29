<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'];
    $task_id = (int)$_POST['task_id'];
    
    $submission_path = null;
    $submission_name = null;

    // Handle File Upload
    if (isset($_FILES['submission']) && $_FILES['submission']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../../public/uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $submission_name = $_FILES['submission']['name'];
        $file_ext = pathinfo($submission_name, PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['submission']['tmp_name'], $target_file)) {
            $submission_path = 'uploads/submissions/' . $file_name;
        } else {
            die("File upload failed.");
        }
    } else {
        die("No file uploaded or upload error.");
    }

    // 1. Save to student_submissions
    $stmt = $conn->prepare("INSERT INTO student_submissions (task_id, student_id, submission_path, submission_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $task_id, $student_id, $submission_path, $submission_name);
    $stmt->execute();

    // 2. Mark task as completed
    $update_stmt = $conn->prepare("UPDATE tasks SET is_completed = 1 WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("ii", $task_id, $student_id);
    $update_stmt->execute();

    header("Location: ../../../public/productivity/view_assigned_task.php?id=" . $task_id . "&submitted=1");
    exit();
}
?>