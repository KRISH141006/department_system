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
        background-image: var(--select-arrow-icon) !important;
        background-repeat: no-repeat !important;
        background-position: right 1rem center !important;
        background-size: 1.1rem 1.1rem !important;
        padding-right: 3rem;
    }

    .feedback-select::after {
        content: none;
    }
</style>

<div class="wrapper">
    <div class="anonymous-feedback-shell">
        <section class="ux-workspace-hero" style="grid-template-columns: 1fr;">
            <div class="ux-workspace-hero-main">
                <span class="ux-kicker">Private Feedback</span>
                <h1 class="ux-hero-title">Anonymous Feedback Box</h1>
                <p class="ux-hero-copy">Your identity remains anonymous. Select the faculty member, optionally choose a subject, and share precise feedback.</p>
            </div>
        </section>

        <div class="ux-form-panel">
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

                <div class="card-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1rem;">Submit Confidential Feedback</button>
                </div>
            </form>
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
