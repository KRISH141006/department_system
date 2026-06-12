<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: $base_path/dashboard");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];

// 1. Fetch Taught Subjects/Classes - Updated junction logic
$subQuery = $conn->prepare("
    SELECT s.id as subject_id, s.name as subject_name, c.name as class_name, c.semester, c.id as class_id, cs.id as class_subject_id
    FROM faculty_subjects fs 
    JOIN class_subjects cs ON fs.class_subject_id = cs.id 
    JOIN subjects s ON cs.subject_id = s.id 
    JOIN classes c ON cs.class_id = c.id 
    WHERE fs.faculty_id = ?
");
$subQuery->bind_param("i", $faculty_id);
$subQuery->execute();
$assignments = $subQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$room_code = bin2hex(random_bytes(4)); // Random short code
$page_title = "Host Live Class";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<style>
    .host-meeting-card {
        max-width: 600px;
        margin: 2rem auto;
        padding: 3rem;
        border-top: 4px solid var(--accent);
        box-shadow: var(--shadow-lg);
    }

    .premium-select {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid var(--border);
        border-radius: var(--radius-sm);
        background-color: var(--surface-2);
        color: var(--text);
        font-size: 1rem;
        font-weight: 600;
        transition: var(--transition);
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.25rem;
        cursor: pointer;
        outline: none;
    }

    .premium-select:hover {
        border-color: var(--accent-hover);
        background-color: var(--surface);
    }

    .premium-select:focus {
        border-color: var(--accent);
        background-color: var(--surface);
        box-shadow: 0 0 0 4px var(--accent-light);
    }

    .form-label-premium {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: var(--text-2);
    }

    [data-theme="dark"] .premium-select {
        background-color: var(--surface-2);
        border-color: var(--border);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
    }

    [data-theme="dark"] .premium-select:hover,
    [data-theme="dark"] .premium-select:focus {
        background-color: #1e293b;
        border-color: var(--accent);
    }
</style>

<div class="wrapper">
    <div class="card host-meeting-card">
        <h1 class="page-title" style="margin-bottom: 0.5rem;">Host Live Class</h1>
        <p class="page-subtitle" style="margin-bottom: 2.5rem;">Launch a new video session. We'll generate a secure meeting link and notify your class immediately.</p>

        <form action="<?= $base_path ?>/api/academics/start_meeting" method="POST" id="hostForm">

            <div class="form-group">
                <label class="form-label-premium">Course & Target Class</label>
                <select name="subject_class" id="subjectSelect" class="premium-select" required onchange="updateDetails()">
                    <option value="">-- Choose Class-Subject --</option>
                    <?php foreach ($assignments as $a): ?>
                        <option value="<?= $a['subject_id'] ?>|<?= $a['class_id'] ?>" data-cs-id="<?= $a['class_subject_id'] ?>">
                            <?= htmlspecialchars($a['subject_name']) ?> (<?= htmlspecialchars($a['class_name']) ?> Sem <?= $a['semester'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="subject_id" id="subject_id">
                <input type="hidden" name="class_id" id="class_id">
            </div>

            <div class="form-group" id="topicGroup" style="display: none; margin-top: 2rem;">
                <label class="form-label-premium">Specific Lesson Topic (Optional)</label>
                <select name="topic_id" id="topicSelect" class="premium-select">
                    <option value="0">-- Any / General Session --</option>
                </select>
            </div>

            <div style="margin-top: 2.5rem; background: var(--accent-light); padding: 1.5rem; border-radius: var(--radius-sm); border: 1px dashed var(--accent); display: flex; align-items: center; gap: 1.25rem;">
                <div style="background: var(--surface); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: var(--shadow);">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="var(--accent)" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                </div>
                <div style="text-align: left;">
                    <p style="font-weight: 700; color: var(--text); font-size: 0.95rem; margin-bottom: 2px;">Google Meet Active</p>
                    <p style="font-size: 0.85rem; color: var(--text-2); margin: 0; line-height: 1.4;">Classroom link will be shared via notifications.</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 3rem; padding: 1.25rem; font-size: 1.1rem; font-weight: 700; border-radius: var(--radius-sm);">
                🚀 Launch Global Classroom
            </button>
        </form>
    </div>
</div>

<script>
async function updateDetails() {
    const select = document.getElementById('subjectSelect');
    const topicGroup = document.getElementById('topicGroup');
    const topicSelect = document.getElementById('topicSelect');
    const subjectIdInput = document.getElementById('subject_id');
    const classIdInput = document.getElementById('class_id');
    
    const val = select.value;
    if (!val) {
        topicGroup.style.display = 'none';
        return;
    }

    const [sid, cid] = val.split('|');
    subjectIdInput.value = sid;
    classIdInput.value = cid;

    // Fetch topics for the selected subject
    topicSelect.innerHTML = '<option value="0">Loading topics...</option>';
    topicGroup.style.display = 'block';

    try {
        const res = await fetch(`<?= $base_path ?>/academics/get_topics_ajax?subject_id=${sid}`);
        const json = await res.json();

        if (json.status === 'success') {
            let html = '<option value="0">-- Any / General Session --</option>';
            json.data.forEach(unit => {
                html += `<optgroup label="Unit ${unit.unit_no}: ${unit.unit_name}">`;
                unit.topics.forEach(t => {
                    html += `<option value="${t.id}">${t.topic_name}</option>`;
                });
                html += `</optgroup>`;
            });
            topicSelect.innerHTML = html;
        } else {
            topicSelect.innerHTML = '<option value="0">-- General Session --</option>';
        }
    } catch (e) {
        topicSelect.innerHTML = '<option value="0">-- Error loading topics --</option>';
    }
}
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
