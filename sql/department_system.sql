-- ============================================================
-- Department Management System - Schema V1
-- ============================================================
-- Conventions:
--   • All PKs are INT AUTO_INCREMENT unless a natural composite
--     key exists (junction tables).
--   • FKs always ON DELETE CASCADE for child records,
--     ON DELETE SET NULL for optional/audit references.
--   • UNIQUE constraints are explicit, not left to app logic.
--   • TEXT used only for genuinely unbounded content.
--   • Timestamps: created_at DEFAULT CURRENT_TIMESTAMP,
--     updated_at ON UPDATE CURRENT_TIMESTAMP.
-- ============================================================

DROP DATABASE IF EXISTS dept_system;
CREATE DATABASE dept_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dept_system;

-- ============================================================
-- DOMAIN 1: IDENTITY & ACCESS
-- ============================================================

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(120) NOT NULL UNIQUE,
    phone       VARCHAR(20),
    password    VARCHAR(255) NOT NULL,
    role        ENUM('student','faculty','expert','admin') NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Universal profile — attributes that apply to ALL roles
CREATE TABLE profiles (
    user_id         INT PRIMARY KEY,
    bio             TEXT,
    github_url      VARCHAR(255),
    leetcode_url    VARCHAR(255),
    linkedin_url    VARCHAR(255),
    portfolio_url   VARCHAR(255),
    skills          TEXT,           -- stored as comma-sep or JSON
    hobbies         TEXT,
    community_score INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Student-specific extension
CREATE TABLE students (
    user_id      INT PRIMARY KEY,
    gr_no        VARCHAR(50) NOT NULL UNIQUE,
    roll_no      VARCHAR(50),
    class_id     INT NOT NULL,       -- FK added after classes table
    batch        VARCHAR(30),
    target_role  VARCHAR(100),
    pac_category ENUM('premium','average','challenged') NOT NULL DEFAULT 'average',
    CONSTRAINT fk_students_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    -- fk_students_class added after classes is created
);

-- Faculty-specific extension
CREATE TABLE faculty (
    user_id             INT PRIMARY KEY,
    emp_id              VARCHAR(50) NOT NULL UNIQUE,
    is_cc               TINYINT(1) NOT NULL DEFAULT 0,  -- is class coordinator
    coordinated_class_id INT NULL,
    teaching_interests  TEXT,
    CONSTRAINT fk_faculty_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Expert/Alumni-specific extension
CREATE TABLE experts (
    user_id         INT PRIMARY KEY,
    company         VARCHAR(255),
    designation     VARCHAR(255),
    expertise_area  TEXT,
    experience_years INT,
    is_alumni       TINYINT(1) NOT NULL DEFAULT 0,
    college_name    VARCHAR(255),
    graduation_year YEAR,
    degree          VARCHAR(100),
    CONSTRAINT fk_experts_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE permissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description     TEXT
);

-- Junction table — composite PK, no surrogate needed
CREATE TABLE role_permissions (
    role          ENUM('student','faculty','expert','admin') NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    CONSTRAINT fk_rp_permission
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);


-- ============================================================
-- DOMAIN 2: ACADEMIC STRUCTURE
-- ============================================================

CREATE TABLE classes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    semester   TINYINT NOT NULL,
    branch     VARCHAR(100) NOT NULL,
    program    VARCHAR(100) NOT NULL,           -- e.g. B.E., M.Tech
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Now that classes exists, add the FK on students
ALTER TABLE students
    ADD CONSTRAINT fk_students_class
        FOREIGN KEY (class_id) REFERENCES classes(id);

-- Link faculty CCs to their class
ALTER TABLE faculty
    ADD CONSTRAINT fk_faculty_coordinated_class
        FOREIGN KEY (coordinated_class_id) REFERENCES classes(id) ON DELETE SET NULL;

CREATE TABLE subjects (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    code       VARCHAR(30)  NOT NULL UNIQUE,
    type       ENUM('core','elective') NOT NULL DEFAULT 'core',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE units (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    unit_no    TINYINT NOT NULL,
    name       VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_units_subject_no (subject_id, unit_no),
    CONSTRAINT fk_units_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE topics (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    unit_id     INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    CONSTRAINT fk_topics_unit
        FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);

-- Explicit class ↔ subject mapping (the join that was previously implicit)
CREATE TABLE class_subjects (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    class_id   INT NOT NULL,
    subject_id INT NOT NULL,
    is_locked  TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_subject (class_id, subject_id),
    CONSTRAINT fk_cs_class
        FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    CONSTRAINT fk_cs_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);


-- ============================================================
-- DOMAIN 3: ACADEMIC ENROLLMENT
-- ============================================================

-- Which faculty member teaches which (class, subject) pairing
CREATE TABLE faculty_subjects (
    faculty_id       INT NOT NULL,
    class_subject_id INT NOT NULL,
    PRIMARY KEY (faculty_id, class_subject_id),
    CONSTRAINT fk_fs_faculty
        FOREIGN KEY (faculty_id)       REFERENCES faculty(user_id)       ON DELETE CASCADE,
    CONSTRAINT fk_fs_class_subject
        FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id)     ON DELETE CASCADE
);

-- Which elective subjects a student is enrolled in.
-- Core subjects are implied by students.class_id → class_subjects.
-- Using class_subject_id keeps full class context (not just subject_id).
CREATE TABLE student_subjects (
    student_id       INT NOT NULL,
    class_subject_id INT NOT NULL,          -- changed from subject_id to preserve class context
    PRIMARY KEY (student_id, class_subject_id),
    CONSTRAINT fk_ss_student
        FOREIGN KEY (student_id)       REFERENCES students(user_id)      ON DELETE CASCADE,
    CONSTRAINT fk_ss_class_subject
        FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id)     ON DELETE CASCADE
);


-- ============================================================
-- DOMAIN 4: ELECTIVE WORKFLOW
-- ============================================================

-- Defines the open/close window for elective selection per semester
CREATE TABLE elective_windows (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    semester   TINYINT NOT NULL,
    is_locked  TINYINT(1) NOT NULL DEFAULT 0,
    opened_by  INT NOT NULL,                 -- user who opened the window
    opened_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at  TIMESTAMP NULL,
    CONSTRAINT fk_ew_opened_by
        FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE elective_change_requests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    old_subject_id INT NOT NULL,
    new_subject_id INT NOT NULL,
    reason         TEXT NOT NULL,
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by    INT NULL,
    approved_at    TIMESTAMP NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ecr_student
        FOREIGN KEY (student_id)     REFERENCES students(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_ecr_old_subject
        FOREIGN KEY (old_subject_id) REFERENCES subjects(id)      ON DELETE RESTRICT,
    CONSTRAINT fk_ecr_new_subject
        FOREIGN KEY (new_subject_id) REFERENCES subjects(id)      ON DELETE RESTRICT,
    CONSTRAINT fk_ecr_approved_by
        FOREIGN KEY (approved_by)    REFERENCES users(id)         ON DELETE SET NULL
);


-- ============================================================
-- DOMAIN 5: FACULTY FEEDBACK
-- ============================================================

-- Scoped to a (faculty, class_subject) pair so the same faculty teaching
-- the same subject to two different classes gets separate forms.
CREATE TABLE feedback_forms (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id       INT NOT NULL,
    class_subject_id INT NOT NULL,          -- was subject_id; now carries class context
    title            VARCHAR(255) NOT NULL,
    description      TEXT,
    status           ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ff_faculty
        FOREIGN KEY (faculty_id)       REFERENCES faculty(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_ff_class_subject
        FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE
);

CREATE TABLE feedback_questions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    form_id       INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('rating','mcq','text') NOT NULL DEFAULT 'rating',
    options       TEXT NULL,               -- JSON array for MCQ options
    CONSTRAINT fk_fq_form
        FOREIGN KEY (form_id) REFERENCES feedback_forms(id) ON DELETE CASCADE
);

CREATE TABLE feedback_responses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    form_id     INT NOT NULL,
    question_id INT NOT NULL,
    student_id  INT NOT NULL,
    rating      TINYINT NULL,
    answer_text TEXT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- A student answers each question exactly once per form
    UNIQUE KEY uq_response (form_id, question_id, student_id),
    CONSTRAINT fk_fr_form
        FOREIGN KEY (form_id)     REFERENCES feedback_forms(id)     ON DELETE CASCADE,
    CONSTRAINT fk_fr_question
        FOREIGN KEY (question_id) REFERENCES feedback_questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_fr_student
        FOREIGN KEY (student_id)  REFERENCES students(user_id)      ON DELETE CASCADE
);


-- ============================================================
-- DOMAIN 6: SYLLABUS VERIFICATION
-- ============================================================

-- Faculty logs each lecture. class_id + subject_id kept explicit
-- (not collapsed to class_subject_id) for fast direct queries.
CREATE TABLE lecture_records (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id   INT NOT NULL,
    class_id     INT NOT NULL,
    subject_id   INT NOT NULL,
    topic_id     INT NOT NULL,
    lecture_date DATE NOT NULL,
    start_time   TIME NOT NULL,
    end_time     TIME NOT NULL,
    assignment   TEXT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lr_faculty
        FOREIGN KEY (faculty_id)  REFERENCES faculty(user_id)  ON DELETE RESTRICT,
    CONSTRAINT fk_lr_class
        FOREIGN KEY (class_id)    REFERENCES classes(id)       ON DELETE RESTRICT,
    CONSTRAINT fk_lr_subject
        FOREIGN KEY (subject_id)  REFERENCES subjects(id)      ON DELETE RESTRICT,
    CONSTRAINT fk_lr_topic
        FOREIGN KEY (topic_id)    REFERENCES topics(id)        ON DELETE RESTRICT
);

-- One student is randomly assigned to verify each lecture
CREATE TABLE verification_assignments (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    lecture_record_id INT NOT NULL,
    student_id        INT NOT NULL,
    assigned_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Only one verification assignment per student per lecture
    UNIQUE KEY uq_va (lecture_record_id, student_id),
    CONSTRAINT fk_va_lecture
        FOREIGN KEY (lecture_record_id) REFERENCES lecture_records(id) ON DELETE CASCADE,
    CONSTRAINT fk_va_student
        FOREIGN KEY (student_id)        REFERENCES students(user_id)   ON DELETE CASCADE
);

-- The actual verification submitted by the assigned student
CREATE TABLE lecture_verifications (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    lecture_record_id INT NOT NULL,
    student_id        INT NOT NULL,
    status            ENUM('pending','verified','disputed') NOT NULL DEFAULT 'pending',
    remarks           TEXT NULL,
    verified_at       TIMESTAMP NULL,
    -- One verification entry per student per lecture
    UNIQUE KEY uq_lv (lecture_record_id, student_id),
    CONSTRAINT fk_lv_lecture
        FOREIGN KEY (lecture_record_id) REFERENCES lecture_records(id) ON DELETE CASCADE,
    CONSTRAINT fk_lv_student
        FOREIGN KEY (student_id)        REFERENCES students(user_id)   ON DELETE CASCADE
);


-- ============================================================
-- DOMAIN 7: PRODUCTIVITY
-- ============================================================

CREATE TABLE task_categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    name       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cat_user_name (user_id, name),
    CONSTRAINT fk_tc_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE task_priorities (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    name       VARCHAR(100) NOT NULL,
    color      VARCHAR(20) NOT NULL DEFAULT '#888888',
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pri_user_name (user_id, name),
    CONSTRAINT fk_tp_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT NULL,
    priority_id INT NULL,
    status      ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    deadline    DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_task_user
        FOREIGN KEY (user_id)     REFERENCES users(id)           ON DELETE CASCADE,
    CONSTRAINT fk_task_category
        FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_task_priority
        FOREIGN KEY (priority_id) REFERENCES task_priorities(id) ON DELETE SET NULL
);


-- ============================================================
-- DOMAIN 8: ASSIGNMENT MANAGEMENT
-- ============================================================

-- Scoped to a specific class via class_subject_id.
-- Without this, a faculty teaching CS101 to two classes
-- cannot target one specifically.
CREATE TABLE assignments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id       INT NOT NULL,
    class_subject_id INT NOT NULL,          -- carries both class + subject context
    title            VARCHAR(255) NOT NULL,
    description      TEXT,
    deadline         DATETIME NULL,
    resource_path    VARCHAR(255) NULL,
    resource_name    VARCHAR(255) NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asgn_faculty
        FOREIGN KEY (faculty_id)       REFERENCES faculty(user_id)   ON DELETE RESTRICT,
    CONSTRAINT fk_asgn_class_subject
        FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE
);

CREATE TABLE submissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT NOT NULL,
    student_id      INT NOT NULL,
    submission_path VARCHAR(255) NOT NULL,
    submission_name VARCHAR(255) NULL,
    grade           VARCHAR(20)  NULL,
    feedback        TEXT NULL,
    submitted_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    graded_at       TIMESTAMP NULL,
    -- One submission per student per assignment
    UNIQUE KEY uq_submission (assignment_id, student_id),
    CONSTRAINT fk_sub_assignment
        FOREIGN KEY (assignment_id) REFERENCES assignments(id)    ON DELETE CASCADE,
    CONSTRAINT fk_sub_student
        FOREIGN KEY (student_id)    REFERENCES students(user_id)  ON DELETE CASCADE
);


-- ============================================================
-- DOMAIN 9: LIVE CLASSES
-- ============================================================

CREATE TABLE live_sessions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    class_id   INT NOT NULL,
    subject_id INT NOT NULL,
    topic_id   INT NULL,               -- optional: may not be known before session starts
    room_code  VARCHAR(100) NOT NULL UNIQUE,
    status     ENUM('live','ended') NOT NULL DEFAULT 'live',
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at   TIMESTAMP NULL,
    CONSTRAINT fk_ls_faculty
        FOREIGN KEY (faculty_id) REFERENCES faculty(user_id)  ON DELETE RESTRICT,
    CONSTRAINT fk_ls_class
        FOREIGN KEY (class_id)   REFERENCES classes(id)       ON DELETE RESTRICT,
    CONSTRAINT fk_ls_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id)      ON DELETE RESTRICT,
    CONSTRAINT fk_ls_topic
        FOREIGN KEY (topic_id)   REFERENCES topics(id)        ON DELETE SET NULL
);


-- ============================================================
-- DOMAIN 10: COMMUNITY
-- ============================================================

CREATE TABLE review_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    skill       VARCHAR(150) NOT NULL,
    status      ENUM('pending','accepted','completed') NOT NULL DEFAULT 'pending',
    reviewer_id INT NULL,              -- assigned later
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rr_user
        FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL UNIQUE,    -- 1:1 with review_request
    reviewer_id INT NOT NULL,
    marks      TINYINT NOT NULL,
    comment    TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_request
        FOREIGN KEY (request_id)  REFERENCES review_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id)           ON DELETE RESTRICT
);


-- ============================================================
-- DOMAIN 11: GAMIFICATION
-- ============================================================

CREATE TABLE badges (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon        VARCHAR(255),          -- icon class or asset path
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_badges (
    user_id    INT NOT NULL,
    badge_id   INT NOT NULL,
    awarded_by INT NULL,               -- NULL = system-awarded
    awarded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_ub_user
        FOREIGN KEY (user_id)    REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_ub_badge
        FOREIGN KEY (badge_id)   REFERENCES badges(id) ON DELETE CASCADE,
    CONSTRAINT fk_ub_awarded_by
        FOREIGN KEY (awarded_by) REFERENCES users(id)  ON DELETE SET NULL
);

-- ============================================================
-- SEED DATA for dept_system (Schema V1)
-- ============================================================
-- All passwords = 1234 (bcrypt hash)
-- Semesters: 1, 3, 5, 7 (odd only)
-- Class layout:
--   Sem 1  → 1EK1, 1EK2                     (2 classes)
--   Sem 3  → 3EK1, 3EK2, 3EK3               (3 classes)
--   Sem 5  → 5EK1, 5EK2, 5EK3               (3 classes)
--   Sem 7  → 7EK1, 7EK2                     (2 classes)
-- Faculty: 15 (IDs 2–16), Admin: 1, Experts: 2 (17–18)
-- Students: 100 per class × 10 students = 100 total (IDs 100–199)
-- ============================================================

USE dept_system;

-- ============================================================
-- DOMAIN 1: IDENTITY & ACCESS
-- ============================================================

-- ------------------------------------------------------------
-- 1. permissions
-- ------------------------------------------------------------
INSERT INTO permissions (permission_name, description) VALUES
('view_student_dashboard', 'Access to student main dashboard'),
('view_faculty_dashboard', 'Access to faculty main dashboard'),
('view_expert_dashboard',  'Access to expert/reviewer dashboard'),
('view_admin_dashboard',   'Access to system admin panel'),
('submit_feedback',        'Permission to submit anonymous/faculty feedback'),
('manage_subjects',        'Permission to create/edit subjects and units'),
('manage_tasks',           'Permission to manage personal productivity tasks'),
('request_validation',     'Permission to create community validation requests'),
('review_requests',        'Permission to review and mark community requests'),
('manage_users',           'Full user management access'),
('select_electives',       'Access to elective subject selection interface');

-- ------------------------------------------------------------
-- 2. role_permissions
-- ------------------------------------------------------------
-- Admin gets everything
INSERT INTO role_permissions (role, permission_id)
SELECT 'admin', id FROM permissions;

-- Faculty
INSERT INTO role_permissions (role, permission_id)
SELECT 'faculty', id FROM permissions
WHERE permission_name IN ('view_faculty_dashboard','submit_feedback','manage_subjects','review_requests');

-- Student
INSERT INTO role_permissions (role, permission_id)
SELECT 'student', id FROM permissions
WHERE permission_name IN ('view_student_dashboard','submit_feedback','manage_tasks','request_validation','select_electives');

-- Expert
INSERT INTO role_permissions (role, permission_id)
SELECT 'expert', id FROM permissions
WHERE permission_name IN ('view_expert_dashboard','review_requests');


-- ------------------------------------------------------------
-- 3. users  (admin=1, faculty=2-16, experts=17-18, students=100-199)
-- ------------------------------------------------------------
INSERT INTO users (id, name, email, phone, password, role, is_verified) VALUES
-- Admin
(1,  'System Admin',       'admin@ict.com',       NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'admin',   1),
-- Faculty
(2,  'Dr. Asha Patel',     'faculty1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(3,  'Prof. Mehul Shah',   'faculty2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(4,  'Dr. Ritesh Joshi',   'faculty3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(5,  'Prof. Nisha Mehta',  'faculty4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(6,  'Dr. Kiran Desai',    'faculty5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(7,  'Prof. Bhavesh Trivedi','faculty6@ict.com',  NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(8,  'Dr. Pooja Vyas',     'faculty7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(9,  'Prof. Harshil Dave', 'faculty8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(10, 'Dr. Neha Parmar',    'faculty9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(11, 'Prof. Jignesh Rathod','faculty10@ict.com',  NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(12, 'Dr. Komal Shah',     'faculty11@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(13, 'Prof. Dhruv Patel',  'faculty12@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(14, 'Dr. Hetal Pandya',   'faculty13@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(15, 'Prof. Manan Bhatt',  'faculty14@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
(16, 'Dr. Rupal Thakkar',  'faculty15@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'faculty', 1),
-- Experts
(17, 'Expert One',         'expert1@ict.com',     NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert',  1),
(18, 'Expert Two',         'expert2@ict.com',     NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'expert',  1),
-- Students: Sem 1 – 1EK1
(100,'Aarav Mehta',        's_1ek1_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(101,'Vihaan Joshi',       's_1ek1_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(102,'Vivaan Desai',       's_1ek1_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(103,'Aditya Parmar',      's_1ek1_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(104,'Arjun Vyas',         's_1ek1_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(105,'Sai Trivedi',        's_1ek1_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(106,'Reyansh Rathod',     's_1ek1_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(107,'Krishna Dave',       's_1ek1_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(108,'Ishaan Patel',       's_1ek1_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(109,'Kabir Shah',         's_1ek1_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 1 – 1EK2
(110,'Aarav Mehta',        's_1ek2_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(111,'Vihaan Joshi',       's_1ek2_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(112,'Vivaan Desai',       's_1ek2_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(113,'Aditya Parmar',      's_1ek2_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(114,'Arjun Vyas',         's_1ek2_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(115,'Sai Trivedi',        's_1ek2_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(116,'Reyansh Rathod',     's_1ek2_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(117,'Krishna Dave',       's_1ek2_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(118,'Ishaan Patel',       's_1ek2_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(119,'Kabir Shah',         's_1ek2_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 3 – 3EK1
(120,'Aarav Desai',        's_3ek1_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(121,'Vihaan Parmar',      's_3ek1_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(122,'Vivaan Vyas',        's_3ek1_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(123,'Aditya Trivedi',     's_3ek1_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(124,'Arjun Rathod',       's_3ek1_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(125,'Sai Dave',           's_3ek1_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(126,'Reyansh Patel',      's_3ek1_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(127,'Krishna Shah',       's_3ek1_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(128,'Ishaan Mehta',       's_3ek1_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(129,'Kabir Joshi',        's_3ek1_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 3 – 3EK2
(130,'Aarav Desai',        's_3ek2_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(131,'Vihaan Parmar',      's_3ek2_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(132,'Vivaan Vyas',        's_3ek2_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(133,'Aditya Trivedi',     's_3ek2_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(134,'Arjun Rathod',       's_3ek2_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(135,'Sai Dave',           's_3ek2_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(136,'Reyansh Patel',      's_3ek2_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(137,'Krishna Shah',       's_3ek2_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(138,'Ishaan Mehta',       's_3ek2_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(139,'Kabir Joshi',        's_3ek2_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 3 – 3EK3
(140,'Aarav Desai',        's_3ek3_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(141,'Vihaan Parmar',      's_3ek3_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(142,'Vivaan Vyas',        's_3ek3_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(143,'Aditya Trivedi',     's_3ek3_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(144,'Arjun Rathod',       's_3ek3_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(145,'Sai Dave',           's_3ek3_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(146,'Reyansh Patel',      's_3ek3_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(147,'Krishna Shah',       's_3ek3_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(148,'Ishaan Mehta',       's_3ek3_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(149,'Kabir Joshi',        's_3ek3_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 5 – 5EK1
(150,'Aarav Vyas',         's_5ek1_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(151,'Vihaan Trivedi',     's_5ek1_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(152,'Vivaan Rathod',      's_5ek1_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(153,'Aditya Dave',        's_5ek1_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(154,'Arjun Patel',        's_5ek1_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(155,'Sai Shah',           's_5ek1_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(156,'Reyansh Mehta',      's_5ek1_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(157,'Krishna Joshi',      's_5ek1_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(158,'Ishaan Desai',       's_5ek1_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(159,'Kabir Parmar',       's_5ek1_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 5 – 5EK2
(160,'Aarav Vyas',         's_5ek2_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(161,'Vihaan Trivedi',     's_5ek2_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(162,'Vivaan Rathod',      's_5ek2_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(163,'Aditya Dave',        's_5ek2_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(164,'Arjun Patel',        's_5ek2_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(165,'Sai Shah',           's_5ek2_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(166,'Reyansh Mehta',      's_5ek2_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(167,'Krishna Joshi',      's_5ek2_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(168,'Ishaan Desai',       's_5ek2_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(169,'Kabir Parmar',       's_5ek2_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 5 – 5EK3
(170,'Aarav Vyas',         's_5ek3_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(171,'Vihaan Trivedi',     's_5ek3_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(172,'Vivaan Rathod',      's_5ek3_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(173,'Aditya Dave',        's_5ek3_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(174,'Arjun Patel',        's_5ek3_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(175,'Sai Shah',           's_5ek3_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(176,'Reyansh Mehta',      's_5ek3_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(177,'Krishna Joshi',      's_5ek3_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(178,'Ishaan Desai',       's_5ek3_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(179,'Kabir Parmar',       's_5ek3_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 7 – 7EK1
(180,'Aarav Rathod',       's_7ek1_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(181,'Vihaan Dave',        's_7ek1_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(182,'Vivaan Patel',       's_7ek1_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(183,'Aditya Shah',        's_7ek1_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(184,'Arjun Mehta',        's_7ek1_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(185,'Sai Joshi',          's_7ek1_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(186,'Reyansh Desai',      's_7ek1_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(187,'Krishna Parmar',     's_7ek1_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(188,'Ishaan Vyas',        's_7ek1_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(189,'Kabir Trivedi',      's_7ek1_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
-- Students: Sem 7 – 7EK2
(190,'Aarav Rathod',       's_7ek2_1@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(191,'Vihaan Dave',        's_7ek2_2@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(192,'Vivaan Patel',       's_7ek2_3@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(193,'Aditya Shah',        's_7ek2_4@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(194,'Arjun Mehta',        's_7ek2_5@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(195,'Sai Joshi',          's_7ek2_6@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(196,'Reyansh Desai',      's_7ek2_7@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(197,'Krishna Parmar',     's_7ek2_8@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(198,'Ishaan Vyas',        's_7ek2_9@ict.com',    NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1),
(199,'Kabir Trivedi',      's_7ek2_10@ict.com',   NULL, '$2y$12$xDPwZaUnIN7U86h9oDzJl.7aC2.2upxBdCVL7eJdI.o5VR0mjH9.u', 'student', 1);


-- ------------------------------------------------------------
-- 4. profiles  (universal – one row per user)
-- ------------------------------------------------------------
-- Admin
INSERT INTO profiles (user_id, bio) VALUES (1, 'System administrator profile');

-- Faculty (user_ids 2–16)
INSERT INTO profiles (user_id, bio, skills) VALUES
(2,  'Faculty profile', 'Teaching, Mentoring'),
(3,  'Faculty profile', 'Teaching, Mentoring'),
(4,  'Faculty profile', 'Teaching, Mentoring'),
(5,  'Faculty profile', 'Teaching, Mentoring'),
(6,  'Faculty profile', 'Teaching, Mentoring'),
(7,  'Faculty profile', 'Teaching, Mentoring'),
(8,  'Faculty profile', 'Teaching, Mentoring'),
(9,  'Faculty profile', 'Teaching, Mentoring'),
(10, 'Faculty profile', 'Teaching, Mentoring'),
(11, 'Faculty profile', 'Teaching, Mentoring'),
(12, 'Faculty profile', 'Teaching, Mentoring'),
(13, 'Faculty profile', 'Teaching, Mentoring'),
(14, 'Faculty profile', 'Teaching, Mentoring'),
(15, 'Faculty profile', 'Teaching, Mentoring'),
(16, 'Faculty profile', 'Teaching, Mentoring');

-- Experts
INSERT INTO profiles (user_id, bio, skills) VALUES
(17, 'Expert profile', 'PHP, Python, Project Review'),
(18, 'Expert profile', 'PHP, Python, Project Review');

-- Students (all share same defaults; bulk insert)
INSERT INTO profiles (user_id, bio, skills, hobbies) VALUES
(100,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(101,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(102,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(103,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(104,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(105,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(106,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(107,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(108,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(109,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(110,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(111,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(112,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(113,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(114,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(115,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(116,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(117,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(118,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(119,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(120,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(121,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(122,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(123,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(124,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(125,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(126,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(127,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(128,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(129,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(130,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(131,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(132,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(133,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(134,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(135,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(136,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(137,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(138,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(139,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(140,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(141,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(142,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(143,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(144,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(145,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(146,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(147,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(148,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(149,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(150,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(151,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(152,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(153,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(154,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(155,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(156,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(157,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(158,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(159,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(160,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(161,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(162,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(163,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(164,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(165,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(166,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(167,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(168,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(169,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(170,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(171,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(172,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(173,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(174,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(175,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(176,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(177,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(178,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(179,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(180,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(181,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(182,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(183,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(184,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(185,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(186,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(187,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(188,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(189,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(190,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(191,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(192,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(193,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(194,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(195,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(196,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(197,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(198,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading'),
(199,'Student profile','HTML, CSS, PHP, MySQL','Coding, Reading');


-- ------------------------------------------------------------
-- 5. faculty  (role-specific extension)
--    is_cc = 1 for class coordinators (one per class)
--    Faculty 2–11 are CCs; 12–16 are not
-- ------------------------------------------------------------
INSERT INTO faculty (user_id, emp_id, is_cc, teaching_interests) VALUES
(2,  'F001', 1, 'Web Development, DBMS, Java'),   -- CC: 1EK1
(3,  'F002', 1, 'Web Development, DBMS, Java'),   -- CC: 1EK2
(4,  'F003', 1, 'Web Development, DBMS, Java'),   -- CC: 3EK1
(5,  'F004', 1, 'Web Development, DBMS, Java'),   -- CC: 3EK2
(6,  'F005', 1, 'Web Development, DBMS, Java'),   -- CC: 3EK3
(7,  'F006', 1, 'Web Development, DBMS, Java'),   -- CC: 5EK1
(8,  'F007', 1, 'Web Development, DBMS, Java'),   -- CC: 5EK2
(9,  'F008', 1, 'Web Development, DBMS, Java'),   -- CC: 5EK3
(10, 'F009', 1, 'Web Development, DBMS, Java'),   -- CC: 7EK1
(11, 'F010', 1, 'Web Development, DBMS, Java'),   -- CC: 7EK2
(12, 'F011', 0, 'Web Development, DBMS, Java'),
(13, 'F012', 0, 'Web Development, DBMS, Java'),
(14, 'F013', 0, 'Web Development, DBMS, Java'),
(15, 'F014', 0, 'Web Development, DBMS, Java'),
(16, 'F015', 0, 'Web Development, DBMS, Java');


-- ------------------------------------------------------------
-- 6. experts  (role-specific extension)
-- ------------------------------------------------------------
INSERT INTO experts (user_id, company, designation, expertise_area, experience_years, is_alumni, college_name, graduation_year, degree) VALUES
(17, 'Industry', 'Software Engineer', 'Software Development', 3, 1, 'Marwadi University', 2022, 'B.Tech'),
(18, 'Industry', 'Software Engineer', 'Software Development', 3, 1, 'Marwadi University', 2022, 'B.Tech');


-- ============================================================
-- DOMAIN 2: ACADEMIC STRUCTURE
-- ============================================================

-- ------------------------------------------------------------
-- 7. classes
-- ------------------------------------------------------------
INSERT INTO classes (id, name, semester, branch, program) VALUES
(1, '1EK1', 1, 'ICT', 'B.E.'),
(2, '1EK2', 1, 'ICT', 'B.E.'),
(3, '3EK1', 3, 'ICT', 'B.E.'),
(4, '3EK2', 3, 'ICT', 'B.E.'),
(5, '3EK3', 3, 'ICT', 'B.E.'),
(6, '5EK1', 5, 'ICT', 'B.E.'),
(7, '5EK2', 5, 'ICT', 'B.E.'),
(8, '5EK3', 5, 'ICT', 'B.E.'),
(9, '7EK1', 7, 'ICT', 'B.E.'),
(10,'7EK2', 7, 'ICT', 'B.E.');


-- ------------------------------------------------------------
-- 8. students  (role-specific extension)
--    gr_no format: GR<user_id>
--    pac_category matches original seed data
-- ------------------------------------------------------------
-- Sem 1 – 1EK1  (class_id=1)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(100,'GR100','1ek-1-01',1,'2025-29','Full Stack Developer','average'),
(101,'GR101','1ek-1-02',1,'2025-29','Full Stack Developer','challenged'),
(102,'GR102','1ek-1-03',1,'2025-29','Full Stack Developer','average'),
(103,'GR103','1ek-1-04',1,'2025-29','Full Stack Developer','premium'),
(104,'GR104','1ek-1-05',1,'2025-29','Full Stack Developer','premium'),
(105,'GR105','1ek-1-06',1,'2025-29','Full Stack Developer','premium'),
(106,'GR106','1ek-1-07',1,'2025-29','Full Stack Developer','average'),
(107,'GR107','1ek-1-08',1,'2025-29','Full Stack Developer','challenged'),
(108,'GR108','1ek-1-09',1,'2025-29','Full Stack Developer','premium'),
(109,'GR109','1ek-1-10',1,'2025-29','Full Stack Developer','premium');

-- Sem 1 – 1EK2  (class_id=2)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(110,'GR110','1ek-2-01',2,'2025-29','Full Stack Developer','premium'),
(111,'GR111','1ek-2-02',2,'2025-29','Full Stack Developer','challenged'),
(112,'GR112','1ek-2-03',2,'2025-29','Full Stack Developer','challenged'),
(113,'GR113','1ek-2-04',2,'2025-29','Full Stack Developer','premium'),
(114,'GR114','1ek-2-05',2,'2025-29','Full Stack Developer','challenged'),
(115,'GR115','1ek-2-06',2,'2025-29','Full Stack Developer','average'),
(116,'GR116','1ek-2-07',2,'2025-29','Full Stack Developer','premium'),
(117,'GR117','1ek-2-08',2,'2025-29','Full Stack Developer','challenged'),
(118,'GR118','1ek-2-09',2,'2025-29','Full Stack Developer','average'),
(119,'GR119','1ek-2-10',2,'2025-29','Full Stack Developer','average');

-- Sem 3 – 3EK1  (class_id=3)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(120,'GR120','3ek-1-01',3,'2023-27','Full Stack Developer','challenged'),
(121,'GR121','3ek-1-02',3,'2023-27','Full Stack Developer','premium'),
(122,'GR122','3ek-1-03',3,'2023-27','Full Stack Developer','premium'),
(123,'GR123','3ek-1-04',3,'2023-27','Full Stack Developer','premium'),
(124,'GR124','3ek-1-05',3,'2023-27','Full Stack Developer','average'),
(125,'GR125','3ek-1-06',3,'2023-27','Full Stack Developer','premium'),
(126,'GR126','3ek-1-07',3,'2023-27','Full Stack Developer','premium'),
(127,'GR127','3ek-1-08',3,'2023-27','Full Stack Developer','premium'),
(128,'GR128','3ek-1-09',3,'2023-27','Full Stack Developer','average'),
(129,'GR129','3ek-1-10',3,'2023-27','Full Stack Developer','average');

-- Sem 3 – 3EK2  (class_id=4)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(130,'GR130','3ek-2-01',4,'2023-27','Full Stack Developer','average'),
(131,'GR131','3ek-2-02',4,'2023-27','Full Stack Developer','average'),
(132,'GR132','3ek-2-03',4,'2023-27','Full Stack Developer','average'),
(133,'GR133','3ek-2-04',4,'2023-27','Full Stack Developer','challenged'),
(134,'GR134','3ek-2-05',4,'2023-27','Full Stack Developer','premium'),
(135,'GR135','3ek-2-06',4,'2023-27','Full Stack Developer','challenged'),
(136,'GR136','3ek-2-07',4,'2023-27','Full Stack Developer','premium'),
(137,'GR137','3ek-2-08',4,'2023-27','Full Stack Developer','average'),
(138,'GR138','3ek-2-09',4,'2023-27','Full Stack Developer','premium'),
(139,'GR139','3ek-2-10',4,'2023-27','Full Stack Developer','average');

-- Sem 3 – 3EK3  (class_id=5)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(140,'GR140','3ek-3-01',5,'2023-27','Full Stack Developer','challenged'),
(141,'GR141','3ek-3-02',5,'2023-27','Full Stack Developer','average'),
(142,'GR142','3ek-3-03',5,'2023-27','Full Stack Developer','average'),
(143,'GR143','3ek-3-04',5,'2023-27','Full Stack Developer','average'),
(144,'GR144','3ek-3-05',5,'2023-27','Full Stack Developer','premium'),
(145,'GR145','3ek-3-06',5,'2023-27','Full Stack Developer','premium'),
(146,'GR146','3ek-3-07',5,'2023-27','Full Stack Developer','premium'),
(147,'GR147','3ek-3-08',5,'2023-27','Full Stack Developer','premium'),
(148,'GR148','3ek-3-09',5,'2023-27','Full Stack Developer','average'),
(149,'GR149','3ek-3-10',5,'2023-27','Full Stack Developer','challenged');

-- Sem 5 – 5EK1  (class_id=6)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(150,'GR150','5ek-1-01',6,'2021-25','Full Stack Developer','average'),
(151,'GR151','5ek-1-02',6,'2021-25','Full Stack Developer','premium'),
(152,'GR152','5ek-1-03',6,'2021-25','Full Stack Developer','average'),
(153,'GR153','5ek-1-04',6,'2021-25','Full Stack Developer','premium'),
(154,'GR154','5ek-1-05',6,'2021-25','Full Stack Developer','premium'),
(155,'GR155','5ek-1-06',6,'2021-25','Full Stack Developer','average'),
(156,'GR156','5ek-1-07',6,'2021-25','Full Stack Developer','average'),
(157,'GR157','5ek-1-08',6,'2021-25','Full Stack Developer','premium'),
(158,'GR158','5ek-1-09',6,'2021-25','Full Stack Developer','challenged'),
(159,'GR159','5ek-1-10',6,'2021-25','Full Stack Developer','premium');

-- Sem 5 – 5EK2  (class_id=7)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(160,'GR160','5ek-2-01',7,'2021-25','Full Stack Developer','premium'),
(161,'GR161','5ek-2-02',7,'2021-25','Full Stack Developer','challenged'),
(162,'GR162','5ek-2-03',7,'2021-25','Full Stack Developer','challenged'),
(163,'GR163','5ek-2-04',7,'2021-25','Full Stack Developer','average'),
(164,'GR164','5ek-2-05',7,'2021-25','Full Stack Developer','premium'),
(165,'GR165','5ek-2-06',7,'2021-25','Full Stack Developer','premium'),
(166,'GR166','5ek-2-07',7,'2021-25','Full Stack Developer','premium'),
(167,'GR167','5ek-2-08',7,'2021-25','Full Stack Developer','premium'),
(168,'GR168','5ek-2-09',7,'2021-25','Full Stack Developer','average'),
(169,'GR169','5ek-2-10',7,'2021-25','Full Stack Developer','challenged');

-- Sem 5 – 5EK3  (class_id=8)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(170,'GR170','5ek-3-01',8,'2021-25','Full Stack Developer','challenged'),
(171,'GR171','5ek-3-02',8,'2021-25','Full Stack Developer','premium'),
(172,'GR172','5ek-3-03',8,'2021-25','Full Stack Developer','premium'),
(173,'GR173','5ek-3-04',8,'2021-25','Full Stack Developer','premium'),
(174,'GR174','5ek-3-05',8,'2021-25','Full Stack Developer','premium'),
(175,'GR175','5ek-3-06',8,'2021-25','Full Stack Developer','average'),
(176,'GR176','5ek-3-07',8,'2021-25','Full Stack Developer','average'),
(177,'GR177','5ek-3-08',8,'2021-25','Full Stack Developer','average'),
(178,'GR178','5ek-3-09',8,'2021-25','Full Stack Developer','average'),
(179,'GR179','5ek-3-10',8,'2021-25','Full Stack Developer','challenged');

-- Sem 7 – 7EK1  (class_id=9)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(180,'GR180','7ek-1-01',9,'2019-23','Full Stack Developer','premium'),
(181,'GR181','7ek-1-02',9,'2019-23','Full Stack Developer','average'),
(182,'GR182','7ek-1-03',9,'2019-23','Full Stack Developer','average'),
(183,'GR183','7ek-1-04',9,'2019-23','Full Stack Developer','premium'),
(184,'GR184','7ek-1-05',9,'2019-23','Full Stack Developer','average'),
(185,'GR185','7ek-1-06',9,'2019-23','Full Stack Developer','challenged'),
(186,'GR186','7ek-1-07',9,'2019-23','Full Stack Developer','average'),
(187,'GR187','7ek-1-08',9,'2019-23','Full Stack Developer','average'),
(188,'GR188','7ek-1-09',9,'2019-23','Full Stack Developer','average'),
(189,'GR189','7ek-1-10',9,'2019-23','Full Stack Developer','average');

-- Sem 7 – 7EK2  (class_id=10)
INSERT INTO students (user_id, gr_no, roll_no, class_id, batch, target_role, pac_category) VALUES
(190,'GR190','7ek-2-01',10,'2019-23','Full Stack Developer','premium'),
(191,'GR191','7ek-2-02',10,'2019-23','Full Stack Developer','average'),
(192,'GR192','7ek-2-03',10,'2019-23','Full Stack Developer','average'),
(193,'GR193','7ek-2-04',10,'2019-23','Full Stack Developer','premium'),
(194,'GR194','7ek-2-05',10,'2019-23','Full Stack Developer','premium'),
(195,'GR195','7ek-2-06',10,'2019-23','Full Stack Developer','average'),
(196,'GR196','7ek-2-07',10,'2019-23','Full Stack Developer','average'),
(197,'GR197','7ek-2-08',10,'2019-23','Full Stack Developer','challenged'),
(198,'GR198','7ek-2-09',10,'2019-23','Full Stack Developer','average'),
(199,'GR199','7ek-2-10',10,'2019-23','Full Stack Developer','premium');


-- ------------------------------------------------------------
-- 9. subjects
--    IDs 1-4  → Sem 1 core
--    IDs 5-8  → Sem 3 core
--    IDs 9-12 → Sem 5 core
--    IDs 13-14→ Sem 5 electives
--    IDs 15-18→ Sem 7 core
-- ------------------------------------------------------------
INSERT INTO subjects (id, name, code, type) VALUES
-- Sem 1
(1,  'Basic Electrical Engineering',   'BEE101',  'core'),
(2,  'Engineering Mathematics-I',      'EM101',   'core'),
(3,  'Programming for Problem Solving','PPS101',  'core'),
(4,  'Physics',                        'PHY101',  'core'),
-- Sem 3
(5,  'Database Management System',     'DBMS301', 'core'),
(6,  'Object Oriented Programming',    'OOP301',  'core'),
(7,  'Signal and System',              'SNS301',  'core'),
(8,  'Probability and Statistics',     'PAS301',  'core'),
-- Sem 5
(9,  'Computer Networks',              'CN501',   'core'),
(10, 'Software Engineering',           'SE501',   'core'),
(11, 'Theory of Computation',          'TOC501',  'core'),
(12, 'Python Programming',             'PP501',   'core'),
(13, 'Mobile App Development',         'MAD501',  'elective'),
(14, 'Cloud Computing',                'CC501',   'elective'),
-- Sem 7
(15, 'Artificial Intelligence',        'AI701',   'core'),
(16, 'Cyber Security',                 'CS701',   'core'),
(17, 'Internet of Things',             'IOT701',  'core'),
(18, 'Big Data Analytics',             'BDA701',  'core');


-- ------------------------------------------------------------
-- 10. units  (2 units per subject for demo)
-- ------------------------------------------------------------
INSERT INTO units (id, subject_id, unit_no, name) VALUES
-- BEE101
(1,  1, 1, 'Introduction and Fundamentals'),
(2,  1, 2, 'Circuit Laws and Applications'),
-- EM101
(3,  2, 1, 'Matrices and Calculus'),
(4,  2, 2, 'Differential Equations'),
-- PPS101
(5,  3, 1, 'C Programming Basics'),
(6,  3, 2, 'Functions and Arrays'),
-- PHY101
(7,  4, 1, 'Mechanics and Optics'),
(8,  4, 2, 'Electrostatics'),
-- DBMS301
(9,  5, 1, 'ER Model and SQL'),
(10, 5, 2, 'Normalization and Transactions'),
-- OOP301
(11, 6, 1, 'Classes and Objects'),
(12, 6, 2, 'Inheritance and Polymorphism'),
-- SNS301
(13, 7, 1, 'Signals and Systems Basics'),
(14, 7, 2, 'Fourier Transform'),
-- PAS301
(15, 8, 1, 'Probability Distribution'),
(16, 8, 2, 'Statistical Inference'),
-- CN501
(17, 9, 1, 'OSI and TCP/IP'),
(18, 9, 2, 'Routing Protocols'),
-- SE501
(19, 10, 1, 'SDLC Models'),
(20, 10, 2, 'Agile and Testing'),
-- TOC501
(21, 11, 1, 'Finite Automata'),
(22, 11, 2, 'Turing Machines'),
-- PP501
(23, 12, 1, 'Python Basics'),
(24, 12, 2, 'OOP in Python'),
-- MAD501
(25, 13, 1, 'Android Basics'),
(26, 13, 2, 'UI Components'),
-- CC501
(27, 14, 1, 'Cloud Service Models'),
(28, 14, 2, 'Virtualization'),
-- AI701
(29, 15, 1, 'AI Fundamentals'),
(30, 15, 2, 'Search Algorithms'),
-- CS701
(31, 16, 1, 'Network Security'),
(32, 16, 2, 'Cryptography'),
-- IOT701
(33, 17, 1, 'Sensors and Devices'),
(34, 17, 2, 'IoT Protocols'),
-- BDA701
(35, 18, 1, 'Big Data Introduction'),
(36, 18, 2, 'Hadoop and Spark');


-- ------------------------------------------------------------
-- 11. topics  (2 topics per unit for demo)
-- ------------------------------------------------------------
INSERT INTO topics (unit_id, name, description) VALUES
-- BEE unit 1
(1,  'Basic Electrical Concepts',      'Voltage, current, resistance basics'),
(1,  'Ohms Law and Power',             'Ohms law and power calculations'),
-- BEE unit 2
(2,  'KVL and KCL',                    'Kirchhoffs voltage and current laws'),
(2,  'Series and Parallel Circuits',   'Analysis of circuit combinations'),
-- EM unit 1
(3,  'Matrix Operations',              'Addition, multiplication, inverse'),
(3,  'Differentiation',                'Rules of differentiation'),
-- EM unit 2
(4,  'First Order ODEs',               'Separable and linear equations'),
(4,  'Second Order ODEs',              'Homogeneous equations'),
-- PPS unit 1
(5,  'Variables and Data Types',       'C fundamentals'),
(5,  'Control Flow',                   'if, for, while loops'),
-- PPS unit 2
(6,  'Functions',                      'Defining and calling functions'),
(6,  'Arrays',                         '1D and 2D arrays'),
-- PHY unit 1
(7,  'Newtons Laws',                   'Laws of motion'),
(7,  'Geometrical Optics',             'Reflection and refraction'),
-- PHY unit 2
(8,  'Coulombs Law',                   'Electrostatic force'),
(8,  'Electric Field',                 'Field lines and Gausss law'),
-- DBMS unit 1
(9,  'ER Diagrams',                    'Entity-relationship modelling'),
(9,  'SQL Basics',                     'SELECT, INSERT, UPDATE, DELETE'),
-- DBMS unit 2
(10, 'Normal Forms',                   '1NF through BCNF'),
(10, 'Transaction Management',         'ACID properties'),
-- OOP unit 1
(11, 'Classes and Objects',            'OOP fundamentals'),
(11, 'Constructors and Destructors',   'Object lifecycle'),
-- OOP unit 2
(12, 'Inheritance',                    'Single and multiple inheritance'),
(12, 'Polymorphism',                   'Overloading and overriding'),
-- SNS unit 1
(13, 'Signal Classification',          'Continuous and discrete signals'),
(13, 'System Properties',              'Linearity, causality'),
-- SNS unit 2
(14, 'Fourier Series',                 'Periodic signal representation'),
(14, 'Fourier Transform',              'Frequency domain analysis'),
-- PAS unit 1
(15, 'Probability Axioms',             'Basic probability rules'),
(15, 'Random Variables',               'PMF and PDF'),
-- PAS unit 2
(16, 'Hypothesis Testing',             't-test and chi-square'),
(16, 'Regression Analysis',            'Linear regression'),
-- CN unit 1
(17, 'OSI Model',                      '7-layer architecture'),
(17, 'TCP/IP Suite',                   'Internet protocol stack'),
-- CN unit 2
(18, 'Distance Vector Routing',        'RIP protocol'),
(18, 'Link State Routing',             'OSPF protocol'),
-- SE unit 1
(19, 'Waterfall Model',                'Sequential SDLC'),
(19, 'Spiral Model',                   'Risk-driven SDLC'),
-- SE unit 2
(20, 'Agile Manifesto',                'Agile principles and Scrum'),
(20, 'Software Testing',               'Unit and integration testing'),
-- TOC unit 1
(21, 'DFA',                            'Deterministic finite automata'),
(21, 'NFA',                            'Non-deterministic finite automata'),
-- TOC unit 2
(22, 'Pushdown Automata',              'PDA and context-free languages'),
(22, 'Turing Machine',                 'TM definition and examples'),
-- Python unit 1
(23, 'Python Syntax',                  'Variables, data types, operators'),
(23, 'Control Structures',             'Conditionals and loops in Python'),
-- Python unit 2
(24, 'Classes in Python',              'Defining classes and objects'),
(24, 'Modules and Packages',           'Organizing Python code'),
-- MAD unit 1
(25, 'Android Architecture',           'Activity and fragment lifecycle'),
(25, 'Layouts',                        'XML and constraint layout'),
-- MAD unit 2
(26, 'RecyclerView',                   'List views in Android'),
(26, 'Intents',                        'Explicit and implicit intents'),
-- CC unit 1
(27, 'IaaS PaaS SaaS',                 'Cloud service models'),
(27, 'Public and Private Cloud',       'Deployment models'),
-- CC unit 2
(28, 'Hypervisors',                    'Type 1 and Type 2 hypervisors'),
(28, 'Containers',                     'Docker and Kubernetes basics'),
-- AI unit 1
(29, 'History of AI',                  'Milestones and current state'),
(29, 'Problem Formulation',            'State space representation'),
-- AI unit 2
(30, 'BFS and DFS',                    'Uninformed search'),
(30, 'A* Algorithm',                   'Informed search'),
-- CS unit 1
(31, 'CIA Triad',                      'Confidentiality, integrity, availability'),
(31, 'Firewalls and IDS',              'Perimeter security'),
-- CS unit 2
(32, 'Symmetric Encryption',           'AES and DES'),
(32, 'Asymmetric Encryption',          'RSA and public key infrastructure'),
-- IOT unit 1
(33, 'Sensor Types',                   'Temperature, humidity, motion'),
(33, 'Microcontrollers',               'Arduino and Raspberry Pi'),
-- IOT unit 2
(34, 'MQTT Protocol',                  'Message queuing for IoT'),
(34, 'CoAP Protocol',                  'Constrained application protocol'),
-- BDA unit 1
(35, 'Big Data Characteristics',       '5 Vs of big data'),
(35, 'Hadoop Ecosystem',               'HDFS and MapReduce'),
-- BDA unit 2
(36, 'Apache Spark',                   'Spark RDD and DataFrames'),
(36, 'Data Pipelines',                 'ETL in big data');


-- ============================================================
-- DOMAIN 3: ACADEMIC STRUCTURE – class_subjects & enrollment
-- ============================================================

-- ------------------------------------------------------------
-- 12. class_subjects  (which subjects belong to which class)
-- ------------------------------------------------------------
-- Sem 1: 1EK1 and 1EK2 share all 4 core subjects
INSERT INTO class_subjects (id, class_id, subject_id) VALUES
(1,  1, 1), (2,  1, 2), (3,  1, 3), (4,  1, 4),   -- 1EK1
(5,  2, 1), (6,  2, 2), (7,  2, 3), (8,  2, 4);   -- 1EK2

-- Sem 3: all three classes share same 4 core subjects
INSERT INTO class_subjects (id, class_id, subject_id) VALUES
(9,  3, 5), (10, 3, 6), (11, 3, 7), (12, 3, 8),   -- 3EK1
(13, 4, 5), (14, 4, 6), (15, 4, 7), (16, 4, 8),   -- 3EK2
(17, 5, 5), (18, 5, 6), (19, 5, 7), (20, 5, 8);   -- 3EK3

-- Sem 5: 4 core + 2 electives (electives mapped to 5EK1 as demo)
INSERT INTO class_subjects (id, class_id, subject_id) VALUES
(21, 6,  9), (22, 6, 10), (23, 6, 11), (24, 6, 12), -- 5EK1 core
(25, 6, 13), (26, 6, 14),                             -- 5EK1 electives
(27, 7,  9), (28, 7, 10), (29, 7, 11), (30, 7, 12), -- 5EK2 core
(31, 8,  9), (32, 8, 10), (33, 8, 11), (34, 8, 12); -- 5EK3 core

-- Sem 7: two classes share 4 core subjects
INSERT INTO class_subjects (id, class_id, subject_id) VALUES
(35, 9,  15), (36, 9,  16), (37, 9,  17), (38, 9,  18),  -- 7EK1
(39, 10, 15), (40, 10, 16), (41, 10, 17), (42, 10, 18);  -- 7EK2


-- ------------------------------------------------------------
-- 13. faculty_subjects  (who teaches which class_subject)
--     Faculty 2-11 are CCs; all 15 faculty teach across semesters
-- ------------------------------------------------------------
INSERT INTO faculty_subjects (faculty_id, class_subject_id) VALUES
-- Sem 1
(2,  1), (2,  2),                -- Dr. Asha  → 1EK1 BEE, EM
(3,  7), (3,  8),                -- Prof. Mehul → 1EK2 PPS, PHY
(4,  3), (4,  4),                -- Dr. Ritesh → 1EK1 PPS, PHY
(5,  5), (5,  6),                -- Prof. Nisha → 1EK2 BEE, EM
-- Sem 3
(6,  9), (6,  10),               -- Dr. Kiran → 3EK1 DBMS, OOP
(7,  13),(7,  14),               -- Prof. Bhavesh → 3EK2 DBMS, OOP
(8,  11),(8,  12),               -- Dr. Pooja → 3EK1 SNS, PAS
(9,  17),(9,  18),               -- Prof. Harshil → 3EK3 DBMS, OOP
(12, 15),(12, 16),               -- Dr. Komal → 3EK2 SNS, PAS
(13, 19),(13, 20),               -- Prof. Dhruv → 3EK3 SNS, PAS
-- Sem 5
(3,  21),(3,  22),               -- Prof. Mehul → 5EK1 CN, SE
(4,  27),(4,  28),               -- Dr. Ritesh → 5EK2 CN, SE
(5,  23),(5,  24),               -- Prof. Nisha → 5EK1 TOC, Python
(6,  25),(6,  26),               -- Dr. Kiran → 5EK1 Electives
(10, 29),(10, 30),               -- Dr. Neha → 5EK3 CN, SE
-- Sem 7
(10, 35),(10, 36),               -- Dr. Neha → 7EK1 AI, CS
(11, 39),(11, 40),               -- Prof. Jignesh → 7EK2 AI, CS
(14, 37),(14, 38),               -- Dr. Hetal → 7EK1 IOT, BDA
(15, 41),(15, 42);               -- Prof. Manan → 7EK2 IOT, BDA


-- ============================================================
-- DOMAIN 7: PRODUCTIVITY
-- ============================================================

-- ------------------------------------------------------------
-- 14. task_categories
-- ------------------------------------------------------------
INSERT INTO task_categories (id, user_id, name) VALUES
(1, 100, 'College Work'),
(2, 100, 'Project'),
(3, 101, 'College Work');

-- ------------------------------------------------------------
-- 15. task_priorities
-- ------------------------------------------------------------
INSERT INTO task_priorities (id, user_id, name, color, sort_order) VALUES
(1, 100, 'High',   '#ff0000', 1),
(2, 100, 'Medium', '#ffaa00', 2),
(3, 100, 'Low',    '#00aa00', 3),
(4, 101, 'High',   '#ff0000', 1),
(5, 101, 'Medium', '#ffaa00', 2);

-- ------------------------------------------------------------
-- 16. tasks
-- ------------------------------------------------------------
INSERT INTO tasks (id, user_id, title, description, category_id, priority_id, status, deadline) VALUES
(1, 100, 'Complete PHP module',  'Finish all PHP exercises from the lab sheet', 1, 1, 'todo',        '2026-06-10 18:00:00'),
(2, 101, 'Prepare DBMS notes',   'Summarise normalisation chapter',              3, 4, 'in_progress', '2026-06-12 18:00:00');


-- ============================================================
-- DOMAIN 5: FACULTY FEEDBACK
-- ============================================================

-- ------------------------------------------------------------
-- 17. feedback_forms  (one active form for Dr. Asha on 1EK1-BEE)
-- ------------------------------------------------------------
INSERT INTO feedback_forms (id, faculty_id, class_subject_id, title, status) VALUES
(1, 2, 1, 'Sem 1 – BEE Teaching Feedback', 'active');

-- ------------------------------------------------------------
-- 18. feedback_questions
-- ------------------------------------------------------------
INSERT INTO feedback_questions (form_id, question_text, question_type) VALUES
(1, 'How clear are the instructor explanations?',  'rating'),
(1, 'Is the course material up to date?',          'rating'),
(1, 'Overall satisfaction with this subject?',     'rating');


-- ============================================================
-- DOMAIN 10: COMMUNITY
-- ============================================================

-- ------------------------------------------------------------
-- 19. review_requests
-- ------------------------------------------------------------
INSERT INTO review_requests (id, user_id, skill, status, reviewer_id) VALUES
(1, 100, 'PHP Development',  'pending',   NULL),
(2, 101, 'Python Scripting', 'accepted',  17);

-- ------------------------------------------------------------
-- 20. reviews
-- ------------------------------------------------------------
INSERT INTO reviews (request_id, reviewer_id, marks, comment) VALUES
(2, 17, 85, 'Excellent understanding of Python basics.');


-- ============================================================
-- DOMAIN 11: GAMIFICATION
-- ============================================================

-- ------------------------------------------------------------
-- 21. badges
-- ------------------------------------------------------------
INSERT INTO badges (id, name, description, icon) VALUES
(1, 'First Login',       'Awarded on first login',              'fa-star'),
(2, 'Task Master',       'Completed 10 tasks',                  'fa-check-double'),
(3, 'Feedback Hero',     'Submitted feedback for all subjects',  'fa-comments'),
(4, 'Community Pillar',  'Completed 5 community reviews',        'fa-users'),
(5, 'Perfect Score',     'Received full marks in a review',      'fa-trophy');

-- ------------------------------------------------------------
-- 22. user_badges  (sample awards)
-- ------------------------------------------------------------
INSERT INTO user_badges (user_id, badge_id, awarded_by) VALUES
(100, 1, NULL),   -- student 100 gets "First Login" (system-awarded)
(101, 1, NULL),   -- student 101 gets "First Login"
(17,  4, NULL);   -- expert 17 gets "Community Pillar"
