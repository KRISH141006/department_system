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
$name         = trim($_POST['name']         ?? '');
$class_name   = strtoupper(trim($_POST['class_name']   ?? ''));
$semester     = (int) ($_POST['semester']     ?? 0);
$roll_no       = strtoupper(trim($_POST['roll_no']      ?? ''));
$emp_id        = strtoupper(trim($_POST['emp_id']       ?? ''));
$linkedin_url  = trim($_POST['linkedin_url'] ?? '');

// --- CC Fields ---
$cc_class           = strtoupper(trim($_POST['cc_class']           ?? ''));
$cc_semester        = (int) ($_POST['cc_semester']        ?? 0);

// Enforce [Semester][ClassName] convention (e.g., 4EK1)
if ($role === 'student' && $semester > 0 && !empty($class_name)) {
    // Remove leading digits, hyphens, and spaces
    $class_pure = preg_replace('/^[\d\s\-_]+/', '', $class_name);
    $class_pure = str_replace(['-', ' '], '', $class_pure);
    $class_name = $semester . $class_pure;
}

// Check if student is trying to change class/semester when it's already set
if ($role === 'student') {
    $checkStmt = $conn->prepare("SELECT class_name, semester FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $current = $checkStmt->get_result()->fetch_assoc();

    if (!empty($current['class_name']) && !empty($current['semester'])) {
        // Prevent changing these fields (they already follow the convention if they were set after this update)
        $class_name = $current['class_name'];
        $semester = $current['semester'];
    }
}

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

if ($is_cc && $cc_semester > 0 && !empty($cc_class)) {
    $cc_pure = preg_replace('/^[\d\s\-_]+/', '', $cc_class);
    $cc_pure = str_replace(['-', ' '], '', $cc_pure);
    $cc_class = $cc_semester . $cc_pure;
}

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

if (empty($branch) || empty($name)) {
    $_SESSION['profile_error'] = "Name and Branch are required.";
    header("Location: ../../../public/community/profile.php");
    exit;
}

// 1. Update users table
$uStmt = $conn->prepare("UPDATE users SET name = ?, class_name = ?, semester = ?, roll_no = ?, emp_id = ?, linkedin_url = ? WHERE id = ?");
$uStmt->bind_param("ssssssi", $name, $class_name, $semester, $roll_no, $emp_id, $linkedin_url, $user_id);
$uStmt->execute();

// Update session name too
$_SESSION['name'] = $name;

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
