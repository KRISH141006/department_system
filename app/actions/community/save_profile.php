<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/community/profile.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- User table fields ---
$class_name   = trim($_POST['class_name']   ?? '');
$semester     = trim($_POST['semester']     ?? '');
$roll_no       = trim($_POST['roll_no']      ?? '');
$emp_id        = trim($_POST['emp_id']       ?? '');
$linkedin_url  = trim($_POST['linkedin_url'] ?? '');

// --- Profile table fields (Common) ---
$branch         = trim($_POST['branch']         ?? '');
$skills         = trim($_POST['skills']         ?? '');
$bio            = trim($_POST['bio']            ?? '');
$hobbies        = trim($_POST['hobbies']        ?? '');

// --- Student Specific ---
$github_url     = trim($_POST['github_url']     ?? '');
$leetcode_url   = trim($_POST['leetcode_url']   ?? '');
$portfolio_url  = trim($_POST['portfolio_url']  ?? '');
$target_role    = trim($_POST['target_role']    ?? '');

// --- Faculty/Admin Specific ---
$designation        = trim($_POST['designation']        ?? '');
$teaching_interests = trim($_POST['teaching_interests'] ?? '');
$is_cc              = isset($_POST['is_cc']) ? 1 : 0;
$cc_class           = trim($_POST['cc_class']           ?? '');
$cc_semester        = trim($_POST['cc_semester']        ?? '');

// --- Expert Specific ---
$is_alumni          = isset($_POST['is_alumni']) ? 1 : 0;
$college_name       = trim($_POST['college_name']       ?? '');
$degree             = trim($_POST['degree']             ?? '');
$graduation_year    = trim($_POST['graduation_year']    ?? '');
$experience_years   = (int) ($_POST['experience_years'] ?? 0);
$company            = trim($_POST['company']            ?? '');
$expertise_area     = trim($_POST['expertise_area']     ?? '');

// Expert might also have designation from common field
if ($role === 'expert') {
    $designation = trim($_POST['designation'] ?? '');
}

if (empty($branch)) {
    $_SESSION['profile_error'] = "Branch is required.";
    header("Location: ../../../public/community/profile.php");
    exit;
}

// 1. Update users table
$uStmt = $conn->prepare("UPDATE users SET class_name = ?, semester = ?, roll_no = ?, emp_id = ?, linkedin_url = ? WHERE id = ?");
$uStmt->bind_param("sssssi", $class_name, $semester, $roll_no, $emp_id, $linkedin_url, $user_id);
$uStmt->execute();

// 2. Check if profile exists
$chk = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();

if ($chk->get_result()->num_rows > 0) {
    // Update profile (all columns)
    $stmt = $conn->prepare("
        UPDATE profiles SET 
            branch = ?, skills = ?, expertise_area = ?, company = ?, designation = ?, bio = ?,
            github_url = ?, leetcode_url = ?, portfolio_url = ?, hobbies = ?, target_role = ?,
            is_alumni = ?, college_name = ?, graduation_year = ?, degree = ?, experience_years = ?,
            teaching_interests = ?, is_cc = ?, cc_class = ?, cc_semester = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param(
        "sssssssssssississssii", 
        $branch, $skills, $expertise_area, $company, $designation, $bio,
        $github_url, $leetcode_url, $portfolio_url, $hobbies, $target_role,
        $is_alumni, $college_name, $graduation_year, $degree, $experience_years,
        $teaching_interests, $is_cc, $cc_class, $cc_semester,
        $user_id
    );
} else {
    // Insert profile
    $stmt = $conn->prepare("
        INSERT INTO profiles (
            user_id, branch, skills, expertise_area, company, designation, bio,
            github_url, leetcode_url, portfolio_url, hobbies, target_role,
            is_alumni, college_name, graduation_year, degree, experience_years,
            teaching_interests, is_cc, cc_class, cc_semester
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "isssssssssssississssi",
        $user_id, $branch, $skills, $expertise_area, $company, $designation, $bio,
        $github_url, $leetcode_url, $portfolio_url, $hobbies, $target_role,
        $is_alumni, $college_name, $graduation_year, $degree, $experience_years,
        $teaching_interests, $is_cc, $cc_class, $cc_semester
    );
}

if ($stmt->execute()) {
    $_SESSION['profile_success'] = "Profile updated successfully!";
    header("Location: ../../../public/community/profile.php");
} else {
    $_SESSION['profile_error'] = "Database error: " . $conn->error;
    header("Location: ../../../public/community/profile.php");
}
?>
