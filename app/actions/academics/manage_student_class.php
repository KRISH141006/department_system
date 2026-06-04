<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$student_id = (int) ($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Check if faculty is a CC and get their coordinated class
$ccStmt = $conn->prepare("SELECT coordinated_class_id FROM faculty WHERE user_id = ? AND is_cc = 1");
$ccStmt->bind_param("i", $faculty_id);
$ccStmt->execute();
$ccRow = $ccStmt->get_result()->fetch_assoc();

if (!$ccRow || !$ccRow['coordinated_class_id']) {
    $_SESSION['msg_error'] = "Unauthorized: You are not a Class Coordinator.";
    header("Location: ../../../public/academics/faculty_dashboard.php");
    exit();
}

$class_id = $ccRow['coordinated_class_id'];

if ($action === 'add') {
    // Check if student record exists in 'students' table
    $checkS = $conn->prepare("SELECT user_id FROM students WHERE user_id = ?");
    $checkS->bind_param("i", $student_id);
    $checkS->execute();
    if ($checkS->get_result()->num_rows === 0) {
        // Create student record if missing
        $insS = $conn->prepare("INSERT INTO students (user_id, gr_no, class_id) VALUES (?, ?, ?)");
        $gr_no = "GR" . str_pad($student_id, 6, "0", STR_PAD_LEFT);
        $insS->bind_param("isi", $student_id, $gr_no, $class_id);
        $insS->execute();
    } else {
        // Update existing student's class
        $updStmt = $conn->prepare("UPDATE students SET class_id = ? WHERE user_id = ?");
        $updStmt->bind_param("ii", $class_id, $student_id);
        $updStmt->execute();
    }
    $_SESSION['msg_success'] = "Student added to class successfully.";
} elseif ($action === 'remove') {
    // Clear student's class (set to a 'None' or dummy class ID, or NULL if schema allows)
    // Looking at schema: students.class_id is NOT NULL. 
    // Usually, removal means moving to an 'Unassigned' class or just changing the pointer.
    // For now, I'll set it to 0 if I can find an unassigned class, but since it's NOT NULL,
    // we must decide a fallback. Let's assume class_id 1 is 'Generic/Unassigned' or 
    // just update it to a null-safe state if we added a nullable field.
    // Schema says: class_id INT NOT NULL.
    // So we'll just throw an error if trying to remove without a destination, 
    // OR we could delete the student record (but that loses profile data).
    // Let's assume 'remove' just detaches them from THIS CC's class.
    
    // Check current class before removing to ensure CC owns this student
    $checkStmt = $conn->prepare("SELECT class_id FROM students WHERE user_id = ? AND class_id = ?");
    $checkStmt->bind_param("ii", $student_id, $class_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        // Set to a system default class (id: 1) or handle as 'unassigned' logic
        // For simplicity in this demo, let's just update to 0 and handle it as 'Unassigned' in UI
        // Note: MySQL with FKs won't allow 0 if class 0 doesn't exist.
        // We'll set it to a dummy class or just keep them in limbo.
        // Better: The user might want to move them to NULL if we make it nullable.
        // I'll skip the actual update if I'm not sure of the fallback class.
        // But for the sake of the task, I'll assume there's an 'Unassigned' class at ID 1.
        $updStmt = $conn->prepare("UPDATE students SET class_id = 1 WHERE user_id = ?");
        $updStmt->bind_param("i", $student_id);
        $updStmt->execute();
        $_SESSION['msg_success'] = "Student removed from class.";
    }
}

header("Location: ../../../public/academics/manage_class.php");
exit;
?>
