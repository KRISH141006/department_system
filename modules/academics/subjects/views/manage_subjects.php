<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Manage Academics";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <h1>Academics Management</h1>
    <p>Manage curriculum, subjects, and view reports.</p>

    <div class="grid-2" style="margin-top: 2rem;">
        <a href="<?= $base_path ?>/academics/faculty_dashboard" class="btn btn-primary" style="display:inline-block; text-align:center;">Go to Faculty Panel</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
