<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../../public/dashboard.php");
    exit();
}

// 1. Fetch all students who have a semester assigned
$stmt = $conn->prepare("SELECT id, semester, class_name FROM users WHERE role = 'student' AND semester IS NOT NULL");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->begin_transaction();

try {
    foreach ($students as $student) {
        $old_sem = (int)$student['semester'];
        $new_sem = $old_sem + 1;
        
        // If semester > 8, they might be graduated, but for now we just increment.
        if ($new_sem > 8) continue;

        $old_class = $student['class_name'];
        // Replace the semester digit in class name (e.g., 4EK1 -> 5EK1)
        // We assume the class name starts with the semester number
        $new_class = preg_replace('/^\d+/', $new_sem, $old_class);

        $upd = $conn->prepare("UPDATE users SET semester = ?, class_name = ? WHERE id = ?");
        $upd->bind_param("isi", $new_sem, $new_class, $student['id']);
        $upd->execute();
    }

    // 2. Also update CC roles in profiles if they are tied to a class/semester
    // Usually CC moves with the batch or stays? The user said "students will be redirected to the next sem"
    // If a CC was for 4EK1, they should now be CC for 5EK1 to follow their students.
    $ccStmt = $conn->prepare("SELECT user_id, cc_semester, cc_class FROM profiles WHERE is_cc = 1");
    $ccStmt->execute();
    $ccs = $ccStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($ccs as $cc) {
        $old_sem = (int)$cc['cc_semester'];
        $new_sem = $old_sem + 1;
        if ($new_sem > 8) {
            // Remove CC status if they passed 8th sem?
            $updCC = $conn->prepare("UPDATE profiles SET is_cc = 0, cc_semester = NULL, cc_class = NULL WHERE user_id = ?");
            $updCC->bind_param("i", $cc['user_id']);
            $updCC->execute();
            continue;
        }
        $new_class = preg_replace('/^\d+/', $new_sem, $cc['cc_class']);

        $updCC = $conn->prepare("UPDATE profiles SET cc_semester = ?, cc_class = ? WHERE user_id = ?");
        $updCC->bind_param("isi", $new_sem, $new_class, $cc['user_id']);
        $updCC->execute();
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Semester transition completed successfully! Students moved to next semester.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error during transition: " . $e->getMessage();
}

header("Location: ../../../public/dashboard.php");
exit();
?>
