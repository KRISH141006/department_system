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

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 600px; margin: 0 auto; padding: 2.5rem;">
        <h1 style="font-family: 'DM Serif Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem;">Host Live Class</h1>
        <p style="color: var(--text-2); margin-bottom: 2rem;">Start a new video session for your students.</p>

        <form action="<?= $base_path ?>/api/academics/start_meeting" method="POST" id="hostForm">
            <input type="hidden" name="room_code" value="<?= $room_code ?>">

            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Select Subject & Class</label>
                <select name="subject_class" id="subjectSelect" required style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);" onchange="updateDetails()">
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

            <div class="form-group" id="topicGroup" style="display: none; margin-top: 1.5rem;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Specific Topic (Optional)</label>
                <select name="topic_id" id="topicSelect" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; background: var(--bg);">
                    <option value="0">-- Any / General Session --</option>
                </select>
            </div>

            <div style="margin-top: 2rem; background: var(--bg-2); padding: 1.5rem; border-radius: 8px; border: 1px dashed var(--border);">
                <p style="font-size: 0.85rem; color: var(--text-2); margin-bottom: 10px;">Session Room Code:</p>
                <code style="font-size: 1.25rem; color: var(--accent); font-weight: 800; letter-spacing: 2px;"><?= strtoupper($room_code) ?></code>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem; padding: 1rem;">
                🚀 Launch Classroom
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
        const res = await fetch(`get_topics_ajax.php?subject_id=${sid}`);
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
