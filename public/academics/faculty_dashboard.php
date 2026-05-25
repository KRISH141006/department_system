<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'creator'])) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, class_name, semester, emp_id FROM users WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? 'Faculty';
$emp_id = $uRow['emp_id'] ?? 'N/A';
$class_name = $uRow['class_name'] ?? 'N/A';
$semester = $uRow['semester'] ?? 'N/A';

$page_title = "Faculty Dashboard";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Faculty Dashboard</h1>
            <p style="color: var(--text-2);">Welcome back, <strong><?= htmlspecialchars($name) ?></strong> (ID: <?= htmlspecialchars($emp_id) ?>). Manage your subjects, feedback, and student interactions.</p>
        </div>
    </div>

    <div class="grid-2">
        <a href="create_subject.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">📚</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Create Subject</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Define syllabus units and topics for your classes.</p>
        </a>

        <a href="create_feedback.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">📝</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Create Feedback</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Generate evaluation forms for student feedback.</p>
        </a>

        <a href="feedback_results.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">📊</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Student's Feedback</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Review consolidated ratings and student comments.</p>
        </a>

        <a href="select_student.php" class="card" style="text-decoration: none; color: inherit;">
            <div style="font-size: 32px; margin-bottom: 12px;">🎯</div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Syllabus Verification</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Assign a student to verify today's covered topics.</p>
        </a>
    </div>

    <!-- QUICK STATS -->
    <div style="margin-top: 60px;">
        <h2 style="margin-bottom: 24px;">Quick Statistics</h2>
        <div class="grid-2" style="grid-template-columns: repeat(3, 1fr);">
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--accent); font-size: 2.5rem;">12</h1>
                <p style="color: var(--text-2); font-size: 14px;">Total Topics Covered</p>
            </div>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--success); font-size: 2.5rem;">4.8</h1>
                <p style="color: var(--text-2); font-size: 14px;">Avg. Rating</p>
            </div>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--warning); font-size: 2.5rem;">15</h1>
                <p style="color: var(--text-2); font-size: 14px;">Pending Feedbacks</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>