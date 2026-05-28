<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT name, class_name, semester, emp_id FROM users WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$uRow = $stmt->get_result()->fetch_assoc();

$name = $uRow['name'] ?? 'Faculty';
$emp_id = $uRow['emp_id'] ?? 'N/A';
$class_name = $uRow['class_name'] ?? 'N/A';
$semester = $uRow['semester'] ?? 'N/A';

// Check if CC
$ccStmt = $conn->prepare("SELECT is_cc, cc_class, cc_semester FROM profiles WHERE user_id = ?");
$ccStmt->bind_param("i", $faculty_id);
$ccStmt->execute();
$ccProfile = $ccStmt->get_result()->fetch_assoc();
$is_cc = $ccProfile['is_cc'] ?? 0;

$page_title = "Faculty Dashboard";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem; margin-bottom: 4rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <div class="dashboard-title">
            <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; color: var(--text);">Faculty Dashboard</h1>
            <p style="color: var(--text-2);">Welcome back, <strong><?= htmlspecialchars($name) ?></strong> (ID: <?= htmlspecialchars($emp_id) ?>). Manage your subjects, feedback, and student interactions.</p>
        </div>
    </div>

    <div class="grid-2">
        <?php if ($is_cc): ?>
            <a href="manage_class.php" class="card" style="text-decoration: none; color: inherit; background: var(--bg-2); border: 2px solid var(--accent);">
                <div style="font-size: 32px; margin-bottom: 12px;">🏫</div>
                <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600; color: var(--accent);">Manage My Class</h3>
                <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">View roster, add or remove students for <strong><?= htmlspecialchars($ccProfile['cc_class']) ?></strong>.</p>
            </a>
        <?php endif; ?>

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
            <?php 
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback_selector WHERE selected_date = ? AND subject_id IN (SELECT id FROM faculty_subjects WHERE faculty_id = ?)");
            $countStmt->bind_param("si", $today, $faculty_id);
            $countStmt->execute();
            $assignedCount = $countStmt->get_result()->fetch_assoc()['count'] ?? 0;
            ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 32px; margin-bottom: 12px;">🎯</div>
                <?php if ($assignedCount > 0): ?>
                    <span class="badge badge-success"><?= $assignedCount ?> Active</span>
                <?php endif; ?>
            </div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Syllabus Verification</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Assign a student to verify today's covered topics.</p>
        </a>

        <a href="syllabus_verification.php" class="card" style="text-decoration: none; color: inherit; border-top: 4px solid var(--accent);">
            <?php 
            // Count today's updates for faculty's subjects (only unverified)
            $updStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM topic_progress tp
                JOIN faculty_subjects fs ON fs.subject_name = tp.subject
                WHERE fs.faculty_id = ? AND DATE(tp.updated_at) = ? AND tp.is_covered = 1 AND tp.is_verified = 0
            ");
            $updStmt->bind_param("is", $faculty_id, $today);
            $updStmt->execute();
            $updCount = $updStmt->get_result()->fetch_assoc()['count'] ?? 0;
            ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 32px; margin-bottom: 12px;">🔍</div>
                <?php if ($updCount > 0): ?>
                    <span class="badge badge-warning"><?= $updCount ?> Updates</span>
                <?php endif; ?>
            </div>
            <h3 style="margin-bottom: 8px; font-size: 1.25rem; font-weight: 600;">Progress Review</h3>
            <p style="font-size: 14px; color: var(--text-2); margin-top: 8px;">Monitor and correct today's syllabus updates from students.</p>
        </a>
    </div>

    <!-- ASSIGNED SUBJECTS -->
    <div style="margin-top: 60px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0;">My Subjects & Classes</h2>
            <a href="create_subject.php" class="btn btn-sm" style="background: var(--accent); color: white;">+ Add Subject</a>
        </div>
        
        <div class="grid-2">
            <?php 
            $subQuery = $conn->prepare("
                SELECT fs.id, fs.subject_name, fs.class_name, fs.semester, u.name as assigned_student 
                FROM faculty_subjects fs
                LEFT JOIN feedback_selector s ON s.subject_id = fs.id AND s.selected_date = ?
                LEFT JOIN users u ON u.id = s.selected_student_id
                WHERE fs.faculty_id = ?
            ");
            $subQuery->bind_param("si", $today, $faculty_id);
            $subQuery->execute();
            $subjects = $subQuery->get_result();

            if ($subjects->num_rows === 0) {
                echo "<div class='card' style='grid-column: span 2; text-align: center; padding: 2rem;'>
                        <p style='color: var(--text-2);'>You haven't added any subjects yet.</p>
                      </div>";
            }

            while ($sub = $subjects->fetch_assoc()) {
            ?>
                <div class="card" style="display: flex; justify-content: space-between; align-items: center; border-left: 4px solid <?php echo $sub['assigned_student'] ? 'var(--success)' : 'transparent'; ?>;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <h3 style="margin: 0; font-size: 1.15rem;"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                            <?php if ($sub['assigned_student']): ?>
                                <span class="badge badge-success" style="font-size: 10px;">Assigned</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size: 14px; color: var(--text-2);">
                            Class: <strong><?php echo htmlspecialchars($sub['class_name']); ?></strong> | 
                            Semester: <strong><?php echo htmlspecialchars($sub['semester']); ?></strong>
                        </p>
                        <?php if ($sub['assigned_student']): ?>
                            <p style="font-size: 12px; color: var(--success); margin-top: 4px; font-weight: 600;">
                                👤 Assigned: <?php echo htmlspecialchars($sub['assigned_student']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="create_subject.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <a href="units.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-secondary">Units</a>
                        <a href="select_student.php?class_name=<?php echo urlencode($sub['class_name']); ?>&semester=<?php echo urlencode($sub['semester']); ?>" class="btn btn-sm <?php echo $sub['assigned_student'] ? 'btn-secondary' : 'btn-primary'; ?>">
                            <?php echo $sub['assigned_student'] ? 'Reassign' : 'Verify'; ?>
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div style="margin-top: 60px;">
        <h2 style="margin-bottom: 24px;">Quick Statistics</h2>
        <div class="grid-2" style="grid-template-columns: repeat(3, 1fr);">
            <?php
            // 1. Total Topics Covered
            $tcStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM topic_progress tp
                JOIN faculty_subjects fs ON fs.subject_name = tp.subject
                WHERE fs.faculty_id = ? AND tp.is_covered = 1
            ");
            $tcStmt->bind_param("i", $faculty_id);
            $tcStmt->execute();
            $totalCovered = $tcStmt->get_result()->fetch_assoc()['count'] ?? 0;

            // 2. Average Rating
            $arStmt = $conn->prepare("
                SELECT AVG(rating) as avg_rating 
                FROM student_faculty_feedback sff
                JOIN faculty_feedback_forms fff ON fff.id = sff.form_id
                WHERE fff.faculty_id = ?
            ");
            $arStmt->bind_param("i", $faculty_id);
            $arStmt->execute();
            $avgRating = $arStmt->get_result()->fetch_assoc()['avg_rating'];
            $displayRating = $avgRating ? round($avgRating, 1) : '0.0';

            // 3. Pending Tasks (Topics covered but not verified)
            $ptStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM topic_progress tp
                JOIN faculty_subjects fs ON fs.subject_name = tp.subject
                WHERE fs.faculty_id = ? AND tp.is_covered = 1 AND tp.is_verified = 0
            ");
            $ptStmt->bind_param("i", $faculty_id);
            $ptStmt->execute();
            $pendingTasks = $ptStmt->get_result()->fetch_assoc()['count'] ?? 0;
            ?>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--accent); font-size: 2.5rem;"><?= $totalCovered ?></h1>
                <p style="color: var(--text-2); font-size: 14px;">Total Topics Covered</p>
            </div>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--success); font-size: 2.5rem;"><?= $displayRating ?></h1>
                <p style="color: var(--text-2); font-size: 14px;">Avg. Rating</p>
            </div>
            <div class="card" style="text-align: center;">
                <h1 style="color: var(--warning); font-size: 2.5rem;"><?= $pendingTasks ?></h1>
                <p style="color: var(--text-2); font-size: 14px;">Pending Verifications</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
