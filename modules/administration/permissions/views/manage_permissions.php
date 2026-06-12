<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Manage Role Permissions";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch all permissions
$res = $conn->query("SELECT * FROM permissions ORDER BY permission_name ASC");
$all_perms = [];
while ($row = $res->fetch_assoc()) {
    $all_perms[] = $row;
}

// Define roles to manage
$roles = ['student', 'faculty', 'expert'];

// Fetch current role permissions
$role_perms = [];
$rp_query = $conn->query("SELECT role, permission_id FROM role_permissions");
while ($row = $rp_query->fetch_assoc()) {
    $role_perms[$row['role']][] = $row['permission_id'];
}
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Rights Management</h1>
            <p class="page-subtitle">Configure granular access controls and dynamic permissions for all system roles.</p>
        </div>
    </div>

    <form action="<?= $base_path ?>/api/admin/save_permissions" method="POST">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Access Permission</th>
                        <?php foreach ($roles as $role): ?>
                            <th style="text-align: center; width: 120px;"><?= ucfirst($role) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_perms as $perm): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($perm['permission_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-3); margin-top: 2px;"><?= htmlspecialchars($perm['description']) ?></div>
                            </td>
                            <?php foreach ($roles as $role): ?>
                                <td style="text-align: center;">
                                    <label class="custom-checkbox" style="display: inline-block; cursor: pointer;">
                                        <input type="checkbox" 
                                               name="perms[<?= $role ?>][]" 
                                               value="<?= $perm['id'] ?>"
                                               <?= (isset($role_perms[$role]) && in_array($perm['id'], $role_perms[$role])) ? 'checked' : '' ?>
                                               style="width: 20px; height: 20px; accent-color: var(--accent);">
                                    </label>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 2.5rem; display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
            <p style="font-size: 0.85rem; color: var(--text-3); margin-right: auto;">⚠️ Changes take effect immediately for all logged-in users.</p>
            <a href="<?= $base_path ?>/dashboard" class="btn btn-secondary">Discard Changes</a>
            <button type="submit" class="btn btn-primary" style="padding-left: 2.5rem; padding-right: 2.5rem;">Save Permissions</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
