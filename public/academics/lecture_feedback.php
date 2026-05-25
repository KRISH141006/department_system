<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../dashboard.php");
    exit();
}

$page_title = "Lecture Feedback";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h2 style="margin-bottom: 1.5rem;">Today's Lecture Feedback</h2>

        <form action="../../app/actions/academics/submit_lecture_feedback.php" method="POST">
            <?php
            // Get student's class and semester
            $student_id = $_SESSION['user_id'];
            $uStmt = $conn->prepare("SELECT class_name, semester FROM users WHERE id = ?");
            $uStmt->bind_param("i", $student_id);
            $uStmt->execute();
            $uRow = $uStmt->get_result()->fetch_assoc();
            $class_name = $uRow['class_name'] ?? '';
            $semester = $uRow['semester'] ?? '';

            // Fetch subjects
            $subQuery = $conn->prepare("SELECT id, subject_name FROM faculty_subjects WHERE class_name = ? AND semester = ?");
            $subQuery->bind_param("ss", $class_name, $semester);
            $subQuery->execute();
            $subjects = $subQuery->get_result();
            ?>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Select Subject:</label>
                    <select name="subject_id" id="subjectSelect" required onchange="loadTopics()">
                        <option value="">-- Choose Subject --</option>
                        <?php while ($sub = $subjects->fetch_assoc()) { ?>
                            <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Topic Covered Type:</label>
                    <select name="topic_type" required>
                        <option value="Syllabus Topic">Syllabus Topic</option>
                        <option value="Other Extra Knowledge">Other Extra Knowledge</option>
                        <option value="Exam Related Discussion">Exam Related Discussion</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Lecture Start Time:</label>
                    <input type="time" name="lecture_start_time" required>
                </div>
                <div class="form-group">
                    <label>Lecture End Time:</label>
                    <input type="time" name="lecture_end_time" required>
                </div>
            </div>

            <!-- TOPICS SECTION (DYNAMICALY LOADED) -->
            <div id="topicsWrapper" style="display:none; margin-top: 1rem; padding: 1.5rem; background: var(--bg-2); border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; margin: 0;">Select Covered Topics</h3>
                    <div id="unitSelectorContainer" style="width: 200px;">
                        <!-- Unit Dropdown will be injected here -->
                    </div>
                </div>
                
                <div id="topicsContainer">
                    <!-- Topics per Unit will be loaded here -->
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label>Any Assignment from Faculty:</label>
                <textarea name="assignment" placeholder="Write assignment details" style="height: 80px;"></textarea>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary btn-full">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadTopics() {
        const subId = document.getElementById('subjectSelect').value;
        const wrapper = document.getElementById('topicsWrapper');
        const container = document.getElementById('topicsContainer');
        const unitSelectorContainer = document.getElementById('unitSelectorContainer');
        
        if (!subId) {
            wrapper.style.display = 'none';
            return;
        }

        container.innerHTML = '<p style="color:var(--text-2);">Loading units and topics...</p>';
        unitSelectorContainer.innerHTML = '';
        wrapper.style.display = 'block';

        try {
            const response = await fetch(`get_topics_ajax.php?subject_id=${subId}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (result.data.length === 0) {
                    container.innerHTML = '<p>No topics found for this subject.</p>';
                    return;
                }

                // Create Unit Dropdown
                let unitSelectHtml = `<select id="unitSelect" onchange="showUnitTopics()" style="padding: 6px 12px; font-size: 13px;">`;
                unitSelectHtml += `<option value="">-- Select Unit --</option>`;
                
                let topicsHtml = '';
                result.data.forEach((unit, index) => {
                    unitSelectHtml += `<option value="unit_${unit.id}">Unit ${unit.unit_no}</option>`;
                    
                    topicsHtml += `
                        <div id="unit_${unit.id}" class="unit-topics-box" style="display: none;">
                            <p style="font-weight: bold; color: var(--accent); font-size: 13px; text-transform: uppercase; margin-bottom: 12px;">Unit ${unit.unit_no}: ${unit.unit_name}</p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                    `;
                    unit.topics.forEach(topic => {
                        topicsHtml += `
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; background: var(--bg); border-radius: 6px; border: 1px solid var(--border);">
                                <input type="checkbox" name="topics[]" value="${topic.topic_name}" style="width: 18px; height: 18px;">
                                <span style="font-size: 14px;">${topic.topic_name}</span>
                            </label>
                        `;
                    });
                    topicsHtml += `</div></div>`;
                });
                unitSelectHtml += `</select>`;
                
                unitSelectorContainer.innerHTML = unitSelectHtml;
                container.innerHTML = topicsHtml;

                // Auto-select first unit if available
                if (result.data.length > 0) {
                    const select = document.getElementById('unitSelect');
                    select.selectedIndex = 1;
                    showUnitTopics();
                }

            } else {
                container.innerHTML = `<p style="color:var(--error);">${result.message}</p>`;
            }
        } catch (error) {
            container.innerHTML = '<p style="color:var(--error);">Failed to load topics.</p>';
        }
    }

    function showUnitTopics() {
        const selectedUnitId = document.getElementById('unitSelect').value;
        const boxes = document.querySelectorAll('.unit-topics-box');
        
        boxes.forEach(box => {
            if (box.id === selectedUnitId) {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
