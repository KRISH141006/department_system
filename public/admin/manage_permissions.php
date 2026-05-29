<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Manage Role Permissions";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch all permissions
$all_perms = $conn->query("SELECT * FROM permissions ORDER BY permission_name ASC")->fetch_all(MYSQLI_ASSOC);

// Define roles to manage (excluding admin as admin always has all perms)
$roles = ['student', 'faculty', 'expert'];

// Fetch current role permissions
$role_perms = [];
$rp_query = $conn->query("SELECT role, permission_id FROM role_permissions");
while ($row = $rp_query->fetch_assoc()) {
    $role_perms[$row['role']][] = $row['permission_id'];
}
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Rights Management</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Configure which modules and actions each role can access across the system.</p>

        <form action="../../app/actions/admin/save_permissions.php" method="POST">
            <div class="card" style="padding: 0; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 600px;">
                    <thead style="background: var(--bg-2); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="padding: 1.25rem 2rem; color: var(--text-3); font-weight: 500;">Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th style="padding: 1.25rem; color: var(--text-3); font-weight: 500; text-align: center;"><?= ucfirst($role) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_perms as $perm): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1.25rem 2rem;">
                                    <div style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($perm['permission_name']) ?></div>
                                    <div style="font-size: 12px; color: var(--text-2);"><?= htmlspecialchars($perm['description']) ?></div>
                                </td>
                                <?php foreach ($roles as $role): ?>
                                    <td style="padding: 1.25rem; text-align: center;">
                                        <input type="checkbox" 
                                               name="perms[<?= $role ?>][]" 
                                               value="<?= $perm['id'] ?>"
                                               <?= (isset($role_perms[$role]) && in_array($perm['id'], $role_perms[$role])) ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding-left: 3rem; padding-right: 3rem;">Save Rights Configuration</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
