<?php
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'];
    $assignment_id = (int)$_POST['task_id'];
    
    // Fetch assignment details for validation
    $stmt = $conn->prepare("SELECT allowed_formats, max_files FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    if (!$assignment) {
        $_SESSION['msg_error'] = "Assignment not found.";
        header("Location: $base_path/academics/assigned_tasks");
        exit();
    }

    $max_files = (int)($assignment['max_files'] ?? 1);
    if ($max_files < 1) {
        $max_files = 1;
    }

    if (!isset($_FILES['submission']) || !is_array($_FILES['submission']['name']) || empty($_FILES['submission']['name'][0])) {
        $_SESSION['msg_error'] = "Please select at least one file to submit.";
        header("Location: $base_path/academics/view_assigned_task?id=" . $assignment_id);
        exit();
    }

    $file_count = count($_FILES['submission']['name']);
    if ($file_count > $max_files) {
        $_SESSION['msg_error'] = "You cannot upload more than " . $max_files . " file(s).";
        header("Location: $base_path/academics/view_assigned_task?id=" . $assignment_id);
        exit();
    }

    // Process allowed formats
    $allowed_exts = [];
    $allowed_formats = trim($assignment['allowed_formats'] ?? '');
    if (!empty($allowed_formats)) {
        $parts = preg_split('/[\s,;\/|]+/', $allowed_formats);
        foreach ($parts as $part) {
            $clean = strtolower(trim($part, " ."));
            if (!empty($clean)) {
                $allowed_exts[] = $clean;
            }
        }
    }

    $uploaded_files = [];
    $upload_dir = realpath(__DIR__ . '/../../../../uploads/') . '/submissions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['submission']['error'][$i] !== UPLOAD_ERR_OK) {
            $_SESSION['msg_error'] = "File upload failed with error code: " . $_FILES['submission']['error'][$i];
            header("Location: $base_path/academics/view_assigned_task?id=" . $assignment_id);
            exit();
        }

        $orig_name = basename($_FILES['submission']['name'][$i]);
        $file_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        // Format extension check
        if (!empty($allowed_exts) && !in_array($file_ext, $allowed_exts)) {
            $_SESSION['msg_error'] = "Invalid file format for '" . htmlspecialchars($orig_name) . "'. Allowed formats: " . implode(', ', array_map('strtoupper', $allowed_exts));
            header("Location: $base_path/academics/view_assigned_task?id=" . $assignment_id);
            exit();
        }

        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['submission']['tmp_name'][$i], $target_file)) {
            $uploaded_files[] = [
                'path' => 'uploads/submissions/' . $file_name,
                'name' => $_FILES['submission']['name'][$i]
            ];
        } else {
            $_SESSION['msg_error'] = "File upload failed. Please check folder permissions and try again.";
            header("Location: $base_path/academics/view_assigned_task?id=" . $assignment_id);
            exit();
        }
    }

    // Populate legacy columns for backward compatibility
    $submission_path = !empty($uploaded_files) ? $uploaded_files[0]['path'] : null;
    $submission_name = !empty($uploaded_files) ? $uploaded_files[0]['name'] : null;

    $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, submission_path, submission_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $assignment_id, $student_id, $submission_path, $submission_name);
    
    if ($stmt->execute()) {
        $submission_id = $conn->insert_id;
        
        // Insert all files into submission_files table
        $ins_file = $conn->prepare("INSERT INTO submission_files (submission_id, file_path, file_name) VALUES (?, ?, ?)");
        foreach ($uploaded_files as $uf) {
            $ins_file->bind_param("iss", $submission_id, $uf['path'], $uf['name']);
            $ins_file->execute();
        }
        
        $_SESSION['msg_success'] = "Assignment submitted successfully.";
    } else {
        $_SESSION['msg_error'] = "Failed to record submission in database: " . $conn->error;
    }

    header("Location: $base_path/academics/view_assigned_task?id=" . $assignment_id . "&submitted=1");
    exit();
}
