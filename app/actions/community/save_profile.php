<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/community/profile.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// User table fields
$class_name = trim($_POST['class_name'] ?? '');
$semester   = trim($_POST['semester']   ?? '');
$roll_no     = trim($_POST['roll_no']    ?? '');
$emp_id      = trim($_POST['emp_id']     ?? '');
$linkedin_url = trim($_POST['linkedin_url'] ?? '');

// Profile table fields
$branch         = trim($_POST['branch']         ?? '');
$skills         = trim($_POST['skills']         ?? '');
$expertise_area = trim($_POST['expertise_area'] ?? '');
$company        = trim($_POST['company']        ?? '');
$designation    = trim($_POST['designation']    ?? '');
$bio            = trim($_POST['bio']            ?? '');

if (empty($branch)) {
    $_SESSION['profile_error'] = "Branch is required.";
    header("Location: ../../../public/community/profile.php");
    exit;
}

// Update users table
$uStmt = $conn->prepare("UPDATE users SET class_name = ?, semester = ?, roll_no = ?, emp_id = ?, linkedin_url = ? WHERE id = ?");
$uStmt->bind_param("sssssi", $class_name, $semester, $roll_no, $emp_id, $linkedin_url, $user_id);
$uStmt->execute();

// Check if profile exists
$chk = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();

if ($chk->get_result()->num_rows > 0) {
    // Update profile
    $stmt = $conn->prepare("UPDATE profiles SET branch = ?, skills = ?, expertise_area = ?, company = ?, designation = ?, bio = ? WHERE user_id = ?");
    $stmt->bind_param("ssssssi", $branch, $skills, $expertise_area, $company, $designation, $bio, $user_id);
} else {
    // Insert profile
    $stmt = $conn->prepare("INSERT INTO profiles (user_id, branch, skills, expertise_area, company, designation, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $branch, $skills, $expertise_area, $company, $designation, $bio);
}

if ($stmt->execute()) {
    $_SESSION['profile_success'] = "Profile updated successfully!";
    header("Location: ../../../public/community/profile.php");
} else {
    $_SESSION['profile_error'] = "Database error: " . $conn->error;
    header("Location: ../../../public/community/profile.php");
}
?>
