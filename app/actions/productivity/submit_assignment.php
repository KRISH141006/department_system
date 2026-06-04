<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'];
    // The POST variable might still be named 'task_id' in the frontend, 
    // but it refers to the assignment ID in the new schema.
    $assignment_id = (int)$_POST['task_id']; 
    
    $submission_path = null;
    $submission_name = null;

    // Handle File Upload
    if (isset($_FILES['submission']) && $_FILES['submission']['error'] == 0) {
        $upload_dir = realpath(__DIR__ . '/../../../public/uploads/') . '/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $submission_name = $_FILES['submission']['name'];
        $file_ext = strtolower(pathinfo($submission_name, PATHINFO_EXTENSION));
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['submission']['tmp_name'], $target_file)) {
            $submission_path = 'uploads/submissions/' . $file_name;
        } else {
            die("File upload failed. Could not move file to $target_file. Check directory permissions.");
        }
    } else {
        $err_code = $_FILES['submission']['error'] ?? 'No file';
        die("No file uploaded or upload error. Error Code: " . $err_code);
    }

    // 1. Save to submissions table (Normalized V1 Schema)
    $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, submission_path, submission_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $assignment_id, $student_id, $submission_path, $submission_name);
    $stmt->execute();

    // 2. We no longer update a separate 'tasks' record as assignments are decoupled.
    // Submission status is now inferred from the existence of a record in the 'submissions' table.

    header("Location: ../../../public/productivity/view_assigned_task.php?id=" . $assignment_id . "&submitted=1");
    exit();
}
?>
