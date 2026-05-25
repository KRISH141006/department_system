<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Manage Academics";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <h1>Academics Management</h1>
    <p>Manage curriculum, subjects, and view reports.</p>

    <div class="grid-2" style="margin-top: 2rem;">
        <a href="faculty_dashboard.php" class="btn btn-primary" style="display:inline-block; text-align:center;">Go to Faculty Panel</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>