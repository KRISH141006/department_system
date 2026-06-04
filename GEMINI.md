# Department Community & Academic Management System

## Project Purpose

A modular academic and community management platform for departments.

Primary domains:

* Identity & Access
* Academic Structure
* Academic Enrollment
* Elective Workflow
* Faculty Feedback
* Syllabus Verification
* Productivity
* Assignment Management
* Live Classes
* Community Validation
* Gamification

Technology Stack:

* PHP (Procedural)
* MySQL
* Shared Authentication
* Shared Database
* Modular Architecture

---

# Core Development Principles

When making any change:

1. Preserve existing functionality.
2. Preserve database integrity.
3. Preserve role compatibility.
4. Preserve module compatibility.
5. Preserve development usability.
6. Prefer normalization over duplication.
7. Prefer foreign keys over name-based relationships.
8. Avoid introducing breaking changes unless explicitly requested.

---

# Current Database Architecture

The project uses a domain-driven database design.

## Identity & Access

Tables:

* users
* profiles
* students
* faculty
* experts
* permissions
* role_permissions

## Academic Structure

Tables:

* classes
* subjects
* units
* topics
* class_subjects

## Academic Enrollment

Tables:

* faculty_subjects
* student_subjects

## Elective Workflow

Tables:

* elective_windows
* elective_change_requests

## Faculty Feedback

Tables:

* feedback_forms
* feedback_questions
* feedback_responses

## Syllabus Verification

Tables:

* lecture_records
* verification_assignments
* lecture_verifications

## Productivity

Tables:

* tasks
* task_categories
* task_priorities

## Assignment Management

Tables:

* assignments
* submissions

## Live Classes

Tables:

* live_sessions

## Community Validation

Tables:

* review_requests
* reviews

## Gamification

Tables:

* badges
* user_badges

---

# Role System

Only these roles exist:

```text
student
faculty
expert
admin
```

Deprecated roles:

```text
hod
cc
reviewer
senior
industry_expert
```

These roles must never be reintroduced.

Any legacy logic must be mapped into:

```text
student
faculty
expert
admin
```

---

# Role Responsibilities

## Student

Can:

* Manage tasks
* Submit faculty feedback
* Participate in syllabus verification
* Create community review requests
* Manage own profile
* View own results

Cannot:

* Manage users
* Manage academic structure
* Access admin features

---

## Faculty

Can:

* Manage subjects
* Manage units
* Manage topics
* Create feedback forms
* Manage assignments
* Manage syllabus tracking
* Access academic reports
* Review community requests

Cannot:

* Manage users
* Manage experts
* Access admin controls

---

## Expert

Can:

* Review requests
* Accept requests
* Reject requests
* Submit evaluations
* Submit scores
* Submit comments

Cannot:

* Manage academic structure
* Manage users
* Access admin controls

---

## Admin

Full system access.

Admin is not automatically faculty.

Admin and faculty accounts are separate.

---

# Database Rules

## Source of Truth

The following file must always represent the complete latest database:

```text
sql/department_system.sql
```

No partial schemas.

No migration-only state.

No outdated SQL.

---

## Schema Modification Rules

Whenever modifying:

* tables
* columns
* foreign keys
* constraints
* indexes
* enums

also update:

* schema SQL
* seed data
* inserts
* foreign keys
* test data
* role permissions

---

## Normalization Rules

Avoid:

* duplicated semester values
* duplicated branch values
* duplicated class information
* subject-name relationships
* topic-name relationships

Prefer:

* foreign keys
* junction tables
* normalized structures

Example:

Use:

```text
class_subjects
```

instead of inferring curriculum from faculty assignments.

---

# Academic Rules

## Subjects

Subjects belong to classes through:

```text
class_subjects
```

not directly through faculty assignments.

---

## Electives

Electives are not separate entities.

Use:

```text
subjects.type
```

Values:

```text
core
elective
```

Elective selection is handled through:

```text
student_subjects
elective_windows
elective_change_requests
```

---

# Syllabus Verification Rules

Verification must remain anonymous.

Faculty must not know which students verified a lecture.

Do not track:

```text
updated_by
```

Track:

```text
verification_count
```

where needed.

---

## Random Verification Selection

Each verification cycle:

* 2 Premium students
* 2 Average students
* 1 Challenged student

Total:

```text
5 students
```

Selected students remain hidden from faculty.

---

## Absent Override

Selected students may mark themselves absent.

Absent students are skipped from verification.

---

# Development Accounts

Always maintain these accounts:

| Role    | Email                                       |
| ------- | ------------------------------------------- |
| admin   | [admin@ict.com](mailto:admin@ict.com)       |
| faculty | [faculty1@ict.com](mailto:faculty1@ict.com) |
| faculty | [faculty2@ict.com](mailto:faculty2@ict.com) |
| student | [student1@ict.com](mailto:student1@ict.com) |
| student | [student2@ict.com](mailto:student2@ict.com) |
| student | [student3@ict.com](mailto:student3@ict.com) |
| expert  | [expert1@ict.com](mailto:expert1@ict.com)   |
| expert  | [expert2@ict.com](mailto:expert2@ict.com)   |

Password:

```text
1234
```

Passwords must always be stored using:

```php
password_hash("1234", PASSWORD_DEFAULT)
```

Never store plaintext passwords.

---

# Local Development Requirements

A developer must be able to:

```text
Clone Repository
↓
Import department_system.sql
↓
Run Project
↓
Login Immediately
↓
Begin Development
```

No manual configuration.

No manual user creation.

No manual seed generation.

The project must remain runnable from scratch at all times.
