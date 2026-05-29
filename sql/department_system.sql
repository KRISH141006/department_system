-- Unified Database Schema for Department Community & Academic Management System

DROP DATABASE IF EXISTS department_system;
CREATE DATABASE department_system;
USE department_system;

-- ======================
-- UNIFIED USERS TABLE
-- ======================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('student','faculty','expert','admin') NOT NULL,
    pac_category ENUM('premium', 'average', 'challenged') DEFAULT 'average',
    class_name VARCHAR(50),
    semester INT,
    batch VARCHAR(30),
    roll_no VARCHAR(50),
    emp_id VARCHAR(50),
    linkedin_url VARCHAR(255),
    is_verified TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- PROFILES TABLE
-- ======================
CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    branch VARCHAR(100),
    skills TEXT,
    expertise_area TEXT,
    company VARCHAR(255),
    designation VARCHAR(255),
    bio TEXT,
    github_url VARCHAR(255),
    leetcode_url VARCHAR(255),
    portfolio_url VARCHAR(255),
    hobbies TEXT,
    target_role VARCHAR(100),
    is_alumni TINYINT DEFAULT 0,
    college_name VARCHAR(255),
    graduation_year VARCHAR(10),
    degree VARCHAR(100),
    experience_years INT,
    teaching_interests TEXT,
    is_cc TINYINT DEFAULT 0,
    cc_class VARCHAR(50),
    cc_semester INT,
    community_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ======================
-- MODULE 1: ACADEMICS
-- ======================
CREATE TABLE IF NOT EXISTS faculty_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    branch VARCHAR(100),
    class_name VARCHAR(50),
    semester INT,
    is_elective TINYINT DEFAULT 0,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_electives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES faculty_subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role ENUM('student','faculty','expert','admin') NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    icon_class VARCHAR(50) DEFAULT 'fa-star',
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS faculty_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    unit_no INT NOT NULL,
    unit_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES faculty_subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS faculty_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    topic_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (unit_id) REFERENCES faculty_units(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS topic_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(100) NOT NULL,
    unit_no INT NOT NULL,
    topic_name VARCHAR(255) NOT NULL,
    is_covered TINYINT DEFAULT 0,
    is_verified TINYINT DEFAULT 0,
    verification_count INT DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faculty_feedback_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS faculty_feedback_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('rating', 'mcq', 'text') DEFAULT 'rating',
    options TEXT NULL,
    FOREIGN KEY (form_id) REFERENCES faculty_feedback_forms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_faculty_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    question_id INT NOT NULL,
    student_id INT NOT NULL,
    rating INT NULL,
    answer_text TEXT NULL,
    FOREIGN KEY (form_id) REFERENCES faculty_feedback_forms(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES faculty_feedback_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lecture_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    lecture_start_time TIME NOT NULL,
    lecture_end_time TIME NOT NULL,
    topic_type VARCHAR(100),
    assignment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES faculty_subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feedback_selector (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selected_student_id INT NOT NULL,
    selected_date DATE NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (selected_student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES faculty_subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS continuous_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    subject_id INT NULL,
    feedback_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES faculty_subjects(id) ON DELETE CASCADE
);

-- ======================
-- MODULE 2: PRODUCTIVITY
-- ======================

CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS task_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#000000',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ======================
-- FACULTY ASSIGNMENTS
-- ======================
CREATE TABLE IF NOT EXISTS faculty_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_details TEXT,
    class_name VARCHAR(50),
    semester INT,
    pac_category ENUM('premium', 'average', 'challenged', 'all') DEFAULT 'all',
    deadline DATETIME NULL,
    resource_path VARCHAR(255) NULL,
    resource_name VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task TEXT NOT NULL,
    description TEXT,
    deadline DATETIME NULL,
    category_id INT NULL,
    priority_id INT NULL,
    is_completed TINYINT DEFAULT 0,
    faculty_assignment_id INT NULL,
    resource_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (priority_id) REFERENCES task_priorities(id) ON DELETE SET NULL,
    FOREIGN KEY (faculty_assignment_id) REFERENCES faculty_assignments(id) ON DELETE CASCADE
);

-- ======================
-- STUDENT SUBMISSIONS
-- ======================
CREATE TABLE IF NOT EXISTS student_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_path VARCHAR(255) NOT NULL,
    submission_name VARCHAR(255) NULL,
    grade VARCHAR(20) DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ======================
-- LIVE MEETINGS
-- ======================
CREATE TABLE IF NOT EXISTS live_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    topic VARCHAR(255) NOT NULL,
    room_code VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('live', 'ended') DEFAULT 'live',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ======================
-- MODULE 3: COMMUNITY
-- ======================
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,           
    skill VARCHAR(150) NOT NULL,
    status ENUM('pending','accepted','completed') DEFAULT 'pending',
    reviewer_id INT NULL,           
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNIQUE NOT NULL,
    reviewer_id INT NOT NULL,
    marks TINYINT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ======================
-- ======================
-- SEED DATA
-- ======================

-- Base Permissions
INSERT INTO permissions (permission_name, description) VALUES
('view_student_dashboard', 'Access to student main dashboard'),
('view_faculty_dashboard', 'Access to faculty main dashboard'),
('view_expert_dashboard', 'Access to expert/reviewer dashboard'),
('view_admin_dashboard', 'Access to system admin panel'),
('submit_feedback', 'Permission to submit anonymous/faculty feedback'),
('manage_subjects', 'Permission to create/edit subjects and units'),
('manage_tasks', 'Permission to manage personal productivity tasks'),
('request_validation', 'Permission to create community validation requests'),
('review_requests', 'Permission to review and mark community requests'),
('manage_users', 'Full user management access'),
('select_electives', 'Access to elective subject selection interface');

-- Role-Permission Mapping
-- Admin
INSERT INTO role_permissions (role, permission_id) 
SELECT 'admin', id FROM permissions;

-- Faculty
INSERT INTO role_permissions (role, permission_id)
SELECT 'faculty', id FROM permissions WHERE permission_name IN 
('view_faculty_dashboard', 'submit_feedback', 'manage_subjects', 'review_requests');

-- Student
INSERT INTO role_permissions (role, permission_id)
SELECT 'student', id FROM permissions WHERE permission_name IN 
('view_student_dashboard', 'submit_feedback', 'manage_tasks', 'request_validation', 'select_electives');

-- Expert
INSERT INTO role_permissions (role, permission_id)
SELECT 'expert', id FROM permissions WHERE permission_name IN 
('view_expert_dashboard', 'review_requests');

-- Password for all users is: 1234
-- Classes changed to odd semesters: 1, 3, 5, 7
-- Class distribution: 1st sem = 2 classes, 3rd sem = 3 classes, 5th sem = 3 classes, 7th sem = 2 classes
-- Total faculty users = 15

INSERT INTO users (id, name, email, password, role, pac_category, class_name, semester, roll_no, emp_id, is_verified) VALUES
(1, 'System Admin', 'admin@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'admin', NULL, NULL, NULL, NULL, NULL, 1),
(2, 'Dr. Asha Patel', 'faculty1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F001', 1),
(3, 'Prof. Mehul Shah', 'faculty2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F002', 1),
(4, 'Dr. Ritesh Joshi', 'faculty3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F003', 1),
(5, 'Prof. Nisha Mehta', 'faculty4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F004', 1),
(6, 'Dr. Kiran Desai', 'faculty5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F005', 1),
(7, 'Prof. Bhavesh Trivedi', 'faculty6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F006', 1),
(8, 'Dr. Pooja Vyas', 'faculty7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F007', 1),
(9, 'Prof. Harshil Dave', 'faculty8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F008', 1),
(10, 'Dr. Neha Parmar', 'faculty9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F009', 1),
(11, 'Prof. Jignesh Rathod', 'faculty10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F010', 1),
(12, 'Dr. Komal Shah', 'faculty11@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F011', 1),
(13, 'Prof. Dhruv Patel', 'faculty12@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F012', 1),
(14, 'Dr. Hetal Pandya', 'faculty13@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F013', 1),
(15, 'Prof. Manan Bhatt', 'faculty14@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F014', 1),
(16, 'Dr. Rupal Thakkar', 'faculty15@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, NULL, 'F015', 1),
(17, 'Expert One', 'expert1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert', NULL, NULL, NULL, NULL, NULL, 1),
(18, 'Expert Two', 'expert2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert', NULL, NULL, NULL, NULL, NULL, 1),
(100, 'Aarav Mehta', 's_1ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '1ek-1', 1, '1ek-1-01', NULL, 1),
(101, 'Vihaan Joshi', 's_1ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '1ek-1', 1, '1ek-1-02', NULL, 1),
(102, 'Vivaan Desai', 's_1ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '1ek-1', 1, '1ek-1-03', NULL, 1),
(103, 'Aditya Parmar', 's_1ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-1', 1, '1ek-1-04', NULL, 1),
(104, 'Arjun Vyas', 's_1ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-1', 1, '1ek-1-05', NULL, 1),
(105, 'Sai Trivedi', 's_1ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-1', 1, '1ek-1-06', NULL, 1),
(106, 'Reyansh Rathod', 's_1ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '1ek-1', 1, '1ek-1-07', NULL, 1),
(107, 'Krishna Dave', 's_1ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '1ek-1', 1, '1ek-1-08', NULL, 1),
(108, 'Ishaan Patel', 's_1ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-1', 1, '1ek-1-09', NULL, 1),
(109, 'Kabir Shah', 's_1ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-1', 1, '1ek-1-10', NULL, 1),
(110, 'Aarav Mehta', 's_1ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-2', 1, '1ek-2-01', NULL, 1),
(111, 'Vihaan Joshi', 's_1ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '1ek-2', 1, '1ek-2-02', NULL, 1),
(112, 'Vivaan Desai', 's_1ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '1ek-2', 1, '1ek-2-03', NULL, 1),
(113, 'Aditya Parmar', 's_1ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-2', 1, '1ek-2-04', NULL, 1),
(114, 'Arjun Vyas', 's_1ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '1ek-2', 1, '1ek-2-05', NULL, 1),
(115, 'Sai Trivedi', 's_1ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '1ek-2', 1, '1ek-2-06', NULL, 1),
(116, 'Reyansh Rathod', 's_1ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '1ek-2', 1, '1ek-2-07', NULL, 1),
(117, 'Krishna Dave', 's_1ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '1ek-2', 1, '1ek-2-08', NULL, 1),
(118, 'Ishaan Patel', 's_1ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '1ek-2', 1, '1ek-2-09', NULL, 1),
(119, 'Kabir Shah', 's_1ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '1ek-2', 1, '1ek-2-10', NULL, 1),
(120, 'Aarav Desai', 's_3ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '3ek-1', 3, '3ek-1-01', NULL, 1),
(121, 'Vihaan Parmar', 's_3ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-1', 3, '3ek-1-02', NULL, 1),
(122, 'Vivaan Vyas', 's_3ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-1', 3, '3ek-1-03', NULL, 1),
(123, 'Aditya Trivedi', 's_3ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-1', 3, '3ek-1-04', NULL, 1),
(124, 'Arjun Rathod', 's_3ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-1', 3, '3ek-1-05', NULL, 1),
(125, 'Sai Dave', 's_3ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-1', 3, '3ek-1-06', NULL, 1),
(126, 'Reyansh Patel', 's_3ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-1', 3, '3ek-1-07', NULL, 1),
(127, 'Krishna Shah', 's_3ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-1', 3, '3ek-1-08', NULL, 1),
(128, 'Ishaan Mehta', 's_3ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-1', 3, '3ek-1-09', NULL, 1),
(129, 'Kabir Joshi', 's_3ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-1', 3, '3ek-1-10', NULL, 1),
(130, 'Aarav Desai', 's_3ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-2', 3, '3ek-2-01', NULL, 1),
(131, 'Vihaan Parmar', 's_3ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-2', 3, '3ek-2-02', NULL, 1),
(132, 'Vivaan Vyas', 's_3ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-2', 3, '3ek-2-03', NULL, 1),
(133, 'Aditya Trivedi', 's_3ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '3ek-2', 3, '3ek-2-04', NULL, 1),
(134, 'Arjun Rathod', 's_3ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-2', 3, '3ek-2-05', NULL, 1),
(135, 'Sai Dave', 's_3ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '3ek-2', 3, '3ek-2-06', NULL, 1),
(136, 'Reyansh Patel', 's_3ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-2', 3, '3ek-2-07', NULL, 1),
(137, 'Krishna Shah', 's_3ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-2', 3, '3ek-2-08', NULL, 1),
(138, 'Ishaan Mehta', 's_3ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-2', 3, '3ek-2-09', NULL, 1),
(139, 'Kabir Joshi', 's_3ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-2', 3, '3ek-2-10', NULL, 1),
(140, 'Aarav Desai', 's_3ek3_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '3ek-3', 3, '3ek-3-01', NULL, 1),
(141, 'Vihaan Parmar', 's_3ek3_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-3', 3, '3ek-3-02', NULL, 1),
(142, 'Vivaan Vyas', 's_3ek3_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-3', 3, '3ek-3-03', NULL, 1),
(143, 'Aditya Trivedi', 's_3ek3_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-3', 3, '3ek-3-04', NULL, 1),
(144, 'Arjun Rathod', 's_3ek3_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-3', 3, '3ek-3-05', NULL, 1),
(145, 'Sai Dave', 's_3ek3_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-3', 3, '3ek-3-06', NULL, 1),
(146, 'Reyansh Patel', 's_3ek3_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-3', 3, '3ek-3-07', NULL, 1),
(147, 'Krishna Shah', 's_3ek3_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '3ek-3', 3, '3ek-3-08', NULL, 1),
(148, 'Ishaan Mehta', 's_3ek3_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '3ek-3', 3, '3ek-3-09', NULL, 1),
(149, 'Kabir Joshi', 's_3ek3_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '3ek-3', 3, '3ek-3-10', NULL, 1),
(150, 'Aarav Vyas', 's_5ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-1', 5, '5ek-1-01', NULL, 1),
(151, 'Vihaan Trivedi', 's_5ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-1', 5, '5ek-1-02', NULL, 1),
(152, 'Vivaan Rathod', 's_5ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-1', 5, '5ek-1-03', NULL, 1),
(153, 'Aditya Dave', 's_5ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-1', 5, '5ek-1-04', NULL, 1),
(154, 'Arjun Patel', 's_5ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-1', 5, '5ek-1-05', NULL, 1),
(155, 'Sai Shah', 's_5ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-1', 5, '5ek-1-06', NULL, 1),
(156, 'Reyansh Mehta', 's_5ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-1', 5, '5ek-1-07', NULL, 1),
(157, 'Krishna Joshi', 's_5ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-1', 5, '5ek-1-08', NULL, 1),
(158, 'Ishaan Desai', 's_5ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '5ek-1', 5, '5ek-1-09', NULL, 1),
(159, 'Kabir Parmar', 's_5ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-1', 5, '5ek-1-10', NULL, 1),
(160, 'Aarav Vyas', 's_5ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-2', 5, '5ek-2-01', NULL, 1),
(161, 'Vihaan Trivedi', 's_5ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '5ek-2', 5, '5ek-2-02', NULL, 1),
(162, 'Vivaan Rathod', 's_5ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '5ek-2', 5, '5ek-2-03', NULL, 1),
(163, 'Aditya Dave', 's_5ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-2', 5, '5ek-2-04', NULL, 1),
(164, 'Arjun Patel', 's_5ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-2', 5, '5ek-2-05', NULL, 1),
(165, 'Sai Shah', 's_5ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-2', 5, '5ek-2-06', NULL, 1),
(166, 'Reyansh Mehta', 's_5ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-2', 5, '5ek-2-07', NULL, 1),
(167, 'Krishna Joshi', 's_5ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-2', 5, '5ek-2-08', NULL, 1),
(168, 'Ishaan Desai', 's_5ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-2', 5, '5ek-2-09', NULL, 1),
(169, 'Kabir Parmar', 's_5ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '5ek-2', 5, '5ek-2-10', NULL, 1),
(170, 'Aarav Vyas', 's_5ek3_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '5ek-3', 5, '5ek-3-01', NULL, 1),
(171, 'Vihaan Trivedi', 's_5ek3_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-3', 5, '5ek-3-02', NULL, 1),
(172, 'Vivaan Rathod', 's_5ek3_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-3', 5, '5ek-3-03', NULL, 1),
(173, 'Aditya Dave', 's_5ek3_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-3', 5, '5ek-3-04', NULL, 1),
(174, 'Arjun Patel', 's_5ek3_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '5ek-3', 5, '5ek-3-05', NULL, 1),
(175, 'Sai Shah', 's_5ek3_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-3', 5, '5ek-3-06', NULL, 1),
(176, 'Reyansh Mehta', 's_5ek3_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-3', 5, '5ek-3-07', NULL, 1),
(177, 'Krishna Joshi', 's_5ek3_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-3', 5, '5ek-3-08', NULL, 1),
(178, 'Ishaan Desai', 's_5ek3_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '5ek-3', 5, '5ek-3-09', NULL, 1),
(179, 'Kabir Parmar', 's_5ek3_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '5ek-3', 5, '5ek-3-10', NULL, 1),
(180, 'Aarav Rathod', 's_7ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '7ek-1', 7, '7ek-1-01', NULL, 1),
(181, 'Vihaan Dave', 's_7ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-02', NULL, 1),
(182, 'Vivaan Patel', 's_7ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-03', NULL, 1),
(183, 'Aditya Shah', 's_7ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '7ek-1', 7, '7ek-1-04', NULL, 1),
(184, 'Arjun Mehta', 's_7ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-05', NULL, 1),
(185, 'Sai Joshi', 's_7ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '7ek-1', 7, '7ek-1-06', NULL, 1),
(186, 'Reyansh Desai', 's_7ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-07', NULL, 1),
(187, 'Krishna Parmar', 's_7ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-08', NULL, 1),
(188, 'Ishaan Vyas', 's_7ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-09', NULL, 1),
(189, 'Kabir Trivedi', 's_7ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-1', 7, '7ek-1-10', NULL, 1),
(190, 'Aarav Rathod', 's_7ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '7ek-2', 7, '7ek-2-01', NULL, 1),
(191, 'Vihaan Dave', 's_7ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-2', 7, '7ek-2-02', NULL, 1),
(192, 'Vivaan Patel', 's_7ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-2', 7, '7ek-2-03', NULL, 1),
(193, 'Aditya Shah', 's_7ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '7ek-2', 7, '7ek-2-04', NULL, 1),
(194, 'Arjun Mehta', 's_7ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '7ek-2', 7, '7ek-2-05', NULL, 1),
(195, 'Sai Joshi', 's_7ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-2', 7, '7ek-2-06', NULL, 1),
(196, 'Reyansh Desai', 's_7ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-2', 7, '7ek-2-07', NULL, 1),
(197, 'Krishna Parmar', 's_7ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'challenged', '7ek-2', 7, '7ek-2-08', NULL, 1),
(198, 'Ishaan Vyas', 's_7ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'average', '7ek-2', 7, '7ek-2-09', NULL, 1),
(199, 'Kabir Trivedi', 's_7ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'premium', '7ek-2', 7, '7ek-2-10', NULL, 1);

INSERT INTO profiles (id, user_id, branch, skills, expertise_area, company, designation, bio, github_url, leetcode_url, portfolio_url, hobbies, target_role, is_alumni, college_name, graduation_year, degree, experience_years, teaching_interests, is_cc, cc_class, cc_semester) VALUES
(1, 1, 'ICT', NULL, 'Administration', NULL, 'System Admin', 'Admin profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(2, 2, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '1ek-1', 1),
(3, 3, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '1ek-2', 1),
(4, 4, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '3ek-1', 3),
(5, 5, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '3ek-2', 3),
(6, 6, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '3ek-3', 3),
(7, 7, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '5ek-1', 5),
(8, 8, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '5ek-2', 5),
(9, 9, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '5ek-3', 5),
(10, 10, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '7ek-1', 7),
(11, 11, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 1, '7ek-2', 7),
(12, 12, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 0, NULL, NULL),
(13, 13, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 0, NULL, NULL),
(14, 14, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 0, NULL, NULL),
(15, 15, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 0, NULL, NULL),
(16, 16, 'ICT', 'Teaching, Mentoring', 'Computer Engineering', NULL, 'Faculty', 'Faculty profile', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, 'Web Development, DBMS, Java', 0, NULL, NULL),
(17, 17, 'ICT', 'PHP, Python, Project Review', 'Software Development', 'Industry', 'Expert', 'Expert profile', NULL, NULL, NULL, NULL, 'Software Engineer', 1, 'Marwadi University', '2022', 'B.Tech', 3, NULL, 0, NULL, NULL),
(18, 18, 'ICT', 'PHP, Python, Project Review', 'Software Development', 'Industry', 'Expert', 'Expert profile', NULL, NULL, NULL, NULL, 'Software Engineer', 1, 'Marwadi University', '2022', 'B.Tech', 3, NULL, 0, NULL, NULL),
(19, 100, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(20, 101, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(21, 102, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(22, 103, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(23, 104, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(24, 105, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(25, 106, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(26, 107, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(27, 108, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(28, 109, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(29, 110, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(30, 111, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(31, 112, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(32, 113, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(33, 114, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(34, 115, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(35, 116, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(36, 117, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(37, 118, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(38, 119, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(39, 120, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(40, 121, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(41, 122, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(42, 123, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(43, 124, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(44, 125, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(45, 126, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(46, 127, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(47, 128, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(48, 129, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(49, 130, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(50, 131, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(51, 132, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(52, 133, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(53, 134, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(54, 135, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(55, 136, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(56, 137, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(57, 138, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(58, 139, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(59, 140, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(60, 141, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(61, 142, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(62, 143, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(63, 144, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(64, 145, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(65, 146, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(66, 147, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(67, 148, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(68, 149, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(69, 150, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(70, 151, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(71, 152, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(72, 153, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(73, 154, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(74, 155, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(75, 156, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(76, 157, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(77, 158, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(78, 159, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(79, 160, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(80, 161, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(81, 162, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(82, 163, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(83, 164, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(84, 165, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(85, 166, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(86, 167, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(87, 168, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(88, 169, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(89, 170, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(90, 171, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(91, 172, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(92, 173, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(93, 174, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(94, 175, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(95, 176, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(96, 177, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(97, 178, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(98, 179, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(99, 180, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(100, 181, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(101, 182, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(102, 183, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(103, 184, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(104, 185, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(105, 186, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(106, 187, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(107, 188, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(108, 189, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(109, 190, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(110, 191, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(111, 192, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(112, 193, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(113, 194, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(114, 195, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(115, 196, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(116, 197, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(117, 198, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(118, 199, 'ICT', 'HTML, CSS, PHP, MySQL', NULL, NULL, NULL, 'Student profile', NULL, NULL, NULL, 'Coding, Reading', 'Full Stack Developer', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL);

-- ==========================================
-- UPDATED SUBJECT DATA FOR ALL SEMESTERS
-- ==========================================

-- SEMESTER 1 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(2,'Basic Electrical Engineering','ICT','1ek-1',1),
(3,'Engineering Mathematics-I','ICT','1ek-2',1),
(4,'Programming for Problem Solving','ICT','1ek-1',1),
(5,'Physics','ICT','1ek-2',1);

-- SEMESTER 2 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(6,'Digital Electronics','ICT','2ek-1',2),
(7,'Data Structures','ICT','2ek-2',2),
(8,'Computer Organization','ICT','2ek-1',2),
(9,'Engineering Mathematics-II','ICT','2ek-2',2);

-- SEMESTER 3 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(10,'Database Management System','ICT','3ek-1',3),
(11,'Object Oriented Programming','ICT','3ek-2',3),
(12,'Signal and System','ICT','3ek-3',3),
(13,'Probability and Statistics','ICT','3ek-1',3);

-- SEMESTER 4 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(14,'Operating System','ICT','4ek-1',4),
(15,'Java Programming','ICT','4ek-2',4),
(16,'Microprocessor and Interfacing','ICT','4ek-3',4),
(2,'Web Technology','ICT','4ek-1',4);

-- SEMESTER 5 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester, is_elective) VALUES
(3,'Computer Networks','ICT','5ek-1',5, 0),
(4,'Software Engineering','ICT','5ek-2',5, 0),
(5,'Theory of Computation','ICT','5ek-3',5, 0),
(6,'Python Programming','ICT','5ek-1',5, 0),
(12,'Mobile App Dev (Elective)','ICT','5ek-1',5, 1),
(13,'Cloud Computing (Elective)','ICT','5ek-2',5, 1);

-- SEMESTER 6 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(7,'Machine Learning','ICT','6ek-1',6),
(8,'Cloud Computing','ICT','6ek-2',6),
(9,'Compiler Design','ICT','6ek-1',6),
(10,'Mobile Application Development','ICT','6ek-2',6);

-- SEMESTER 7 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(11,'Artificial Intelligence','ICT','7ek-1',7),
(12,'Cyber Security','ICT','7ek-2',7),
(13,'Internet of Things','ICT','7ek-1',7),
(14,'Big Data Analytics','ICT','7ek-2',7);

-- SEMESTER 8 SUBJECTS
INSERT INTO faculty_subjects (faculty_id, subject_name, branch, class_name, semester) VALUES
(15,'Project Management','ICT','8ek-1',8),
(16,'Blockchain Technology','ICT','8ek-2',8),
(2,'Industrial Training','ICT','8ek-1',8),
(3,'Major Project','ICT','8ek-2',8);

-- ==========================================
-- SUBJECT DETAILS (UNITS)
-- ==========================================

INSERT INTO faculty_units (subject_id, unit_no, unit_name) VALUES
(1,1,'Introduction and Fundamentals'),
(1,2,'Circuit Laws and Applications'),
(2,1,'Matrices and Calculus'),
(3,1,'C Programming Basics'),
(4,1,'Mechanics and Optics'),
(5,1,'Logic Gates and Flip Flops'),
(6,1,'Arrays and Linked List'),
(7,1,'CPU and Memory'),
(8,1,'Differential Equations'),
(9,1,'ER Model and SQL'),
(10,1,'Classes and Objects'),
(11,1,'Signals and Systems Basics'),
(12,1,'Probability Distribution'),
(13,1,'Process Management'),
(14,1,'Java Fundamentals'),
(15,1,'8086 Architecture'),
(16,1,'HTML CSS JavaScript'),
(17,1,'OSI and TCP/IP'),
(18,1,'SDLC Models'),
(19,1,'Finite Automata'),
(20,1,'Python Basics'),
(21,1,'Introduction to ML'),
(22,1,'Cloud Service Models'),
(23,1,'Lexical Analysis'),
(24,1,'Android Basics'),
(25,1,'AI Fundamentals'),
(26,1,'Network Security'),
(27,1,'Sensors and Devices'),
(28,1,'Big Data Introduction'),
(29,1,'Planning and Scheduling'),
(30,1,'Blockchain Basics'),
(31,1,'Industry Exposure'),
(32,1,'Project Development');

INSERT INTO faculty_topics (id, unit_id, topic_name) VALUES
(1, 1, 'Basic Electronics Topic 1.1'),
(2, 1, 'Basic Electronics Topic 1.2'),
(3, 2, 'Basic Electronics Topic 2.1'),
(4, 2, 'Basic Electronics Topic 2.2'),
(5, 3, 'Engineering Mathematics I Topic 1.1'),
(6, 3, 'Engineering Mathematics I Topic 1.2'),
(7, 4, 'Engineering Mathematics I Topic 2.1'),
(8, 4, 'Engineering Mathematics I Topic 2.2'),
(9, 5, 'Programming Fundamentals Topic 1.1'),
(10, 5, 'Programming Fundamentals Topic 1.2'),
(11, 6, 'Programming Fundamentals Topic 2.1'),
(12, 6, 'Programming Fundamentals Topic 2.2'),
(13, 7, 'Communication Skills Topic 1.1'),
(14, 7, 'Communication Skills Topic 1.2'),
(15, 8, 'Communication Skills Topic 2.1'),
(16, 8, 'Communication Skills Topic 2.2'),
(17, 9, 'Basic Electronics Topic 1.1'),
(18, 9, 'Basic Electronics Topic 1.2'),
(19, 10, 'Basic Electronics Topic 2.1'),
(20, 10, 'Basic Electronics Topic 2.2'),
(21, 11, 'Engineering Mathematics I Topic 1.1'),
(22, 11, 'Engineering Mathematics I Topic 1.2'),
(23, 12, 'Engineering Mathematics I Topic 2.1'),
(24, 12, 'Engineering Mathematics I Topic 2.2'),
(25, 13, 'Programming Fundamentals Topic 1.1'),
(26, 13, 'Programming Fundamentals Topic 1.2'),
(27, 14, 'Programming Fundamentals Topic 2.1'),
(28, 14, 'Programming Fundamentals Topic 2.2'),
(29, 15, 'Communication Skills Topic 1.1'),
(30, 15, 'Communication Skills Topic 1.2'),
(31, 16, 'Communication Skills Topic 2.1'),
(32, 16, 'Communication Skills Topic 2.2'),
(33, 17, 'Data Structures Topic 1.1'),
(34, 17, 'Data Structures Topic 1.2'),
(35, 18, 'Data Structures Topic 2.1'),
(36, 18, 'Data Structures Topic 2.2'),
(37, 19, 'Digital Electronics Topic 1.1'),
(38, 19, 'Digital Electronics Topic 1.2'),
(39, 20, 'Digital Electronics Topic 2.1'),
(40, 20, 'Digital Electronics Topic 2.2'),
(41, 21, 'Database Management System Topic 1.1'),
(42, 21, 'Database Management System Topic 1.2'),
(43, 22, 'Database Management System Topic 2.1'),
(44, 22, 'Database Management System Topic 2.2'),
(45, 23, 'Object Oriented Programming Topic 1.1'),
(46, 23, 'Object Oriented Programming Topic 1.2'),
(47, 24, 'Object Oriented Programming Topic 2.1'),
(48, 24, 'Object Oriented Programming Topic 2.2'),
(49, 25, 'Data Structures Topic 1.1'),
(50, 25, 'Data Structures Topic 1.2'),
(51, 26, 'Data Structures Topic 2.1'),
(52, 26, 'Data Structures Topic 2.2'),
(53, 27, 'Digital Electronics Topic 1.1'),
(54, 27, 'Digital Electronics Topic 1.2'),
(55, 28, 'Digital Electronics Topic 2.1'),
(56, 28, 'Digital Electronics Topic 2.2'),
(57, 29, 'Database Management System Topic 1.1'),
(58, 29, 'Database Management System Topic 1.2'),
(59, 30, 'Database Management System Topic 2.1'),
(60, 30, 'Database Management System Topic 2.2'),
(61, 31, 'Object Oriented Programming Topic 1.1'),
(62, 31, 'Object Oriented Programming Topic 1.2'),
(63, 32, 'Object Oriented Programming Topic 2.1'),
(64, 32, 'Object Oriented Programming Topic 2.2');

INSERT INTO task_categories (id, user_id, name) VALUES
(1, 100, 'College Work'),
(2, 100, 'Project');

INSERT INTO task_priorities (id, user_id, name, color, sort_order) VALUES
(1, 100, 'High', '#ff0000', 1),
(2, 100, 'Medium', '#ffaa00', 2);

INSERT INTO tasks (id, user_id, task, deadline, category_id, priority_id, is_completed) VALUES
(1, 100, 'Complete PHP module', '2026-06-10 18:00:00', 1, 1, 0),
(2, 101, 'Prepare DBMS notes', '2026-06-12 18:00:00', 1, 2, 0);

INSERT INTO requests (id, user_id, skill, status, reviewer_id) VALUES
(1, 100, 'PHP Development', 'pending', NULL),
(2, 101, 'Python Scripting', 'accepted', 17);

INSERT INTO reviews (request_id, reviewer_id, marks, comment) VALUES
(2, 17, 85, 'Excellent understanding of Python basics.');

INSERT INTO faculty_feedback_forms (id, faculty_id, is_active) VALUES
(1, 2, 1);

INSERT INTO faculty_feedback_questions (form_id, question_text) VALUES
(1, 'How clear are the instructor explanations?'),
(1, 'Is the course material up to date?');
