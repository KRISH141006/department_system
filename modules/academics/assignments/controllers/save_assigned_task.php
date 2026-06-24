<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';
require_once __DIR__ . '/../../../../shared/helpers/notifications.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

ensure_notifications_table($conn);

$faculty_id = (int) $_SESSION['user_id'];
$class_subject_id = (int) ($_POST['class_subject_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$deadline = $_POST['deadline'] ?? '';
$allowed_formats = trim($_POST['allowed_formats'] ?? '');

if (!$class_subject_id || empty($title) || empty($deadline)) {
    $_SESSION['msg_error'] = "Missing assignment details.";
    header("Location: $base_path/academics/assign_task");
    exit();
}

$current_time = time();
$deadline_time = strtotime($deadline);
if ($deadline_time === false || $deadline_time <= $current_time) {
    $_SESSION['msg_error'] = "The assignment deadline must be a future date and time.";
    header("Location: $base_path/academics/assign_task");
    exit();
}

$max_files = (int) ($_POST['max_files'] ?? 1);
if ($max_files < 1) {
    $max_files = 1;
}

// Handle Multiple File Uploads
$uploaded_resources = [];
if (isset($_FILES['resource_files']) && is_array($_FILES['resource_files']['name'])) {
    $upload_dir = realpath(__DIR__ . '/../../../../uploads/') . '/resources/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    for ($i = 0; $i < count($_FILES['resource_files']['name']); $i++) {
        if ($_FILES['resource_files']['error'][$i] === UPLOAD_ERR_OK) {
            $orig_name = basename($_FILES['resource_files']['name'][$i]);
            $file_ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['resource_files']['tmp_name'][$i], $target_file)) {
                $uploaded_resources[] = [
                    'path' => 'uploads/resources/' . $file_name,
                    'name' => $_FILES['resource_files']['name'][$i]
                ];
            }
        }
    }
}

// Populate legacy columns for backward compatibility
$resource_path = !empty($uploaded_resources) ? $uploaded_resources[0]['path'] : null;
$resource_name = !empty($uploaded_resources) ? $uploaded_resources[0]['name'] : null;

try {
    // 1. Insert into 'assignments' table
    $stmt = $conn->prepare("
        INSERT INTO assignments (faculty_id, class_subject_id, title, description, deadline, resource_path, resource_name, allowed_formats, max_files, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissssssi", $faculty_id, $class_subject_id, $title, $description, $deadline, $resource_path, $resource_name, $allowed_formats, $max_files);
    
    if ($stmt->execute()) {
        $assignment_id = $conn->insert_id;
        
        // Insert all resources into assignment_resources table
        if (!empty($uploaded_resources)) {
            $ins_res = $conn->prepare("INSERT INTO assignment_resources (assignment_id, file_path, file_name) VALUES (?, ?, ?)");
            foreach ($uploaded_resources as $ur) {
                $ins_res->bind_param("iss", $assignment_id, $ur['path'], $ur['name']);
                $ins_res->execute();
            }
        }

        $context = notification_class_subject_context($conn, $class_subject_id);
        $subject_name = $context['subject_name'] ?? 'your subject';
        $class_name = $context['class_name'] ?? 'selected class';
        $semester = $context['semester'] ?? '';
        $subject_type = $context['subject_type'] ?? 'core';
        $audience_label = $subject_type === 'elective'
            ? 'enrolled elective students'
            : trim($class_name . ($semester !== '' ? " (Sem $semester)" : ''));

        $student_ids = notification_student_ids_for_class_subject($conn, $class_subject_id);
        if (empty($student_ids) && $subject_type === 'elective') {
            $student_ids = notification_student_ids_for_class_subject($conn, $class_subject_id, true);
        }

        $deadline_label = date('d M, h:i A', strtotime($deadline));
        $student_notification_count = create_notifications(
            $conn,
            $student_ids,
            'assignment_posted',
            "New task: $title",
            "Subject: $subject_name. For: $audience_label. Deadline: $deadline_label.",
            "$base_path/academics/view_assigned_task?id=$assignment_id",
            $faculty_id
        );

        create_notification(
            $conn,
            $faculty_id,
            'assignment_published',
            "Task published: $title",
            "$subject_name assignment published for $audience_label. Student notifications sent: $student_notification_count.",
            "$base_path/academics/submissions?assignment_id=$assignment_id",
            $faculty_id
        );
        
        $_SESSION['msg_success'] = "Assignment published successfully. Notifications sent to $student_notification_count student" . ($student_notification_count === 1 ? "." : "s.");
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Failed to save assignment: " . $e->getMessage();
}

header("Location: $base_path/academics/assigned_tasks_history");
exit;
?>
