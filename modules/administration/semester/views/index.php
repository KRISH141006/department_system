<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_admin_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Semester Management";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch current semester stats
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT id) as total_classes,
        GROUP_CONCAT(DISTINCT semester ORDER BY semester SEPARATOR ', ') as active_semesters
    FROM classes
")->fetch_assoc();

$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
?>

<div class="wrapper">
    <div class="section-header" style="margin-top: 0;">
        <div>
            <h1 class="page-title">Semester Management</h1>
            <p class="page-subtitle">Control the academic lifecycle and transition students to the next level.</p>
        </div>
    </div>

    <div class="grid-2" style="grid-template-columns: 1.5fr 1fr; align-items: start;">
        <div class="card card-accent-red">
            <h2 class="card-title">Announce Semester End</h2>
            <p class="card-desc" style="margin-bottom: 2rem;">
                Closing the semester is a critical system action. Performing this will:
            </p>
            
            <ul style="color: var(--text-2); font-size: 0.9rem; margin-bottom: 2rem; padding-left: 1.5rem; line-height: 1.8;">
                <li>Increment the semester count for all existing classes by 1.</li>
                <li>Clear all current elective subject enrollments.</li>
                <li>Archive/Clear all pending elective unlock requests.</li>
                <li>Set all active faculty feedback forms to 'closed'.</li>
                <li>Reset the random PAC student assignments for syllabus verification.</li>
            </ul>

            <div class="alert alert-error" style="background: rgba(239, 68, 68, 0.05); border-left: 4px solid var(--error); margin-bottom: 2rem;">
                <strong>⚠️ Warning:</strong> This action cannot be undone. Please ensure all grading and final reviews are complete.
            </div>

            <form action="<?= $base_path ?>/api/admin/semester_done" method="POST" onsubmit="return confirm('Are you ABSOLUTELY sure? This will advance all students to the next semester.')">
                <button type="submit" class="btn btn-primary" style="background: var(--error); border: none; width: 100%; padding: 1rem; font-weight: 700;">
                    Finalize Current Semester & Advance Students
                </button>
            </form>
        </div>

        <div>
            <h3 class="section-title" style="font-size: 1.1rem; margin: 0 0 1.5rem 0;">Current Status</h3>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Students</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--accent);"><?= $student_count ?></div>
            </div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Active Classes</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--text);"><?= $stats['total_classes'] ?></div>
            </div>
            <div class="card">
                <div style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Semesters in Play</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-2); margin-top: 0.5rem;"><?= $stats['active_semesters'] ?: 'None' ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
