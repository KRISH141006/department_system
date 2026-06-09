<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $base_path/admin/manage_cc");
    exit();
}

$faculty_post = $_POST['faculty'] ?? [];

try {
    $conn->begin_transaction();

    // Fetch all existing faculty user IDs to identify updates vs insertions
    $all_faculty = [];
    $res = $conn->query("SELECT user_id FROM faculty");
    while ($row = $res->fetch_assoc()) {
        $all_faculty[] = (int)$row['user_id'];
    }

    // Fetch all faculty users from the users table to validate they actually have the faculty role
    $valid_faculty_ids = [];
    $vRes = $conn->query("SELECT id FROM users WHERE role = 'faculty'");
    while ($row = $vRes->fetch_assoc()) {
        $valid_faculty_ids[] = (int)$row['id'];
    }

    // CC assignment validation
    $assigned_classes = [];
    foreach ($valid_faculty_ids as $fid) {
        $data = $faculty_post[$fid] ?? [];
        $is_cc = isset($data['is_cc']) ? 1 : 0;
        $coordinated_class_id = $is_cc && !empty($data['coordinated_class_id']) ? (int)$data['coordinated_class_id'] : null;

        if ($is_cc) {
            if (!$coordinated_class_id) {
                // Fetch faculty name
                $uStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                $uStmt->bind_param("i", $fid);
                $uStmt->execute();
                $name = $uStmt->get_result()->fetch_assoc()['name'] ?? 'Faculty';
                $uStmt->close();
                throw new Exception("First select class for $name.");
            }

            if (isset($assigned_classes[$coordinated_class_id])) {
                // Duplicate class in the request itself
                $cStmt = $conn->prepare("SELECT name, semester, branch FROM classes WHERE id = ?");
                $cStmt->bind_param("i", $coordinated_class_id);
                $cStmt->execute();
                $cRow = $cStmt->get_result()->fetch_assoc();
                $className = $cRow ? ($cRow['name'] . ' (Sem ' . $cRow['semester'] . ' - ' . $cRow['branch'] . ')') : 'Selected Class';
                $cStmt->close();
                throw new Exception("The class \"$className\" is assigned to multiple Class Coordinators. Each class can have only one CC.");
            }
            $assigned_classes[$coordinated_class_id] = $fid;
        }
    }

    // Check database for duplicate assignments with users not in the POST request
    if (!empty($assigned_classes)) {
        foreach ($assigned_classes as $class_id => $fid) {
            $checkStmt = $conn->prepare("
                SELECT u.name 
                FROM faculty f 
                JOIN users u ON f.user_id = u.id 
                WHERE f.is_cc = 1 AND f.coordinated_class_id = ? AND f.user_id != ?
            ");
            $checkStmt->bind_param("ii", $class_id, $fid);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($checkRes) {
                $cStmt = $conn->prepare("SELECT name, semester, branch FROM classes WHERE id = ?");
                $cStmt->bind_param("i", $class_id);
                $cStmt->execute();
                $cRow = $cStmt->get_result()->fetch_assoc();
                $className = $cRow ? ($cRow['name'] . ' (Sem ' . $cRow['semester'] . ' - ' . $cRow['branch'] . ')') : 'Selected Class';
                $cStmt->close();
                throw new Exception("The class \"$className\" is already assigned to another coordinator (" . $checkRes['name'] . ").");
            }
        }
    }

    foreach ($valid_faculty_ids as $fid) {
        // If they were submitted in the form, parse their values; otherwise default to non-CC
        $data = $faculty_post[$fid] ?? [];
        $is_cc = isset($data['is_cc']) ? 1 : 0;
        $coordinated_class_id = $is_cc && !empty($data['coordinated_class_id']) ? (int)$data['coordinated_class_id'] : null;

        if (in_array($fid, $all_faculty)) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE faculty SET is_cc = ?, coordinated_class_id = ? WHERE user_id = ?");
            $stmt->bind_param("iii", $is_cc, $coordinated_class_id, $fid);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new faculty record with temporary emp_id if none exists
            $emp_id = "EMP" . str_pad($fid, 4, "0", STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO faculty (user_id, emp_id, is_cc, coordinated_class_id, teaching_interests) VALUES (?, ?, ?, ?, '')");
            $stmt->bind_param("isii", $fid, $emp_id, $is_cc, $coordinated_class_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();
    $_SESSION['admin_cc_success'] = "Class Coordinators updated successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['admin_cc_error'] = "System Error: " . $e->getMessage();
}

header("Location: $base_path/admin/manage_cc");
exit();
