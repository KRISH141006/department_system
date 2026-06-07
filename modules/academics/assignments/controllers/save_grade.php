<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$submission_id = (int) ($_POST['submission_id'] ?? 0);
$assignment_id = (int) ($_POST['assignment_id'] ?? 0);
$grade         = trim($_POST['grade']         ?? '');
$feedback      = trim($_POST['feedback']      ?? '');

if (!$submission_id || empty($grade)) {
    $_SESSION['msg_error'] = "Missing grade or submission ID.";
    header("Location: $base_path/academics/submissions?assignment_id=$assignment_id");
    exit();
}

try {
    // Update submission with grade and feedback - Updated to new 'submissions' table
    $stmt = $conn->prepare("UPDATE submissions SET grade = ?, feedback = ?, graded_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Grade and feedback saved successfully.";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: $base_path/academics/submissions?assignment_id=$assignment_id");
exit;
?>
