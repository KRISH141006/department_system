<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/community/profile.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$branch  = trim($_POST['branch'] ?? '');
$skills  = trim($_POST['skills'] ?? '');

if (empty($branch)) {
    $_SESSION['profile_error'] = "Branch is required.";
    header("Location: ../../../public/community/profile.php");
    exit;
}

// Check if profile exists
$chk = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    // Update
    $stmt = $conn->prepare("UPDATE profiles SET branch = ?, skills = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $branch, $skills, $user_id);
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO profiles (user_id, branch, skills) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $branch, $skills);
}

if ($stmt->execute()) {
    header("Location: ../../../public/dashboard.php");
} else {
    $_SESSION['profile_error'] = "Database error: " . $conn->error;
    header("Location: ../../../public/community/profile.php");
}
