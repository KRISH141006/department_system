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
    class_name VARCHAR(50),
    semester VARCHAR(20),
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
    cc_semester VARCHAR(20),
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
    class_name VARCHAR(50),
    semester VARCHAR(20),
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
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
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
    FOREIGN KEY (form_id) REFERENCES faculty_feedback_forms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_faculty_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    question_id INT NOT NULL,
    student_id INT NOT NULL,
    rating INT NOT NULL,
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

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task TEXT NOT NULL,
    deadline DATETIME NULL,
    category_id INT NULL,
    priority_id INT NULL,
    is_completed TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (priority_id) REFERENCES task_priorities(id) ON DELETE SET NULL
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
-- SEED DATA
-- ======================

-- Passwords are '1234'
INSERT INTO users (id, name, email, password, role, class_name, semester, roll_no, is_verified) VALUES
(1, 'System Admin', 'admin@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'admin', NULL, NULL, NULL, 1),
(2, 'Faculty One', 'faculty1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(3, 'Faculty Two', 'faculty2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(4, 'Student One', 'student1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'ICT-A', 'Sem-6', '21IT001', 1),
(5, 'Student Two', 'student2@ict.com', '$2y$12$ xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'ICT-B', 'Sem-6', '21IT002', 1),
(6, 'Student Three', 'student3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'ICT-A', 'Sem-6', '21IT003', 1),
(7, 'Expert One', 'expert1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert', NULL, NULL, NULL, 1),
(8, 'Expert Two', 'expert2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert', NULL, NULL, NULL, 1),

-- === 2EK1 ===
(100, 'Suresh Sharma', '    ', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(101, 'Ramesh Patel', 'f_2ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(102, 'Mahesh Vaghela', 'f_2ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(103, 'Rajesh Kumar', 'f_2ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(104, 'Dinesh Gupta', 'f_2ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(105, 'Mukesh Singh', 'f_2ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(106, 'Alpesh Shah', 'f_2ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(107, 'Hitesh Rathod', 'f_2ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(108, 'Paresh Mehta', 'f_2ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(109, 'Naresh Chauhan', 'f_2ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(110, 'Aarav Patel', 's_2ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-01', 1),
(111, 'Vihaan Sharma', 's_2ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-02', 1),
(112, 'Vivaan Shah', 's_2ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-03', 1),
(113, 'Ananya Iyer', 's_2ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-04', 1),
(114, 'Diya Mehta', 's_2ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-05', 1),
(115, 'Advait Joshi', 's_2ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-06', 1),
(116, 'Ishani Gupta', 's_2ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-07', 1),
(117, 'Kabir Singh', 's_2ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-08', 1),
(118, 'Myra Kapoor', 's_2ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-09', 1),
(119, 'Reyansh Reddy', 's_2ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek1', 'Sem-2', '2ek1-10', 1),

-- === 2EK2 ===
(120, 'Sanjay Bhatt', 'f_2ek2_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(121, 'Vijay Joshi', 'f_2ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(122, 'Ajay Parmar', 'f_2ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(123, 'Jay Vyas', 'f_2ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(124, 'Parth Trivedi', 'f_2ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(125, 'Keyur Raval', 'f_2ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(126, 'Darshit Kacha', 'f_2ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(127, 'Hardik Pandya', 'f_2ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(128, 'Amit Trivedi', 'f_2ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(129, 'Sunil Gavaskar', 'f_2ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(130, 'Aryan Malhotra', 's_2ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-01', 1),
(131, 'Saanvi Bansal', 's_2ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-02', 1),
(132, 'Krishna Murthy', 's_2ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-03', 1),
(133, 'Kiara Advani', 's_2ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-04', 1),
(134, 'Devansh Bakshi', 's_2ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-05', 1),
(135, 'Navya Naveli', 's_2ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-06', 1),
(136, 'Atharv Deshmukh', 's_2ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-07', 1),
(137, 'Zoya Akhtar', 's_2ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-08', 1),
(138, 'Ayaan Hirani', 's_2ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-09', 1),
(139, 'Shanaya Singhania', 's_2ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '2ek2', 'Sem-2', '2ek2-10', 1),

-- === 4EK1 ===
(200, 'Manoj Bajpayee', 'f_4ek1_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(201, 'Pankaj Tripathi', 'f_4ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(202, 'Nawazuddin Siddiqui', 'f_4ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(203, 'Irrfan Khan', 'f_4ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(204, 'Rajkummar Rao', 'f_4ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(205, 'Ayushmann Khurrana', 'f_4ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(206, 'Vicky Kaushal', 'f_4ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(207, 'Varun Dhawan', 'f_4ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(208, 'Ranveer Singh', 'f_4ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(209, 'Ranbir Kapoor', 'f_4ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(210, 'Ishaan Khattar', 's_4ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-01', 1),
(211, 'Janhvi Kapoor', 's_4ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-02', 1),
(212, 'Sara Ali Khan', 's_4ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-03', 1),
(213, 'Ananya Panday', 's_4ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-04', 1),
(214, 'Ibrahim Khan', 's_4ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-05', 1),
(215, 'Taimur Ali', 's_4ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-06', 1),
(216, 'Suahana Khan', 's_4ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-07', 1),
(217, 'Aryan Khan', 's_4ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-08', 1),
(218, 'Aaradhya Bachchan', 's_4ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-09', 1),
(219, 'Agastya Nanda', 's_4ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek1', 'Sem-4', '4ek1-10', 1),

-- === 4EK2 ===
(220, 'Rahul Dravid', 'f_4ek2_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(221, 'Sachin Tendulkar', 'f_4ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(222, 'Saurav Ganguly', 'f_4ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(223, 'VVS Laxman', 'f_4ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(224, 'Virender Sehwag', 'f_4ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(225, 'Anil Kumble', 'f_4ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(226, 'Harbhajan Singh', 'f_4ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(227, 'Zaheer Khan', 'f_4ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(228, 'MS Dhoni', 'f_4ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(229, 'Virat Kohli', 'f_4ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(230, 'Prithvi Shaw', 's_4ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-01', 1),
(231, 'Shubman Gill', 's_4ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-02', 1),
(232, 'Rishabh Pant', 's_4ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-03', 1),
(233, 'Ishan Kishan', 's_4ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-04', 1),
(234, 'Shreyas Iyer', 's_4ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-05', 1),
(235, 'KLRahul', 's_4ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-06', 1),
(236, 'Hardik Pandya', 's_4ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-07', 1),
(237, 'Jasprit Bumrah', 's_4ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-08', 1),
(238, 'Ravindra Jadeja', 's_4ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-09', 1),
(239, 'Mohammed Siraj', 's_4ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek2', 'Sem-4', '4ek2-10', 1),

-- === 4EK3 ===
(240, 'Ravi Shastri', 'f_4ek3_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(241, 'Kapil Dev', 'f_4ek3_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(242, 'Mohammad Azharuddin', 'f_4ek3_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(243, 'Sunil Shetty', 'f_4ek3_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(244, 'Akshay Kumar', 'f_4ek3_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(245, 'Shah Rukh Khan', 'f_4ek3_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(246, 'Aamir Khan', 'f_4ek3_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(247, 'Salman Khan', 'f_4ek3_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(248, 'Saif Ali Khan', 'f_4ek3_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(249, 'Hrithik Roshan', 'f_4ek3_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(250, 'Tiger Shroff', 's_4ek3_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-01', 1),
(251, 'Siddharth Malhotra', 's_4ek3_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-02', 1),
(252, 'Varun Dhawan', 's_4ek3_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-03', 1),
(253, 'Alia Bhatt', 's_4ek3_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-04', 1),
(254, 'Shraddha Kapoor', 's_4ek3_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-05', 1),
(255, 'Kriti Sanon', 's_4ek3_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-06', 1),
(256, 'Kartik Aaryan', 's_4ek3_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-07', 1),
(257, 'Deepika Padukone', 's_4ek3_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-08', 1),
(258, 'Priyanka Chopra', 's_4ek3_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-09', 1),
(259, 'Ranveer Singh', 's_4ek3_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '4ek3', 'Sem-4', '4ek3-10', 1),

-- === 6EK1 ===
(300, 'Jagdish Prasad', 'f_6ek1_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(301, 'Nirmal Singh', 'f_6ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(302, 'Baldev Raj', 'f_6ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(303, 'Gurcharan Singh', 'f_6ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(304, 'Harish Kumar', 'f_6ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(305, 'Ishwar Lal', 'f_6ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(306, 'Kishan Chand', 'f_6ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(307, 'Laxman Das', 'f_6ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(308, 'Madan Gopal', 'f_6ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(309, 'Om Prakash', 'f_6ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(310, 'Tushar Kapoor', 's_6ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-01', 1),
(311, 'Riteish Deshmukh', 's_6ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-02', 1),
(312, 'Genelia DSouza', 's_6ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-03', 1),
(313, 'Abhishek Bachchan', 's_6ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-04', 1),
(314, 'Aishwarya Rai', 's_6ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-05', 1),
(315, 'Sushmita Sen', 's_6ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-06', 1),
(316, 'Lara Dutta', 's_6ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-07', 1),
(317, 'Preity Zinta', 's_6ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-08', 1),
(318, 'Rani Mukerji', 's_6ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-09', 1),
(319, 'Kajol Devgan', 's_6ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek1', 'Sem-6', '6ek1-10', 1),

-- === 6EK2 ===
(320, 'Prem Nath', 'f_6ek2_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(321, 'Ram Nath', 'f_6ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(322, 'Shanti Lal', 'f_6ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(323, 'Tara Chand', 'f_6ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(324, 'Udhav Das', 'f_6ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(325, 'Ved Prakash', 'f_6ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(326, 'Yash Pal', 'f_6ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(327, 'Amar Nath', 'f_6ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(328, 'Bharat Bhushan', 'f_6ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(329, 'Chander Mohan', 'f_6ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(330, 'Karisma Kapoor', 's_6ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-01', 1),
(331, 'Kareena Kapoor', 's_6ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-02', 1),
(332, 'Saif Ali', 's_6ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-03', 1),
(333, 'Soha Ali', 's_6ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-04', 1),
(334, 'Kunal Khemu', 's_6ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-05', 1),
(335, 'Sara Ali', 's_6ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-06', 1),
(336, 'Ibrahim Khan', 's_6ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-07', 1),
(337, 'Jehangir Ali', 's_6ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-08', 1),
(338, 'Taimur Khan', 's_6ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-09', 1),
(339, 'Babita Kapoor', 's_6ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '6ek2', 'Sem-6', '6ek2-10', 1),

-- === 8EK1 ===
(400, 'Dev Anand', 'f_8ek1_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(401, 'Guru Dutt', 'f_8ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(402, 'Kishore Kumar', 'f_8ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(403, 'Mukesh Kumar', 'f_8ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(404, 'Raj Kapoor', 'f_8ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(405, 'Dilip Kumar', 'f_8ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(406, 'Sunil Dutt', 'f_8ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(407, 'Shammi Kapoor', 'f_8ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(408, 'Shashi Kapoor', 'f_8ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(409, 'Rishi Kapoor', 'f_8ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(410, 'Ranbir Kapoor', 's_8ek1_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-01', 1),
(411, 'Alia Bhatt', 's_8ek1_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-02', 1),
(412, 'Katrina Kaif', 's_8ek1_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-03', 1),
(413, 'Vicky Kaushal', 's_8ek1_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-04', 1),
(414, 'Deepika Padukone', 's_8ek1_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-05', 1),
(415, 'Ranveer Singh', 's_8ek1_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-06', 1),
(416, 'Anushka Sharma', 's_8ek1_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-07', 1),
(417, 'Virat Kohli', 's_8ek1_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-08', 1),
(418, 'Sonam Kapoor', 's_8ek1_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-09', 1),
(419, 'Anand Ahuja', 's_8ek1_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek1', 'Sem-8', '8ek1-10', 1),

-- === 8EK2 ===
(420, 'Amitabh Bachchan', 'f_8ek2_cc@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(421, 'Rajesh Khanna', 'f_8ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(422, 'Dharmendra Singh', 'f_8ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(423, 'Jeetendra Kapoor', 'f_8ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(424, 'Vinod Khanna', 'f_8ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(425, 'Shatrughan Sinha', 'f_8ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(426, 'Mithun Chakraborty', 'f_8ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(427, 'Govinda Ahuja', 'f_8ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(428, 'Anil Kapoor', 'f_8ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(429, 'Jackie Shroff', 'f_8ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', NULL, NULL, NULL, 1),
(430, 'Abhishek Bachchan', 's_8ek2_1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-01', 1),
(431, 'Aishwarya Rai', 's_8ek2_2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-02', 1),
(432, 'Jaya Bachchan', 's_8ek2_3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-03', 1),
(433, 'Shweta Nanda', 's_8ek2_4@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-04', 1),
(434, 'Navya Nanda', 's_8ek2_5@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-05', 1),
(435, 'Agastya Nanda', 's_8ek2_6@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-06', 1),
(436, 'Nikhil Nanda', 's_8ek2_7@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-07', 1),
(437, 'Aaradhya Bachchan', 's_8ek2_8@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-08', 1),
(438, 'Amitabh Jr', 's_8ek2_9@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-09', 1),
(439, 'Jaya Jr', 's_8ek2_10@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', '8ek2', 'Sem-8', '8ek2-10', 1);

-- Profiles
INSERT INTO profiles (user_id, branch, skills, bio, is_cc, cc_class, cc_semester) VALUES
(1, 'ICT', 'Management, Security', 'System administrator for the ICT department.', 0, NULL, NULL),
(2, 'ICT', 'Web Development, PHP', 'Assistant Professor in ICT Department.', 0, NULL, NULL),
(3, 'ICT', 'Database Management, SQL', 'Associate Professor in ICT Department.', 0, NULL, NULL),
(4, 'ICT', 'HTML, CSS, JS', '3rd year student interested in Full Stack.', 0, NULL, NULL),
(5, 'ICT', 'Python, Data Science', '3rd year student specializing in AI.', 0, NULL, NULL),
(6, 'ICT', 'Networking, Cisco', '3rd year student focusing on infrastructure.', 0, NULL, NULL),
(7, 'Industry', 'System Architecture', 'Senior Architect at Tech Corp.', 0, NULL, NULL),
(8, 'Industry', 'Product Management', 'Expert in Agile and Scrum.', 0, NULL, NULL),

-- Profiles: 2EK1
(100, 'ICT', 'Teaching', 'CC for 2ek1 (Suresh Sharma)', 1, '2ek1', 'Sem-2'),
(101, 'ICT', 'Teaching', 'Faculty for 2ek1 (Ramesh Patel)', 0, NULL, NULL),
(102, 'ICT', 'Teaching', 'Faculty for 2ek1 (Mahesh Vaghela)', 0, NULL, NULL),
(103, 'ICT', 'Teaching', 'Faculty for 2ek1 (Rajesh Kumar)', 0, NULL, NULL),
(104, 'ICT', 'Teaching', 'Faculty for 2ek1 (Dinesh Gupta)', 0, NULL, NULL),
(105, 'ICT', 'Teaching', 'Faculty for 2ek1 (Mukesh Singh)', 0, NULL, NULL),
(106, 'ICT', 'Teaching', 'Faculty for 2ek1 (Alpesh Shah)', 0, NULL, NULL),
(107, 'ICT', 'Teaching', 'Faculty for 2ek1 (Hitesh Rathod)', 0, NULL, NULL),
(108, 'ICT', 'Teaching', 'Faculty for 2ek1 (Paresh Mehta)', 0, NULL, NULL),
(109, 'ICT', 'Teaching', 'Faculty for 2ek1 (Naresh Chauhan)', 0, NULL, NULL),
(110, 'ICT', 'Learning', 'Student 2ek1 (Aarav Patel)', 0, NULL, NULL),
(111, 'ICT', 'Learning', 'Student 2ek1 (Vihaan Sharma)', 0, NULL, NULL),
(112, 'ICT', 'Learning', 'Student 2ek1 (Vivaan Shah)', 0, NULL, NULL),
(113, 'ICT', 'Learning', 'Student 2ek1 (Ananya Iyer)', 0, NULL, NULL),
(114, 'ICT', 'Learning', 'Student 2ek1 (Diya Mehta)', 0, NULL, NULL),
(115, 'ICT', 'Learning', 'Student 2ek1 (Advait Joshi)', 0, NULL, NULL),
(116, 'ICT', 'Learning', 'Student 2ek1 (Ishani Gupta)', 0, NULL, NULL),
(117, 'ICT', 'Learning', 'Student 2ek1 (Kabir Singh)', 0, NULL, NULL),
(118, 'ICT', 'Learning', 'Student 2ek1 (Myra Kapoor)', 0, NULL, NULL),
(119, 'ICT', 'Learning', 'Student 2ek1 (Reyansh Reddy)', 0, NULL, NULL),

-- Profiles: 2EK2
(120, 'ICT', 'Teaching', 'CC for 2ek2 (Sanjay Bhatt)', 1, '2ek2', 'Sem-2'),
(121, 'ICT', 'Teaching', 'Faculty for 2ek2 (Vijay Joshi)', 0, NULL, NULL),
(122, 'ICT', 'Teaching', 'Faculty for 2ek2 (Ajay Parmar)', 0, NULL, NULL),
(123, 'ICT', 'Teaching', 'Faculty for 2ek2 (Jay Vyas)', 0, NULL, NULL),
(124, 'ICT', 'Teaching', 'Faculty for 2ek2 (Parth Trivedi)', 0, NULL, NULL),
(125, 'ICT', 'Teaching', 'Faculty for 2ek2 (Keyur Raval)', 0, NULL, NULL),
(126, 'ICT', 'Teaching', 'Faculty for 2ek2 (Darshit Kacha)', 0, NULL, NULL),
(127, 'ICT', 'Teaching', 'Faculty for 2ek2 (Hardik Pandya)', 0, NULL, NULL),
(128, 'ICT', 'Teaching', 'Faculty for 2ek2 (Amit Trivedi)', 0, NULL, NULL),
(129, 'ICT', 'Teaching', 'Faculty for 2ek2 (Sunil Gavaskar)', 0, NULL, NULL),
(130, 'ICT', 'Learning', 'Student 2ek2 (Aryan Malhotra)', 0, NULL, NULL),
(131, 'ICT', 'Learning', 'Student 2ek2 (Saanvi Bansal)', 0, NULL, NULL),
(132, 'ICT', 'Learning', 'Student 2ek2 (Krishna Murthy)', 0, NULL, NULL),
(133, 'ICT', 'Learning', 'Student 2ek2 (Kiara Advani)', 0, NULL, NULL),
(134, 'ICT', 'Learning', 'Student 2ek2 (Devansh Bakshi)', 0, NULL, NULL),
(135, 'ICT', 'Learning', 'Student 2ek2 (Navya Naveli)', 0, NULL, NULL),
(136, 'ICT', 'Learning', 'Student 2ek2 (Atharv Deshmukh)', 0, NULL, NULL),
(137, 'ICT', 'Learning', 'Student 2ek2 (Zoya Akhtar)', 0, NULL, NULL),
(138, 'ICT', 'Learning', 'Student 2ek2 (Ayaan Hirani)', 0, NULL, NULL),
(139, 'ICT', 'Learning', 'Student 2ek2 (Shanaya Singhania)', 0, NULL, NULL),

-- Profiles: 4EK1
(200, 'ICT', 'Teaching', 'CC for 4ek1 (Manoj Bajpayee)', 1, '4ek1', 'Sem-4'),
(201, 'ICT', 'Teaching', 'Faculty for 4ek1 (Pankaj Tripathi)', 0, NULL, NULL),
(202, 'ICT', 'Teaching', 'Faculty for 4ek1 (Nawazuddin Siddiqui)', 0, NULL, NULL),
(203, 'ICT', 'Teaching', 'Faculty for 4ek1 (Irrfan Khan)', 0, NULL, NULL),
(204, 'ICT', 'Teaching', 'Faculty for 4ek1 (Rajkummar Rao)', 0, NULL, NULL),
(205, 'ICT', 'Teaching', 'Faculty for 4ek1 (Ayushmann Khurrana)', 0, NULL, NULL),
(206, 'ICT', 'Teaching', 'Faculty for 4ek1 (Vicky Kaushal)', 0, NULL, NULL),
(207, 'ICT', 'Teaching', 'Faculty for 4ek1 (Varun Dhawan)', 0, NULL, NULL),
(208, 'ICT', 'Teaching', 'Faculty for 4ek1 (Ranveer Singh)', 0, NULL, NULL),
(209, 'ICT', 'Teaching', 'Faculty for 4ek1 (Ranbir Kapoor)', 0, NULL, NULL),
(210, 'ICT', 'Learning', 'Student 4ek1 (Ishaan Khattar)', 0, NULL, NULL),
(211, 'ICT', 'Learning', 'Student 4ek1 (Janhvi Kapoor)', 0, NULL, NULL),
(212, 'ICT', 'Learning', 'Student 4ek1 (Sara Ali Khan)', 0, NULL, NULL),
(213, 'ICT', 'Learning', 'Student 4ek1 (Ananya Panday)', 0, NULL, NULL),
(214, 'ICT', 'Learning', 'Student 4ek1 (Ibrahim Khan)', 0, NULL, NULL),
(215, 'ICT', 'Learning', 'Student 4ek1 (Taimur Ali)', 0, NULL, NULL),
(216, 'ICT', 'Learning', 'Student 4ek1 (Suahana Khan)', 0, NULL, NULL),
(217, 'ICT', 'Learning', 'Student 4ek1 (Aryan Khan)', 0, NULL, NULL),
(218, 'ICT', 'Learning', 'Student 4ek1 (Aaradhya Bachchan)', 0, NULL, NULL),
(219, 'ICT', 'Learning', 'Student 4ek1 (Agastya Nanda)', 0, NULL, NULL),

-- Profiles: 4EK2
(220, 'ICT', 'Teaching', 'CC for 4ek2 (Rahul Dravid)', 1, '4ek2', 'Sem-4'),
(221, 'ICT', 'Teaching', 'Faculty for 4ek2 (Sachin Tendulkar)', 0, NULL, NULL),
(222, 'ICT', 'Teaching', 'Faculty for 4ek2 (Saurav Ganguly)', 0, NULL, NULL),
(223, 'ICT', 'Teaching', 'Faculty for 4ek2 (VVS Laxman)', 0, NULL, NULL),
(224, 'ICT', 'Teaching', 'Faculty for 4ek2 (Virender Sehwag)', 0, NULL, NULL),
(225, 'ICT', 'Teaching', 'Faculty for 4ek2 (Anil Kumble)', 0, NULL, NULL),
(226, 'ICT', 'Teaching', 'Faculty for 4ek2 (Harbhajan Singh)', 0, NULL, NULL),
(227, 'ICT', 'Teaching', 'Faculty for 4ek2 (Zaheer Khan)', 0, NULL, NULL),
(228, 'ICT', 'Teaching', 'Faculty for 4ek2 (MS Dhoni)', 0, NULL, NULL),
(229, 'ICT', 'Teaching', 'Faculty for 4ek2 (Virat Kohli)', 0, NULL, NULL),
(230, 'ICT', 'Learning', 'Student 4ek2 (Prithvi Shaw)', 0, NULL, NULL),
(231, 'ICT', 'Learning', 'Student 4ek2 (Shubman Gill)', 0, NULL, NULL),
(232, 'ICT', 'Learning', 'Student 4ek2 (Rishabh Pant)', 0, NULL, NULL),
(233, 'ICT', 'Learning', 'Student 4ek2 (Ishan Kishan)', 0, NULL, NULL),
(234, 'ICT', 'Learning', 'Student 4ek2 (Shreyas Iyer)', 0, NULL, NULL),
(235, 'ICT', 'Learning', 'Student 4ek2 (KLRahul)', 0, NULL, NULL),
(236, 'ICT', 'Learning', 'Student 4ek2 (Hardik Pandya)', 0, NULL, NULL),
(237, 'ICT', 'Learning', 'Student 4ek2 (Jasprit Bumrah)', 0, NULL, NULL),
(238, 'ICT', 'Learning', 'Student 4ek2 (Ravindra Jadeja)', 0, NULL, NULL),
(239, 'ICT', 'Learning', 'Student 4ek2 (Mohammed Siraj)', 0, NULL, NULL),

-- Profiles: 4EK3
(240, 'ICT', 'Teaching', 'CC for 4ek3 (Ravi Shastri)', 1, '4ek3', 'Sem-4'),
(241, 'ICT', 'Teaching', 'Faculty for 4ek3 (Kapil Dev)', 0, NULL, NULL),
(242, 'ICT', 'Teaching', 'Faculty for 4ek3 (Mohammad Azharuddin)', 0, NULL, NULL),
(243, 'ICT', 'Teaching', 'Faculty for 4ek3 (Sunil Shetty)', 0, NULL, NULL),
(244, 'ICT', 'Teaching', 'Faculty for 4ek3 (Akshay Kumar)', 0, NULL, NULL),
(245, 'ICT', 'Teaching', 'Faculty for 4ek3 (Shah Rukh Khan)', 0, NULL, NULL),
(246, 'ICT', 'Teaching', 'Faculty for 4ek3 (Aamir Khan)', 0, NULL, NULL),
(247, 'ICT', 'Teaching', 'Faculty for 4ek3 (Salman Khan)', 0, NULL, NULL),
(248, 'ICT', 'Teaching', 'Faculty for 4ek3 (Saif Ali Khan)', 0, NULL, NULL),
(249, 'ICT', 'Teaching', 'Faculty for 4ek3 (Hrithik Roshan)', 0, NULL, NULL),
(250, 'ICT', 'Learning', 'Student 4ek3 (Tiger Shroff)', 0, NULL, NULL),
(251, 'ICT', 'Learning', 'Student 4ek3 (Siddharth Malhotra)', 0, NULL, NULL),
(252, 'ICT', 'Learning', 'Student 4ek3 (Varun Dhawan)', 0, NULL, NULL),
(253, 'ICT', 'Learning', 'Student 4ek3 (Alia Bhatt)', 0, NULL, NULL),
(254, 'ICT', 'Learning', 'Student 4ek3 (Shraddha Kapoor)', 0, NULL, NULL),
(255, 'ICT', 'Learning', 'Student 4ek3 (Kriti Sanon)', 0, NULL, NULL),
(256, 'ICT', 'Learning', 'Student 4ek3 (Kartik Aaryan)', 0, NULL, NULL),
(257, 'ICT', 'Learning', 'Student 4ek3 (Deepika Padukone)', 0, NULL, NULL),
(258, 'ICT', 'Learning', 'Student 4ek3 (Priyanka Chopra)', 0, NULL, NULL),
(259, 'ICT', 'Learning', 'Student 4ek3 (Ranveer Singh)', 0, NULL, NULL),

-- Profiles: 6EK1
(300, 'ICT', 'Teaching', 'CC for 6ek1 (Jagdish Prasad)', 1, '6ek1', 'Sem-6'),
(301, 'ICT', 'Teaching', 'Faculty for 6ek1 (Nirmal Singh)', 0, NULL, NULL),
(302, 'ICT', 'Teaching', 'Faculty for 6ek1 (Baldev Raj)', 0, NULL, NULL),
(303, 'ICT', 'Teaching', 'Faculty for 6ek1 (Gurcharan Singh)', 0, NULL, NULL),
(304, 'ICT', 'Teaching', 'Faculty for 6ek1 (Harish Kumar)', 0, NULL, NULL),
(305, 'ICT', 'Teaching', 'Faculty for 6ek1 (Ishwar Lal)', 0, NULL, NULL),
(306, 'ICT', 'Teaching', 'Faculty for 6ek1 (Kishan Chand)', 0, NULL, NULL),
(307, 'ICT', 'Teaching', 'Faculty for 6ek1 (Laxman Das)', 0, NULL, NULL),
(308, 'ICT', 'Teaching', 'Faculty for 6ek1 (Madan Gopal)', 0, NULL, NULL),
(309, 'ICT', 'Teaching', 'Faculty for 6ek1 (Om Prakash)', 0, NULL, NULL),
(310, 'ICT', 'Learning', 'Student 6ek1 (Tushar Kapoor)', 0, NULL, NULL),
(311, 'ICT', 'Learning', 'Student 6ek1 (Riteish Deshmukh)', 0, NULL, NULL),
(312, 'ICT', 'Learning', 'Student 6ek1 (Genelia DSouza)', 0, NULL, NULL),
(313, 'ICT', 'Learning', 'Student 6ek1 (Abhishek Bachchan)', 0, NULL, NULL),
(314, 'ICT', 'Learning', 'Student 6ek1 (Aishwarya Rai)', 0, NULL, NULL),
(315, 'ICT', 'Learning', 'Student 6ek1 (Sushmita Sen)', 0, NULL, NULL),
(316, 'ICT', 'Learning', 'Student 6ek1 (Lara Dutta)', 0, NULL, NULL),
(317, 'ICT', 'Learning', 'Student 6ek1 (Preity Zinta)', 0, NULL, NULL),
(318, 'ICT', 'Learning', 'Student 6ek1 (Rani Mukerji)', 0, NULL, NULL),
(319, 'ICT', 'Learning', 'Student 6ek1 (Kajol Devgan)', 0, NULL, NULL),

-- Profiles: 6EK2
(320, 'ICT', 'Teaching', 'CC for 6ek2 (Prem Nath)', 1, '6ek2', 'Sem-6'),
(321, 'ICT', 'Teaching', 'Faculty for 6ek2 (Ram Nath)', 0, NULL, NULL),
(322, 'ICT', 'Teaching', 'Faculty for 6ek2 (Shanti Lal)', 0, NULL, NULL),
(323, 'ICT', 'Teaching', 'Faculty for 6ek2 (Tara Chand)', 0, NULL, NULL),
(324, 'ICT', 'Teaching', 'Faculty for 6ek2 (Udhav Das)', 0, NULL, NULL),
(325, 'ICT', 'Teaching', 'Faculty for 6ek2 (Ved Prakash)', 0, NULL, NULL),
(326, 'ICT', 'Teaching', 'Faculty for 6ek2 (Yash Pal)', 0, NULL, NULL),
(327, 'ICT', 'Teaching', 'Faculty for 6ek2 (Amar Nath)', 0, NULL, NULL),
(328, 'ICT', 'Teaching', 'Faculty for 6ek2 (Bharat Bhushan)', 0, NULL, NULL),
(329, 'ICT', 'Teaching', 'Faculty for 6ek2 (Chander Mohan)', 0, NULL, NULL),
(330, 'ICT', 'Learning', 'Student 6ek2 (Karisma Kapoor)', 0, NULL, NULL),
(331, 'ICT', 'Learning', 'Student 6ek2 (Kareena Kapoor)', 0, NULL, NULL),
(332, 'ICT', 'Learning', 'Student 6ek2 (Saif Ali)', 0, NULL, NULL),
(333, 'ICT', 'Learning', 'Student 6ek2 (Soha Ali)', 0, NULL, NULL),
(334, 'ICT', 'Learning', 'Student 6ek2 (Kunal Khemu)', 0, NULL, NULL),
(335, 'ICT', 'Learning', 'Student 6ek2 (Sara Ali)', 0, NULL, NULL),
(336, 'ICT', 'Learning', 'Student 6ek2 (Ibrahim Khan)', 0, NULL, NULL),
(337, 'ICT', 'Learning', 'Student 6ek2 (Jehangir Ali)', 0, NULL, NULL),
(338, 'ICT', 'Learning', 'Student 6ek2 (Taimur Khan)', 0, NULL, NULL),
(339, 'ICT', 'Learning', 'Student 6ek2 (Babita Kapoor)', 0, NULL, NULL),

-- Profiles: 8EK1
(400, 'ICT', 'Teaching', 'CC for 8ek1 (Dev Anand)', 1, '8ek1', 'Sem-8'),
(401, 'ICT', 'Teaching', 'Faculty for 8ek1 (Guru Dutt)', 0, NULL, NULL),
(402, 'ICT', 'Teaching', 'Faculty for 8ek1 (Kishore Kumar)', 0, NULL, NULL),
(403, 'ICT', 'Teaching', 'Faculty for 8ek1 (Mukesh Kumar)', 0, NULL, NULL),
(404, 'ICT', 'Teaching', 'Faculty for 8ek1 (Raj Kapoor)', 0, NULL, NULL),
(405, 'ICT', 'Teaching', 'Faculty for 8ek1 (Dilip Kumar)', 0, NULL, NULL),
(406, 'ICT', 'Teaching', 'Faculty for 8ek1 (Sunil Dutt)', 0, NULL, NULL),
(407, 'ICT', 'Teaching', 'Faculty for 8ek1 (Shammi Kapoor)', 0, NULL, NULL),
(408, 'ICT', 'Teaching', 'Faculty for 8ek1 (Shashi Kapoor)', 0, NULL, NULL),
(409, 'ICT', 'Teaching', 'Faculty for 8ek1 (Rishi Kapoor)', 0, NULL, NULL),
(410, 'ICT', 'Learning', 'Student 8ek1 (Ranbir Kapoor)', 0, NULL, NULL),
(411, 'ICT', 'Learning', 'Student 8ek1 (Alia Bhatt)', 0, NULL, NULL),
(412, 'ICT', 'Learning', 'Student 8ek1 (Katrina Kaif)', 0, NULL, NULL),
(413, 'ICT', 'Learning', 'Student 8ek1 (Vicky Kaushal)', 0, NULL, NULL),
(414, 'ICT', 'Learning', 'Student 8ek1 (Deepika Padukone)', 0, NULL, NULL),
(415, 'ICT', 'Learning', 'Student 8ek1 (Ranveer Singh)', 0, NULL, NULL),
(416, 'ICT', 'Learning', 'Student 8ek1 (Anushka Sharma)', 0, NULL, NULL),
(417, 'ICT', 'Learning', 'Student 8ek1 (Virat Kohli)', 0, NULL, NULL),
(418, 'ICT', 'Learning', 'Student 8ek1 (Sonam Kapoor)', 0, NULL, NULL),
(419, 'ICT', 'Learning', 'Student 8ek1 (Anand Ahuja)', 0, NULL, NULL),

-- Profiles: 8EK2
(420, 'ICT', 'Teaching', 'CC for 8ek2 (Amitabh Bachchan)', 1, '8ek2', 'Sem-8'),
(421, 'ICT', 'Teaching', 'Faculty for 8ek2 (Rajesh Khanna)', 0, NULL, NULL),
(422, 'ICT', 'Teaching', 'Faculty for 8ek2 (Dharmendra Singh)', 0, NULL, NULL),
(423, 'ICT', 'Teaching', 'Faculty for 8ek2 (Jeetendra Kapoor)', 0, NULL, NULL),
(424, 'ICT', 'Teaching', 'Faculty for 8ek2 (Vinod Khanna)', 0, NULL, NULL),
(425, 'ICT', 'Teaching', 'Faculty for 8ek2 (Shatrughan Sinha)', 0, NULL, NULL),
(426, 'ICT', 'Teaching', 'Faculty for 8ek2 (Mithun Chakraborty)', 0, NULL, NULL),
(427, 'ICT', 'Teaching', 'Faculty for 8ek2 (Govinda Ahuja)', 0, NULL, NULL),
(428, 'ICT', 'Teaching', 'Faculty for 8ek2 (Anil Kapoor)', 0, NULL, NULL),
(429, 'ICT', 'Teaching', 'Faculty for 8ek2 (Jackie Shroff)', 0, NULL, NULL),
(430, 'ICT', 'Learning', 'Student 8ek2 (Abhishek Bachchan)', 0, NULL, NULL),
(431, 'ICT', 'Learning', 'Student 8ek2 (Aishwarya Rai)', 0, NULL, NULL),
(432, 'ICT', 'Learning', 'Student 8ek2 (Jaya Bachchan)', 0, NULL, NULL),
(433, 'ICT', 'Learning', 'Student 8ek2 (Shweta Nanda)', 0, NULL, NULL),
(434, 'ICT', 'Learning', 'Student 8ek2 (Navya Nanda)', 0, NULL, NULL),
(435, 'ICT', 'Learning', 'Student 8ek2 (Agastya Nanda)', 0, NULL, NULL),
(436, 'ICT', 'Learning', 'Student 8ek2 (Nikhil Nanda)', 0, NULL, NULL),
(437, 'ICT', 'Learning', 'Student 8ek2 (Aaradhya Bachchan)', 0, NULL, NULL),
(438, 'ICT', 'Learning', 'Student 8ek2 (Amitabh Jr)', 0, NULL, NULL),
(439, 'ICT', 'Learning', 'Student 8ek2 (Jaya Jr)', 0, NULL, NULL);

-- Academics Seed
INSERT INTO faculty_subjects (id, faculty_id, subject_name, class_name, semester) VALUES
(1, 2, 'Web Programming', 'ICT-A', 'Sem-6'),
(2, 3, 'Database Systems', 'ICT-B', 'Sem-6');

INSERT INTO faculty_units (id, subject_id, unit_no, unit_name) VALUES
(1, 1, 1, 'Introduction to PHP'),
(2, 1, 2, 'Working with MySQL'),
(3, 2, 1, 'Relational Model'),
(4, 2, 2, 'SQL Queries');

INSERT INTO faculty_topics (unit_id, topic_name) VALUES
(1, 'PHP Syntax'),
(1, 'Variables and Data Types'),
(2, 'Connecting to DB'),
(2, 'CRUD Operations'),
(3, 'Entity Relationship Diagram'),
(3, 'Normal Forms'),
(4, 'SELECT Statements'),
(4, 'Joins and Unions');

-- Productivity Seed
INSERT INTO task_categories (id, user_id, name) VALUES
(1, 4, 'Studies'),
(2, 4, 'Personal');

INSERT INTO task_priorities (id, user_id, name, color) VALUES
(1, 4, 'High', '#ff0000'),
(2, 4, 'Medium', '#ffaa00');

INSERT INTO tasks (user_id, task, category_id, priority_id) VALUES
(4, 'Complete PHP Project', 1, 1),
(4, 'Buy groceries', 2, 2);

-- Community Seed
INSERT INTO requests (id, user_id, skill, status, reviewer_id) VALUES
(1, 4, 'PHP Development', 'pending', NULL),
(2, 5, 'Python Scripting', 'accepted', 7);

INSERT INTO reviews (request_id, reviewer_id, marks, comment) VALUES
(2, 7, 85, 'Excellent understanding of Python basics.');

INSERT INTO faculty_feedback_forms (id, faculty_id, is_active) VALUES (1, 2, 1);
INSERT INTO faculty_feedback_questions (form_id, question_text) VALUES 
(1, 'How clear are the instructor explanations?'),
(1, 'Is the course material up to date?');
