<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

if (!has_permission('view_faculty_dashboard')) {
    header("Location: ../dashboard.php");
    exit();
}

$faculty_id = (int) $_SESSION['user_id'];
$pStmt = $conn->prepare("SELECT branch FROM profiles WHERE user_id = ?");
$pStmt->bind_param("i", $faculty_id);
$pStmt->execute();
$profile = $pStmt->get_result()->fetch_assoc();
$branch = $profile['branch'] ?? '';

$subject_id = (int) ($_GET['id'] ?? 0);
$subject_data = null;
$units_data = [];

if ($subject_id) {
    $sStmt = $conn->prepare("SELECT * FROM faculty_subjects WHERE id = ? AND faculty_id = ?");
    $sStmt->bind_param("ii", $subject_id, $faculty_id);
    $sStmt->execute();
    $subject_data = $sStmt->get_result()->fetch_assoc();

    if ($subject_data) {
        $uStmt = $conn->prepare("SELECT * FROM faculty_units WHERE subject_id = ? ORDER BY unit_no ASC");
        $uStmt->bind_param("i", $subject_id);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        while ($uRow = $uRes->fetch_assoc()) {
            $tStmt = $conn->prepare("SELECT topic_name FROM faculty_topics WHERE unit_id = ?");
            $tStmt->bind_param("i", $uRow['id']);
            $tStmt->execute();
            $tRes = $tStmt->get_result();
            $topics = [];
            while ($tRow = $tRes->fetch_assoc()) {
                $topics[] = $tRow['topic_name'];
            }
            $uRow['topics'] = implode("\n", $topics);
            $units_data[] = $uRow;
        }
    } else {
        $subject_id = 0; // Reset if not found or not owned
    }
}

$page_title = $subject_id ? "Edit Subject" : "Create Subject";
require_once __DIR__ . '/../../app/includes/header.php';
?>

<div class="wrapper" style="padding: 2rem;">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h2 style="margin-bottom: 1.5rem;"><?= $subject_id ? "Edit" : "Create" ?> Subject</h2>

        <form action="../../app/actions/academics/save_subject.php" method="POST" id="subjectForm">
            <?php if ($subject_id): ?>
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <?php endif; ?>
            <div class="grid-2">
                <div class="form-group">
                    <label>Subject Name</label>
                    <input type="text" name="subject_name" value="<?= htmlspecialchars($subject_data['subject_name'] ?? '') ?>" placeholder="e.g. Web Development" required>
                </div>
                <div class="form-group">
                    <label>Department / Branch</label>
                    <input type="text" name="branch" value="<?= htmlspecialchars($subject_data['branch'] ?? $branch) ?>" placeholder="e.g. IT" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Target Class</label>
                    <input type="text" name="class_name" value="<?= htmlspecialchars($subject_data['class_name'] ?? '') ?>" placeholder="e.g. 4EK1" required>
                </div>
                <div class="form-group">
                    <label>Target Semester</label>
                    <select name="semester" required>
                        <option value="">-- Select Semester --</option>
                        <?php 
                        for($i=1; $i<=8; $i++) {
                            $val = $i;
                            $sel = ($subject_data && $subject_data['semester'] == $val) ? 'selected' : '';
                            echo "<option value='{$val}' {$sel}>{$i}</option>";
                        }
                        ?>
                    </select>
                </div>
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
                                <input type="text" name="unit_names[]" value="<?= htmlspecialchars($unit['unit_name']) ?>" placeholder="Unit <?= $index + 1 ?> Name" required>
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

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
