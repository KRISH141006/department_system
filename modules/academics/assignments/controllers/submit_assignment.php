<?php
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'];
    $assignment_id = (int)$_POST['task_id'];
    
    $submission_path = null;
    $submission_name = null;

    if (isset($_FILES['submission']) && $_FILES['submission']['error'] == 0) {
        $upload_dir = realpath(__DIR__ . '/../../../../public/uploads/') . '/submissions/';
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

    $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, submission_path, submission_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $assignment_id, $student_id, $submission_path, $submission_name);
    $stmt->execute();

    header("Location: $base_path/public/academics/view_assigned_task.php?id=" . $assignment_id . "&submitted=1");
    exit();
}
