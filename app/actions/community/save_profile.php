<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/community/profile.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- 1. Basic Information (Users Table) ---
$name = trim($_POST['name'] ?? '');
if (empty($name)) {
    $_SESSION['profile_error'] = "Full Name is required.";
    header("Location: ../../../public/community/profile.php");
    exit;
}

$uStmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
$uStmt->bind_param("si", $name, $user_id);
$uStmt->execute();
$_SESSION['name'] = $name;

// --- 2. Common Profile Fields (Profiles Table) ---
$linkedin_url  = trim($_POST['linkedin_url'] ?? '');
$github_url    = trim($_POST['github_url'] ?? '');
$leetcode_url  = trim($_POST['leetcode_url'] ?? '');
$portfolio_url = trim($_POST['portfolio_url'] ?? '');
$skills        = trim($_POST['skills'] ?? '');
$hobbies       = trim($_POST['hobbies'] ?? '');
$bio           = trim($_POST['bio'] ?? '');

$pStmt = $conn->prepare("
    INSERT INTO profiles (user_id, linkedin_url, github_url, leetcode_url, portfolio_url, skills, hobbies, bio)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        linkedin_url = VALUES(linkedin_url),
        github_url = VALUES(github_url),
        leetcode_url = VALUES(leetcode_url),
        portfolio_url = VALUES(portfolio_url),
        skills = VALUES(skills),
        hobbies = VALUES(hobbies),
        bio = VALUES(bio)
");
$pStmt->bind_param("isssssss", $user_id, $linkedin_url, $github_url, $leetcode_url, $portfolio_url, $skills, $hobbies, $bio);
$pStmt->execute();

// --- 3. Role-Specific Data ---
try {
    if ($role === 'student') {
        $class_id    = (int) ($_POST['class_id'] ?? 0);
        $roll_no     = strtoupper(trim($_POST['roll_no'] ?? ''));
        $gr_no       = trim($_POST['gr_no'] ?? '');
        $target_role = trim($_POST['target_role'] ?? '');

        if (!$class_id || !$roll_no) {
            throw new Exception("Class and Roll Number are required for students.");
        }

        // Generate a GR Number if not provided (it's NOT NULL UNIQUE)
        if (empty($gr_no)) {
            // Check if user already has a GR number
            $checkGR = $conn->prepare("SELECT gr_no FROM students WHERE user_id = ?");
            $checkGR->bind_param("i", $user_id);
            $checkGR->execute();
            $existing = $checkGR->get_result()->fetch_assoc();
            if ($existing && !empty($existing['gr_no'])) {
                $gr_no = $existing['gr_no'];
            } else {
                $gr_no = "GR" . str_pad($user_id, 6, "0", STR_PAD_LEFT);
            }
        }

        $sStmt = $conn->prepare("
            INSERT INTO students (user_id, class_id, roll_no, gr_no, target_role)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                class_id = VALUES(class_id),
                roll_no = VALUES(roll_no),
                gr_no = VALUES(gr_no),
                target_role = VALUES(target_role)
        ");
        $sStmt->bind_param("iisss", $user_id, $class_id, $roll_no, $gr_no, $target_role);
        $sStmt->execute();

    } elseif ($role === 'faculty') {
        $emp_id             = strtoupper(trim($_POST['emp_id'] ?? ''));
        $is_cc              = isset($_POST['is_cc']) ? 1 : 0;
        $teaching_interests = trim($_POST['teaching_interests'] ?? '');

        if (empty($emp_id)) {
            throw new Exception("Employee ID is required for faculty.");
        }

        $fStmt = $conn->prepare("
            INSERT INTO faculty (user_id, emp_id, is_cc, teaching_interests)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                emp_id = VALUES(emp_id),
                is_cc = VALUES(is_cc),
                teaching_interests = VALUES(teaching_interests)
        ");
        $fStmt->bind_param("isis", $user_id, $emp_id, $is_cc, $teaching_interests);
        $fStmt->execute();

    } elseif ($role === 'expert') {
        $company          = trim($_POST['company'] ?? '');
        $designation      = trim($_POST['designation'] ?? '');
        $expertise_area   = trim($_POST['expertise_area'] ?? '');
        $experience_years = (int) ($_POST['experience_years'] ?? 0);
        $is_alumni        = isset($_POST['is_alumni']) ? 1 : 0;
        $college_name     = trim($_POST['college_name'] ?? '');
        $graduation_year  = trim($_POST['graduation_year'] ?? '');
        $degree           = trim($_POST['degree'] ?? '');

        if (empty($company) || empty($designation)) {
            throw new Exception("Company and Designation are required for experts.");
        }

        $eStmt = $conn->prepare("
            INSERT INTO experts (user_id, company, designation, expertise_area, experience_years, is_alumni, college_name, graduation_year, degree)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                company = VALUES(company),
                designation = VALUES(designation),
                expertise_area = VALUES(expertise_area),
                experience_years = VALUES(experience_years),
                is_alumni = VALUES(is_alumni),
                college_name = VALUES(college_name),
                graduation_year = VALUES(graduation_year),
                degree = VALUES(degree)
        ");
        $eStmt->bind_param("isssiisss", $user_id, $company, $designation, $expertise_area, $experience_years, $is_alumni, $college_name, $graduation_year, $degree);
        $eStmt->execute();
    }

    $_SESSION['profile_success'] = "Profile updated successfully!";
    header("Location: ../../../public/community/profile.php");
} catch (Exception $e) {
    $_SESSION['profile_error'] = $e->getMessage();
    header("Location: ../../../public/community/profile.php");
}
?>
