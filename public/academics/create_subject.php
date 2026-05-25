<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Create Subject";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper narrow" style="padding: 2rem;">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem;">Create Subject</h2>

        <form action="../../app/actions/academics/save_subject.php" method="POST">
            <div class="form-group">
                <label>Subject Name</label>
                <input type="text" name="subject_name" placeholder="e.g. Web Development" required>
            </div>

            <?php for ($i = 1; $i <= 7; $i++) { ?>
                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1rem;">Unit <?php echo $i; ?></h3>
                    <div class="form-group">
                        <label>Unit Name</label>
                        <input type="text" name="unit_name_<?php echo $i; ?>" placeholder="Unit <?php echo $i; ?> Name">
                    </div>
                    <div class="form-group">
                        <label>Topics (Line by line)</label>
                        <textarea name="topics_<?php echo $i; ?>" placeholder="Enter topics line by line" style="height: 100px;"></textarea>
                    </div>
                </div>
            <?php } ?>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">Confirm Create Subject</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>