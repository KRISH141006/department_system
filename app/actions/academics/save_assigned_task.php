<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_id = $_SESSION['user_id'];
    $class_name = $_POST['class_name'];
    $semester = (int) $_POST['semester'];
    $pac_category = $_POST['pac_category'];
    $task_name = $_POST['task_name'];
    $task_details = $_POST['task_details'];
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    $resource_path = null;
    $resource_name = null;

    // Handle File Upload
    if (isset($_FILES['resource']) && $_FILES['resource']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../../public/uploads/resources/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $resource_name = $_FILES['resource']['name'];
        $file_ext = pathinfo($resource_name, PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['resource']['tmp_name'], $target_file)) {
            $resource_path = 'uploads/resources/' . $file_name;
        }
    }

    // 1. Save to faculty_assignments for history
    $stmt = $conn->prepare("INSERT INTO faculty_assignments (faculty_id, task_name, task_details, class_name, semester, pac_category, deadline, resource_path, resource_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssissss", $faculty_id, $task_name, $task_details, $class_name, $semester, $pac_category, $deadline, $resource_path, $resource_name);
    $stmt->execute();
    $assignment_id = $stmt->insert_id;

    // 2. Filter students
    $query = "SELECT id FROM users WHERE role = 'student' AND class_name = ? AND semester = ?";
    if ($pac_category !== 'all') {
        $query .= " AND pac_category = ?";
    }

    $filter_stmt = $conn->prepare($query);
    if ($pac_category !== 'all') {
        $filter_stmt->bind_param("sis", $class_name, $semester, $pac_category);
    } else {
        $filter_stmt->bind_param("si", $class_name, $semester);
    }
    
    $filter_stmt->execute();
    $students = $filter_stmt->get_result();
    $assigned_count = 0;

    // 3. Assign tasks to each student
    $task_stmt = $conn->prepare("INSERT INTO tasks (user_id, task, description, deadline, faculty_assignment_id, resource_path) VALUES (?, ?, ?, ?, ?, ?)");
    
    while ($student = $students->fetch_assoc()) {
        $student_id = $student['id'];
        $task_stmt->bind_param("isssis", $student_id, $task_name, $task_details, $deadline, $assignment_id, $resource_path);
        $task_stmt->execute();
        $assigned_count++;
    }

    if ($assigned_count > 0) {
        header("Location: ../../../public/academics/assign_task.php?success=1&count=" . $assigned_count);
    } else {
        header("Location: ../../../public/academics/assign_task.php?error=" . urlencode("No students found matching the criteria."));
    }
    exit();
}
?>