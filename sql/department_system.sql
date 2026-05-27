-- Unified Database Schema for Department Community & Academic Management System

CREATE DATABASE IF NOT EXISTS department_system;
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
    role ENUM('student','faculty','alumni','senior','hod','creator','expert') NOT NULL,
    class_name VARCHAR(50),
    semester VARCHAR(20),
    batch VARCHAR(30),
    roll_no VARCHAR(50),
    emp_id VARCHAR(50),
    linkedin_url VARCHAR(255),
    is_verified TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- PROFILES TABLE (from Module 3)
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
    lecture_start_time TIME NOT NULL,
    lecture_end_time TIME NOT NULL,
    topic_type VARCHAR(100),
    assignment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feedback_selector (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selected_student_id INT NOT NULL,
    selected_date DATE NOT NULL,
    FOREIGN KEY (selected_student_id) REFERENCES users(id) ON DELETE CASCADE
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

SELECT * FROM users;