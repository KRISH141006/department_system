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
(5, 'Student Two', 'student2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'ICT-B', 'Sem-6', '21IT002', 1),
(6, 'Student Three', 'student3@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 'ICT-A', 'Sem-6', '21IT003', 1),
(7, 'Expert One', 'expert1@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert', NULL, NULL, NULL, 1),
(8, 'Expert Two', 'expert2@ict.com', '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert', NULL, NULL, NULL, 1);

-- Profiles
INSERT INTO profiles (user_id, branch, skills, bio) VALUES
(1, 'ICT', 'Management, Security', 'System administrator for the ICT department.'),
(2, 'ICT', 'Web Development, PHP', 'Assistant Professor in ICT Department.'),
(3, 'ICT', 'Database Management, SQL', 'Associate Professor in ICT Department.'),
(4, 'ICT', 'HTML, CSS, JS', '3rd year student interested in Full Stack.'),
(5, 'ICT', 'Python, Data Science', '3rd year student specializing in AI.'),
(6, 'ICT', 'Networking, Cisco', '3rd year student focusing on infrastructure.'),
(7, 'Industry', 'System Architecture', 'Senior Architect at Tech Corp.'),
(8, 'Industry', 'Product Management', 'Expert in Agile and Scrum.');

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
