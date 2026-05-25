<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/academics/student_dashboard.php");
    exit();
}

// In the new system, maybe we don't have can_give_feedback session exactly like this, but let's keep the logic or adapt it.
$unit_no = $_POST['unit_no'] ?? 1;
$selectedTopics = $_POST['topics'] ?? [];

$stmt = $conn->prepare("UPDATE topic_progress SET is_covered = 0 WHERE subject='IWT' AND unit_no=?");
$stmt->bind_param("i", $unit_no);
$stmt->execute();

foreach ($selectedTopics as $topic) {
    $check = $conn->prepare("SELECT id FROM topic_progress WHERE subject='IWT' AND unit_no=? AND topic_name=?");
    $check->bind_param("is", $unit_no, $topic);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $update = $conn->prepare("UPDATE topic_progress SET is_covered=1, updated_by=? WHERE subject='IWT' AND unit_no=? AND topic_name=?");
        $update->bind_param("iis", $_SESSION['user_id'], $unit_no, $topic);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO topic_progress (subject, unit_no, topic_name, is_covered, updated_by) VALUES ('IWT', ?, ?, 1, ?)");
        $insert->bind_param("isi", $unit_no, $topic, $_SESSION['user_id']);
        $insert->execute();
    }
}

if (isset($_GET['from']) && $_GET['from'] == 'feedback') {
    echo "<script>
        alert('Topics selected successfully');
        window.location.href='../../../public/academics/lecture_feedback.php';
    </script>";
} else {
    echo "<script>
        alert('Covered topics confirmed successfully');
        window.location.href='../../../public/academics/units.php?subject=IWT&unit=$unit_no';
    </script>";
}
?>