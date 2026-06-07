<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/public/dashboard.php");
    exit();
}

$conn->begin_transaction();

try {
    // 1. Fetch all classes that can be promoted (Sem 1 to 7)
    $stmt = $conn->query("SELECT id, name, semester FROM classes WHERE semester < 8");
    $classes = $stmt->fetch_all(MYSQLI_ASSOC);

    foreach ($classes as $class) {
        $old_sem = (int)$class['semester'];
        $new_sem = $old_sem + 1;
        
        $old_name = $class['name'];
        // Replace the leading semester number with the new one
        $pure_name = preg_replace('/^\d+/', '', $old_name);
        $new_name = $new_sem . $pure_name;

        $upd = $conn->prepare("UPDATE classes SET semester = ?, name = ? WHERE id = ?");
        $upd->bind_param("isi", $new_sem, $new_name, $class['id']);
        $upd->execute();
    }

    // 2. CC logic from original profiles is removed as those columns (cc_semester, cc_class) 
    // no longer exist in the normalized V1 schema. 
    // CC status is now a simple boolean in the 'faculty' table.

    $conn->commit();
    $_SESSION['msg_success'] = "Semester transition completed successfully! Classes promoted.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error during transition: " . $e->getMessage();
}

header("Location: $base_path/public/dashboard.php");
exit();
