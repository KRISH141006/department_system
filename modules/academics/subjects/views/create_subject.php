<?php
require_once __DIR__ . '/../../../../shared/middleware/auth.php';
require_once __DIR__ . '/../../../../shared/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../../../../public/dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$pStmt = $conn->prepare("SELECT bio FROM profiles WHERE user_id = ?");
$pStmt->bind_param("i", $faculty_id);
$pStmt->execute();
$profile = $pStmt->get_result()->fetch_assoc();

$subject_id = (int) ($_GET['id'] ?? 0);
$subject_data = null;
$units_data = [];

if ($subject_id) {
    // New query joining through class_subjects and classes
    $sStmt = $conn->prepare("
        SELECT s.*, c.name as class_name, c.semester, c.branch 
        FROM faculty_subjects fs 
        JOIN class_subjects cs ON fs.class_subject_id = cs.id 
        JOIN subjects s ON cs.subject_id = s.id 
        JOIN classes c ON cs.class_id = c.id 
        WHERE s.id = ? AND fs.faculty_id = ?
    ");
    $sStmt->bind_param("ii", $subject_id, $faculty_id);
    $sStmt->execute();
    $subject_data = $sStmt->get_result()->fetch_assoc();

    if ($subject_data) {
        // Updated table: units
        $uStmt = $conn->prepare("SELECT * FROM units WHERE subject_id = ? ORDER BY unit_no ASC");
        $uStmt->bind_param("i", $subject_id);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        while ($uRow = $uRes->fetch_assoc()) {
            // Updated table: topics
            $tStmt = $conn->prepare("SELECT name FROM topics WHERE unit_id = ?");
            $tStmt->bind_param("i", $uRow['id']);
            $tStmt->execute();
            $tRes = $tStmt->get_result();
            $topics = [];
            while ($tRow = $tRes->fetch_assoc()) {
                $topics[] = $tRow['name'];
            }
            $uRow['topics'] = implode("\n", $topics);
            $units_data[] = $uRow;
        }
    } else {
        $subject_id = 0; // Reset if not found or not owned
    }
}

$page_title = $subject_id ? "Edit Subject" : "Create Subject";
require_once __DIR__ . '/../../../../shared/layout/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h2 style="margin-bottom: 1.5rem;"><?= $subject_id ? "Edit" : "Create" ?> Subject</h2>

        <form action="../../../../app/actions/academics/save_subject.php" method="POST" id="subjectForm">
            <?php if ($subject_id): ?>
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <?php endif; ?>

            <div class="form-group" style="margin-bottom: 2rem; padding: 1rem; background: var(--bg-2); border-radius: 8px; border: 1px dashed var(--border);">
                <label class="checkbox-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_elective" id="isElective" value="1" <?= ($subject_data['type'] ?? '') === 'elective' ? 'checked' : '' ?> style="width: 20px; height: 20px;" onchange="toggleFields()">
                    <span style="font-weight: 600; color: var(--text); font-size: 1.1rem;">Is this an Elective Subject?</span>
                </label>
                <p style="font-size: 0.85rem; color: var(--text-2); margin-top: 5px; margin-left: 30px;">
                    Students will receive an enrollment request and must accept to join.
                </p>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Subject Name</label>
                    <input type="text" name="subject_name" value="<?= htmlspecialchars($subject_data['name'] ?? '') ?>" placeholder="e.g. Web Development" required>
                </div>
                <div class="form-group">
                    <label>Subject Code <span style="color:red;">*</span></label>
                    <input type="text" name="subject_code" value="<?= htmlspecialchars($subject_data['code'] ?? '') ?>" placeholder="e.g. IT301" required>
                </div>
            </div>

            <div class="form-group" id="classGroup">
                <label>Target Class</label>
                <input type="text" name="class_name" value="<?= htmlspecialchars($subject_data['class_name'] ?? '') ?>" placeholder="e.g. 4EK1" id="classInput" oninput="autoSelectSemester()">
            </div>

            <div class="form-group">
                <label>Target Semester</label>
                <input type="hidden" name="semester" id="semesterHidden" value="<?= $subject_data['semester'] ?? '' ?>">
                <select id="semesterSelect" disabled style="background: var(--bg-2); cursor: not-allowed; opacity: 0.8;">
                    <option value="">-- Auto-selected --</option>
                    <?php 
                    for($i=1; $i<=8; $i++) {
                        $val = $i;
                        $sel = ($subject_data && $subject_data['semester'] == $val) ? 'selected' : '';
                        echo "<option value='{$val}' {$sel}>Semester {$i}</option>";
                    }
                    ?>
                </select>
                <p style="font-size: 0.75rem; color: var(--text-3); margin-top: 5px;">Semester is derived from class code (e.g., 4th for 4EK1).</p>
            </div>

            <div id="unitsContainer">
                <?php if ($subject_id && !empty($units_data)): ?>
                    <?php foreach ($units_data as $index => $unit): ?>
                        <div class="unit-box" style="margin-top: 2rem; padding: 1.5rem; border: 1px solid var(--border); border-radius: 8px; position: relative;">
                            <?php if ($index > 0): ?>
                                <button type="button" onclick="removeUnit(this)" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--error); cursor: pointer; font-weight: bold;">Remove</button>
                            <?php endif; ?>
                            <h3 style="margin-bottom: 1rem;">Unit <?= $index + 1 ?></h3>
                            <div class="form-group">
                                <label>Unit Name</label>
                                <input type="text" name="unit_names[]" value="<?= htmlspecialchars($unit['name']) ?>" placeholder="Unit <?= $index + 1 ?> Name" required>
                            </div>
                            <div class="form-group">
                                <label>Topics (Line by line)</label>
                                <textarea name="unit_topics[]" placeholder="Enter topics line by line" style="height: 100px;" required><?= htmlspecialchars($unit['topics']) ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="unit-box" style="margin-top: 2rem; padding: 1.5rem; border: 1px solid var(--border); border-radius: 8px; position: relative;">
                        <h3 style="margin-bottom: 1rem;">Unit 1</h3>
                        <div class="form-group">
                            <label>Unit Name</label>
                            <input type="text" name="unit_names[]" placeholder="Unit 1 Name" required>
                        </div>
                        <div class="form-group">
                            <label>Topics (Line by line)</label>
                            <textarea name="unit_topics[]" placeholder="Enter topics line by line" style="height: 100px;" required></textarea>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="addUnit()">+ Add Unit</button>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 2rem;">
                <?= $subject_id ? "Update" : "Confirm Create" ?> Subject
            </button>
        </form>
    </div>
</div>

<script>
    let unitCount = <?= count($units_data) ?: 1 ?>;

    function autoSelectSemester() {
        const classInput = document.getElementById('classInput');
        const semesterSelect = document.getElementById('semesterSelect');
        const semesterHidden = document.getElementById('semesterHidden');
        const classVal = classInput.value.trim();
        
        if (classVal.length > 0) {
            const firstChar = classVal.charAt(0);
            if (!isNaN(firstChar) && firstChar >= 1 && firstChar <= 8) {
                semesterSelect.value = firstChar;
                semesterHidden.value = firstChar;
            } else {
                semesterSelect.value = "";
                semesterHidden.value = "";
            }
        }
    }

    function toggleFields() {
        const isElective = document.getElementById('isElective').checked;
        const classGroup = document.getElementById('classGroup');
        const classInput = document.getElementById('classInput');
        const semesterSelect = document.getElementById('semesterSelect');
        const semesterHidden = document.getElementById('semesterHidden');
        
        if (isElective) {
            classGroup.style.display = 'none';
            classInput.removeAttribute('required');
            classInput.value = 'ALL';
            semesterSelect.disabled = false;
            semesterSelect.style.cursor = 'default';
            semesterSelect.style.opacity = '1';
        } else {
            classGroup.style.display = 'block';
            classInput.setAttribute('required', 'required');
            if (classInput.value === 'ALL') classInput.value = '';
            semesterSelect.disabled = true;
            semesterSelect.style.cursor = 'not-allowed';
            semesterSelect.style.opacity = '0.8';
            autoSelectSemester();
        }
    }

    window.onload = function() {
        toggleFields();
        if (document.getElementById('classInput').value !== 'ALL') {
            autoSelectSemester();
        }
    };

    document.getElementById('subjectForm').onsubmit = function() {
        if (document.getElementById('isElective').checked) {
            document.getElementById('semesterHidden').value = document.getElementById('semesterSelect').value;
        }
    };

    function addUnit() {
        unitCount++;
        const container = document.getElementById('unitsContainer');
        const unitDiv = document.createElement('div');
        unitDiv.className = 'unit-box';
        unitDiv.style.cssText = 'margin-top: 2rem; padding: 1.5rem; border: 1px solid var(--border); border-radius: 8px; position: relative;';
        unitDiv.innerHTML = `
            <button type="button" onclick="removeUnit(this)" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--error); cursor: pointer; font-weight: bold;">Remove</button>
            <h3 style="margin-bottom: 1rem;">Unit ${unitCount}</h3>
            <div class="form-group">
                <label>Unit Name</label>
                <input type="text" name="unit_names[]" placeholder="Unit ${unitCount} Name" required>
            </div>
            <div class="form-group">
                <label>Topics (Line by line)</label>
                <textarea name="unit_topics[]" placeholder="Enter topics line by line" style="height: 100px;" required></textarea>
            </div>
        `;
        container.appendChild(unitDiv);
        updateUnitNumbers();
    }

    function removeUnit(btn) {
        btn.closest('.unit-box').remove();
        updateUnitNumbers();
    }

    function updateUnitNumbers() {
        const units = document.querySelectorAll('.unit-box h3');
        unitCount = units.length;
        units.forEach((h3, index) => {
            h3.textContent = `Unit ${index + 1}`;
        });
    }
</script>

<?php require_once __DIR__ . '/../../../../shared/layout/footer.php'; ?>
