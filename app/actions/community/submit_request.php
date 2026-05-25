<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../../public/dashboard.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$skill   = trim($_POST['skill'] ?? '');

if (empty($skill)) {
    $_SESSION['req_error'] = "Please enter a skill.";
    header("Location: ../../../public/community/request.php");
    exit;
}

$stmt = $conn->prepare("INSERT INTO requests (user_id, skill) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $skill);

if ($stmt->execute()) {
    $_SESSION['req_success'] = "Skill test requested successfully!";
} else {
    $_SESSION['req_error'] = "Could not submit request. Try again.";
}

header("Location: ../../../public/community/request.php");
exit;
