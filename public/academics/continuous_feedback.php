<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Anonymous Feedback Box";
require_once __DIR__ . '/../../app/includes/header.php';

// Fetch all faculty members
$fac_query = $conn->query("SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name ASC");
$faculty_members = $fac_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="wrapper" style="padding: 2rem;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h1 class="page-title" style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Anonymous Feedback Box</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Your identity will remain completely anonymous. This feedback is only visible to the Department Admin.</p>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <form action="../../app/actions/academics/submit_continuous_feedback.php" method="POST">
                <div class="form-group">
                    <label>Select Faculty</label>
                    <select name="faculty_id" id="facultySelect" required onchange="loadFacultySubjects()">
                        <option value="">-- Choose Faculty --</option>
                        <?php foreach ($faculty_members as $fac): ?>
                            <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Subject (Optional)</label>
                    <select name="subject_id" id="subjectSelect">
                        <option value="">-- General Feedback --</option>
                        <!-- Dynamically loaded -->
                    </select>
                </div>

                <div class="form-group">
                    <label>Your Feedback</label>
                    <textarea name="feedback_text" required placeholder="Write your honest feedback here..." style="height: 150px;"></textarea>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-full">Submit Anonymous Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function loadFacultySubjects() {
    const facId = document.getElementById('facultySelect').value;
    const subSelect = document.getElementById('subjectSelect');
    
    // Reset subjects
    subSelect.innerHTML = '<option value="">-- General Feedback --</option>';
    
    if (!facId) return;

    try {
        // We can reuse a similar logic to get_topics_ajax but for subjects
        const response = await fetch(`get_faculty_subjects_ajax.php?faculty_id=${facId}`);
        const result = await response.json();

        if (result.status === 'success') {
            result.data.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.subject_name + ' (' + sub.class_name + ')';
                subSelect.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Failed to load subjects");
    }
}
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
