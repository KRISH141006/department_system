<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../../../public/dashboard.php");
    exit();
}

$perms_data = $_POST['perms'] ?? [];
$roles_to_manage = ['student', 'faculty', 'expert'];

try {
    $conn->begin_transaction();

    // 1. Clear existing permissions for managed roles
    $stmt_del = $conn->prepare("DELETE FROM role_permissions WHERE role = ?");
    foreach ($roles_to_manage as $role) {
        $stmt_del->bind_param("s", $role);
        $stmt_del->execute();
    }

    // 2. Insert new permission mappings
    $stmt_ins = $conn->prepare("INSERT INTO role_permissions (role, permission_id) VALUES (?, ?)");
    foreach ($perms_data as $role => $permission_ids) {
        if (in_array($role, $roles_to_manage)) {
            foreach ($permission_ids as $pid) {
                $p_id = (int)$pid;
                $stmt_ins->bind_param("si", $role, $p_id);
                $stmt_ins->execute();
            }
        }
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Role permissions updated successfully. Changes will take effect on next login.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg_error'] = "Error updating permissions: " . $e->getMessage();
}

header("Location: ../../../public/admin/manage_permissions.php");
exit();
