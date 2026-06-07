<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $base_path/public/academics/continuous_feedback.php");
    exit();
}

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/public/dashboard.php");
    exit();
}

$faculty_id   = (int) ($_POST['faculty_id']   ?? 0);
$subject_id   = (int) ($_POST['subject_id']   ?? 0);
$feedback_text = trim($_POST['feedback_text'] ?? '');

if (!$faculty_id || empty($feedback_text)) {
    $_SESSION['msg_error'] = "Faculty and Feedback text are required.";
    header("Location: $base_path/public/academics/continuous_feedback.php");
    exit();
}

try {
    // 1. Ensure table exists (Safety check in case SQL wasn't imported)
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS continuous_feedback (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            faculty_id    INT NOT NULL,
            subject_id    INT NULL,
            feedback_text TEXT NOT NULL,
            created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_cf_faculty_v2 FOREIGN KEY (faculty_id) REFERENCES faculty(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_cf_subject_v2 FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->query($createTableQuery);

    // 2. Prepare query
    $query = "INSERT INTO continuous_feedback (faculty_id, subject_id, feedback_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    // 3. Bind parameters
    // Handle optional subject_id
    $subject_val = ($subject_id > 0) ? $subject_id : NULL;
    $stmt->bind_param("iis", $faculty_id, $subject_val, $feedback_text);

    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "Thank you! Your anonymous feedback has been submitted.";
    } else {
        throw new Exception("Execute error: " . $stmt->error);
    }

} catch (Exception $e) {
    $_SESSION['msg_error'] = "Error: " . $e->getMessage();
}

header("Location: $base_path/public/academics/continuous_feedback.php");
exit();
?>
