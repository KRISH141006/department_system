<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Create Feedback Form";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper narrow" style="padding: 2rem;">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem;">Create Feedback Form</h2>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Configure the questions for student evaluation.</p>

        <form action="../../app/actions/academics/save_feedback_form.php" method="POST">
            <div class="form-group">
                <label>Question 1</label>
                <input type="text" name="q1" value="The teacher explains the topic clearly." required>
            </div>
            <div class="form-group">
                <label>Question 2</label>
                <input type="text" name="q2" value="The teaching method is easy to understand." required>
            </div>
            <div class="form-group">
                <label>Question 3</label>
                <input type="text" name="q3" value="The teacher gives useful examples during lecture." required>
            </div>
            <div class="form-group">
                <label>Question 4</label>
                <input type="text" name="q4" value="The lecture is interactive and engaging." required>
            </div>
            <div class="form-group">
                <label>Question 5</label>
                <input type="text" name="q5" value="The teacher clears doubts properly." required>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 1.5rem;">Launch Feedback Form</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>