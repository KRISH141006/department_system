<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_student_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$page_title = "Anonymous Feedback Box";
require_once __DIR__ . '/../../../../shared/layout/header.php';

// Fetch all faculty members
$fac_query = $conn->query("SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name ASC");
$faculty_members = $fac_query->fetch_all(MYSQLI_ASSOC);
?>

<style>
    .anonymous-feedback-shell {
        max-width: 700px;
        margin: 0 auto;
    }

    .feedback-select {
        position: relative;
    }

    .feedback-select select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none !important;
        padding-right: 3rem;
    }

    .feedback-select::after {
        content: "";
        position: absolute;
        right: 1.15rem;
        top: 50%;
        width: 0.65rem;
        height: 0.65rem;
        border-right: 2px solid var(--text-3);
        border-bottom: 2px solid var(--text-3);
        pointer-events: none;
        transform: translateY(-65%) rotate(45deg);
        transition: var(--transition);
    }

    .feedback-select:focus-within::after {
        border-color: var(--accent);
    }
</style>

<div class="wrapper">
    <div class="anonymous-feedback-shell">
        <div class="section-header" style="margin-top: 0; text-align: center; display: block;">
            <h1 class="page-title">Anonymous Feedback Box</h1>
            <p class="page-subtitle">Your identity will remain completely anonymous. Help us improve by providing honest feedback.</p>
        </div>

        <div class="card card-accent-orange">
            <form action="<?= $base_path ?>/api/academics/submit_continuous_feedback" method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Select Faculty Member</label>
                    <div class="feedback-select">
                        <select name="faculty_id" id="facultySelect" class="form-control" required onchange="loadFacultySubjects()">
                            <option value="">-- Choose Faculty --</option>
                            <?php foreach ($faculty_members as $fac): ?>
                                <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Select Subject <span style="font-weight: 400; opacity: 0.7;">(Optional)</span></label>
                    <div class="feedback-select">
                        <select name="subject_id" id="subjectSelect" class="form-control">
                            <option value="">-- General Feedback --</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label class="form-label">Your Honest Feedback</label>
                    <textarea name="feedback_text" class="form-control" required placeholder="Describe your experience or suggest improvements..." style="min-height: 180px; resize: vertical;"></textarea>
                </div>

                <div style="margin-top: 2.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1rem;">Submit Confidential Feedback</button>
                </div>
            </form>
        </div>
        
        <div style="margin-top: 2rem; text-align: center; color: var(--text-3); font-size: 0.8rem;">
            🛡️ Encrypted & Anonymous Submission System
        </div>
    </div>
</div>

<script>
async function loadFacultySubjects() {
    const facId = document.getElementById('facultySelect').value;
    const subSelect = document.getElementById('subjectSelect');
    
    subSelect.innerHTML = '<option value="">-- General Feedback --</option>';
    if (!facId) return;

    try {
        const response = await fetch(`<?= $base_path ?>/academics/get_faculty_subjects_ajax?faculty_id=${facId}`);
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

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
