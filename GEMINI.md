# Department System Development Rules

This project is under active development.

The database schema, roles, modules, and workflows may evolve frequently during development.

The AI assistant must ALWAYS preserve:

* working functionality
* database integrity
* module compatibility
* development usability
* role consistency

---

# Project Overview

Project Name:
Department Community & Academic Management System

Modules:

* Academics
* Productivity
* Community Validation

Architecture:

* Procedural PHP
* MySQL
* Shared authentication
* Shared database
* Modular structure

---

# Role System Rules

ONLY these 4 roles are allowed in the system:

* student
* faculty
* expert
* admin

Deprecated roles:

* hod
* cc
* reviewer
* senior
* industry_expert

These old roles must NEVER be reintroduced.

Old role logic must always be mapped safely into the new role system.

---

# Role Definitions

## student

Student can:

* manage tasks
* access productivity module
* submit lecture feedback
* submit faculty feedback
* create validation requests
* manage own profile
* view own reviews/results

Student cannot:

* manage users
* create subjects
* review requests
* access admin controls

---

## faculty

Faculty can:

* create/manage subjects
* create/manage units/topics
* create feedback forms
* manage lecture tracking
* access reports
* manage academic content
* review student requests (Community Module)

Faculty cannot:

* manage system users
* add experts
* access admin controls

There is NO separate HOD role anymore.

---

## expert

Expert can:

* access reviewer dashboard
* review requests
* accept/reject requests
* submit reviews/comments/scores

Expert cannot:

* manage users
* manage academic structure
* access admin controls

Old reviewer/senior/industry_expert logic should always map to:
expert

---

## admin

Admin has full platform access.

Admin can:

* manage all modules
* manage users
* manage faculty
* manage experts
* manage students
* manage permissions
* access all dashboards
* monitor all systems
* manage academics
* manage community system

Admin is NOT automatically faculty.

Faculty and Admin are separate accounts.

---

# Database Rules

Whenever modifying:

* tables
* columns
* relationships
* foreign keys
* constraints
* enums
* indexes

the AI MUST also update:

* sql/department_system.sql
* seed data
* INSERT queries
* foreign key references
* role-compatible test data

---

# Database Workflow

Whenever schema changes occur:

## STEP 1 — Drop Existing Database

```sql
DROP DATABASE IF EXISTS department_system;
```

---

## STEP 2 — Recreate Database

```sql
CREATE DATABASE department_system;
USE department_system;
```

---

## STEP 3 — Rebuild Full Schema

Rebuild:

* all tables
* all foreign keys
* all indexes
* all constraints
* all enums

inside:

sql/department_system.sql

The SQL file must always represent the FULL latest database state.

---

## STEP 4 — Regenerate Seed Data

Always regenerate:

* users
* profiles
* subjects
* units
* topics
* tasks
* feedback
* requests
* reviews

Seed data must remain fully compatible with:

* foreign keys
* role system
* dashboards
* middleware
* authentication

---

# Database Role Rules

The users table role ENUM must ONLY contain:

```sql
ENUM('student','faculty','expert','admin')
```

Never reintroduce old roles.

---

# Development Accounts

Always maintain these development accounts:

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

Password for ALL accounts:
1234

Passwords must ALWAYS remain hashed using:

```php
password_hash("1234", PASSWORD_DEFAULT)
```

Never store plaintext passwords.

---

# Development Workflow Goal

The project should ALWAYS support:

Clone repo
↓
Import sql/department_system.sql
↓
Run project
↓
Login instantly with development accounts
↓
Start development immediately

No manual setup should be required.

---

# Authentication Rules

During development:

* OTP may be bypassed for seed accounts
* real email verification is NOT required for local testing
* .env credentials must NEVER be committed

Environment variables must remain inside:
.env

---

# Important Development Rules

## DO NOT

* leave outdated seed data
* leave broken foreign keys
* create partial schemas
* leave invalid relationships
* hardcode production credentials
* require manual signup for development
* reintroduce deprecated roles

---

# ALWAYS ENSURE

After schema changes:

* SQL imports successfully
* seed data works
* dashboards remain accessible
* middleware remains compatible
* all modules remain testable
* all test accounts can login
* no foreign key issues exist
* no table creation errors exist

The system must ALWAYS remain runnable from scratch using:

sql/department_system.sql

---

# PAC Category & Syllabus Verification Rules

* **PAC Category**: Students must have a `pac_category` assigned `ENUM('premium', 'average', 'challenged')` in the `users` table. This drives randomized selection.
* **Anonymous Verification**: Syllabus topic verifications (`topic_progress`) must remain strictly anonymous. Do not track `updated_by`. Instead, track `verification_count`.
* **5-Student Random Selection**: Faculty must use randomized bulk assignment. It assigns exactly 5 students per review session based on the PAC ratio (2 Premium, 2 Average, 1 Challenged). The assigned students must be hidden from faculty to ensure complete anonymity.
* **Absent Override**: Students selected for verification must have the option to mark themselves absent and skip the review queue.