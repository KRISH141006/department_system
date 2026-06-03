<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
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

// Fetch Dashboard Statistics
// 1. Total Classes
$statsClassesStmt = $conn->prepare("SELECT COUNT(*) as count FROM faculty_subjects WHERE faculty_id = ?");
$statsClassesStmt->bind_param("i", $faculty_id);
$statsClassesStmt->execute();
$totalClasses = $statsClassesStmt->get_result()->fetch_assoc()['count'] ?? 0;

// 2. Total Students (Unique students in classes taught by faculty)
$statsStudentsStmt = $conn->prepare("SELECT COUNT(DISTINCT id) as count FROM users WHERE role='student' AND class_name IN (SELECT class_name FROM faculty_subjects WHERE faculty_id = ?)");
$statsStudentsStmt->bind_param("i", $faculty_id);
$statsStudentsStmt->execute();
$totalStudents = $statsStudentsStmt->get_result()->fetch_assoc()['count'] ?? 0;

// 3. Pending Reviews (Community Module)
$statsReviewsStmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE reviewer_id = ? AND status != 'completed'");
$statsReviewsStmt->bind_param("i", $faculty_id);
$statsReviewsStmt->execute();
$pendingReviews = $statsReviewsStmt->get_result()->fetch_assoc()['count'] ?? 0;

// 4. Active Assignments
$statsAssignmentsStmt = $conn->prepare("SELECT COUNT(*) as count FROM faculty_assignments WHERE faculty_id = ? AND (deadline IS NULL OR deadline > NOW())");
$statsAssignmentsStmt->bind_param("i", $faculty_id);
$statsAssignmentsStmt->execute();
$activeAssignments = $statsAssignmentsStmt->get_result()->fetch_assoc()['count'] ?? 0;

$page_title = "Faculty Dashboard";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem;">
        <div class="dashboard-title">
            <h1 class="page-title">Faculty Dashboard</h1>
            <p class="page-subtitle" style="margin-bottom: 0;">Welcome back, <strong><?= htmlspecialchars($name) ?></strong> (ID: <?= htmlspecialchars($emp_id) ?>).</p>
        </div>
        <div class="quick-actions" style="display: flex; gap: 8px;">
            <a href="create_subject.php" class="btn btn-sm btn-primary" title="Shortcut: N">+ Add Subject <span style="font-size: 0.7rem; opacity: 0.6; margin-left: 4px;">(N)</span></a>
            <a href="assign_task.php" class="btn btn-sm btn-success" title="Shortcut: A">+ Assign Task <span style="font-size: 0.7rem; opacity: 0.6; margin-left: 4px;">(A)</span></a>
            <a href="host_meeting.php" class="btn btn-sm" style="background: #ef4444; color: white;">Host Live</a>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div class="card card-sm" style="margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem; background: rgba(59, 130, 246, 0.1); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; color: var(--accent);">🏫</div>
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1;"><?= $totalClasses ?></h2>
                <p class="text-muted" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin: 0;">Classes</p>
            </div>
        </div>
        <div class="card card-sm" style="margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem; background: rgba(34, 197, 94, 0.1); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; color: var(--success);">👥</div>
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1;"><?= $totalStudents ?></h2>
                <p class="text-muted" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin: 0;">Students</p>
            </div>
        </div>
        <div class="card card-sm" style="margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem; background: rgba(245, 158, 11, 0.1); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; color: var(--warning);">⏳</div>
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1;"><?= $pendingReviews ?></h2>
                <p class="text-muted" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin: 0;">Pending Reviews</p>
            </div>
        </div>
        <div class="card card-sm" style="margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem; background: rgba(59, 130, 246, 0.1); width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; color: var(--accent);">📤</div>
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1;"><?= $activeAssignments ?></h2>
                <p class="text-muted" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin: 0;">Active Tasks</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="grid-3" style="margin-bottom: 0;">
            <?php if ($is_cc): ?>
            <a href="manage_class.php" class="card card-sm" style="text-decoration: none; border-left: 4px solid var(--accent);">
                <div style="font-size: 24px; margin-bottom: 8px;">🏫</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Class Manager</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Manage <strong><?= htmlspecialchars($ccProfile['cc_class']) ?></strong> roster.</p>
            </a>
            <?php endif; ?>

            <a href="create_subject.php" class="card card-sm" style="text-decoration: none;">
                <div style="font-size: 24px; margin-bottom: 8px;">📚</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Syllabus</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Define units and topics for classes.</p>
            </a>

            <a href="create_feedback.php" class="card card-sm" style="text-decoration: none;">
                <div style="font-size: 24px; margin-bottom: 8px;">📝</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Feedback Forms</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Generate evaluation forms.</p>
            </a>

            <a href="feedback_results.php" class="card card-sm" style="text-decoration: none;">
                <div style="font-size: 24px; margin-bottom: 8px;">📊</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Results</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Review consolidated ratings.</p>
            </a>

            <a href="assign_task.php" class="card card-sm" style="text-decoration: none; border-left: 4px solid var(--accent);">
                <div style="font-size: 24px; margin-bottom: 8px;">📋</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Assign Task</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Academic or productivity tasks.</p>
            </a>

            <a href="submissions.php" class="card card-sm" style="text-decoration: none; border-left: 4px solid #22c55e;">
                <div style="font-size: 24px; margin-bottom: 8px;">📤</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Submissions</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Review and grade assignments.</p>
            </a>

            <a href="assigned_tasks_history.php" class="card card-sm" style="text-decoration: none; border-left: 4px solid var(--primary);">
                <div style="font-size: 24px; margin-bottom: 8px;">📜</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Task History</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Manage previously assigned tasks.</p>
            </a>

            <a href="select_student.php" class="card card-sm" style="text-decoration: none;">
                <?php 
                $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback_selector WHERE selected_date = ? AND subject_id IN (SELECT id FROM faculty_subjects WHERE faculty_id = ?)");
                $countStmt->bind_param("si", $today, $faculty_id);
                $countStmt->execute();
                $assignedCount = $countStmt->get_result()->fetch_assoc()['count'] ?? 0;
                ?>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="font-size: 24px; margin-bottom: 8px;">🎯</div>
                    <?php if ($assignedCount > 0): ?>
                        <span class="badge badge-success"><?= $assignedCount ?></span>
                    <?php endif; ?>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 4px;">Verification</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Assign students to verify topics.</p>
            </a>

            <a href="syllabus_verification.php" class="card card-sm" style="text-decoration: none; border-top: 4px solid var(--accent);">
                <?php 
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
                    <div style="font-size: 24px; margin-bottom: 8px;">🔍</div>
                    <?php if ($updCount > 0): ?>
                        <span class="badge badge-warning"><?= $updCount ?></span>
                    <?php endif; ?>
                </div>
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 4px;">Progress Review</h3>
                <p style="font-size: 0.75rem; color: var(--text-secondary);">Monitor syllabus updates.</p>
            </a>
        </div>

        <!-- Today's Schedule Widget -->
        <div class="card" style="margin-bottom: 0; padding: 1.5rem; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Today's Schedule</h3>
                <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent); font-size: 0.7rem;"><?= date('D, j M') ?></span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem; flex: 1;">
                <?php
                $schedQuery = $conn->prepare("SELECT subject_name, class_name FROM faculty_subjects WHERE faculty_id = ? LIMIT 3");
                $schedQuery->bind_param("i", $faculty_id);
                $schedQuery->execute();
                $schedules = $schedQuery->get_result();
                
                if ($schedules->num_rows > 0) {
                    $times = ['09:00 AM', '11:00 AM', '02:00 PM'];
                    $i = 0;
                    while ($s = $schedules->fetch_assoc()) {
                        $time = $times[$i++] ?? 'TBA';
                ?>
                    <div style="display: flex; align-items: center; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); width: 70px;"><?= $time ?></div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0; font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($s['subject_name']) ?></h4>
                            <p style="margin: 0; font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($s['class_name']) ?></p>
                        </div>
                        <span class="badge badge-success" style="font-size: 0.6rem;">Upcoming</span>
                    </div>
                <?php 
                    }
                } else {
                    echo "<div style='text-align: center; padding: 2rem 0; opacity: 0.5;'>
                            <div style='font-size: 2rem; margin-bottom: 0.5rem;'>📅</div>
                            <p style='font-size: 0.85rem;'>No classes scheduled today</p>
                          </div>";
                }
                ?>
            </div>
            
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.75rem; color: var(--text-secondary);">
                <strong>Keyboard Shortcuts:</strong>
                <div style="display: flex; gap: 10px; margin-top: 5px; opacity: 0.7;">
                    <span><kbd style="background: var(--bg-secondary); padding: 2px 4px; border-radius: 4px; border: 1px solid var(--border-color);">N</kbd> New Subject</span>
                    <span><kbd style="background: var(--bg-secondary); padding: 2px 4px; border-radius: 4px; border: 1px solid var(--border-color);">A</kbd> Assignment</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ASSIGNED SUBJECTS -->
    <div style="margin-top: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h2 style="font-family: 'DM Serif Display', serif; font-size: 1.75rem;">My Subjects & Classes</h2>
        </div>
        
        <div class="grid-2">
            <?php 
            $subQuery = $conn->prepare("
                SELECT fs.id, fs.subject_name, fs.class_name, fs.semester, fs.is_elective,
                       (SELECT COUNT(*) FROM feedback_selector s WHERE s.subject_id = fs.id AND s.selected_date = ?) as assigned_count
                FROM faculty_subjects fs
                WHERE fs.faculty_id = ?
            ");
            $subQuery->bind_param("si", $today, $faculty_id);
            $subQuery->execute();
            $subjects = $subQuery->get_result();

            if ($subjects->num_rows === 0) {
                echo "<div class='card' style='grid-column: span 2; text-align: center; padding: 2rem;'>
                        <p class='text-muted'>You haven't added any subjects yet.</p>
                      </div>";
            }

            while ($sub = $subjects->fetch_assoc()) {
                $hasAssignments = $sub['assigned_count'] > 0;
            ?>
                <div class="card card-sm" style="display: flex; justify-content: space-between; align-items: center; border-left: 4px solid <?php echo $sub['is_elective'] ? 'var(--primary)' : ($hasAssignments ? 'var(--success)' : 'var(--border-color)'); ?>;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 700;"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                            <?php if ($sub['is_elective']): ?>
                                <span class="badge badge-primary" style="font-size: 9px;">Elective</span>
                            <?php endif; ?>
                            <?php if ($hasAssignments): ?>
                                <span class="badge badge-success" style="font-size: 9px;">Assigned</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--text-secondary);">
                            <?= htmlspecialchars($sub['class_name']) ?> | Sem <?= htmlspecialchars($sub['semester']) ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <a href="units.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-secondary" title="Units">Units</a>
                        <a href="select_student.php?class_name=<?php echo urlencode($sub['class_name']); ?>&semester=<?php echo urlencode($sub['semester']); ?>" class="btn btn-sm <?php echo $hasAssignments ? 'btn-secondary' : 'btn-primary'; ?>">
                            <?php echo $hasAssignments ? 'Details' : 'Verify'; ?>
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div style="margin-top: 3rem;">
        <h2 style="font-family: 'DM Serif Display', serif; font-size: 1.75rem; margin-bottom: 1.25rem;">Quick Statistics</h2>
        <div class="grid-3">
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

            // 3. Pending Tasks
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
            <div class="card card-sm" style="text-align: center;">
                <h1 style="color: var(--accent); font-size: 2rem; font-weight: 800;"><?= $totalCovered ?></h1>
                <p class="text-muted" style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Topics Covered</p>
            </div>
            <div class="card card-sm" style="text-align: center;">
                <h1 style="color: var(--success); font-size: 2rem; font-weight: 800;"><?= $displayRating ?></h1>
                <p class="text-muted" style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Avg. Rating</p>
            </div>
            <div class="card card-sm" style="text-align: center;">
                <h1 style="color: var(--warning); font-size: 2rem; font-weight: 800;"><?= $pendingTasks ?></h1>
                <p class="text-muted" style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Pending</p>
            </div>
        </div>
    </div>
</div>

<script>
// Keyboard Shortcuts Support
document.addEventListener('keydown', function(e) {
    // Prevent shortcuts if user is typing in an input
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
        if (e.key === 'Escape') {
            e.target.blur();
        }
        return;
    }

    // Ctrl + K -> Search
    if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        alert('Quick Search is coming soon! Use (N) for New Subject or (A) for Assignments.');
    }

    // N -> Create New Subject
    if (e.key.toLowerCase() === 'n') {
        window.location.href = 'create_subject.php';
    }

    // A -> Create Assignment (Assign Task)
    if (e.key.toLowerCase() === 'a') {
        window.location.href = 'assign_task.php';
    }

    // Esc -> Close Modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(m => m.style.display = 'none');
    }
});
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
