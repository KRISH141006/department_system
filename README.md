# Department Management System

A comprehensive web application for managing academic activities, student productivity, and community-driven skill reviews within a department.

## Features

- **Academics Module**: 
  - Manage faculty subjects, units, and topics.
  - Track lecture progress.
  - Faculty and lecture feedback systems.
- **Productivity Module**: 
  - Personal task management for users.
- **Community Module**: 
  - User profiles with skill sets.
  - Peer-to-peer skill review requests and mark-based evaluations.
- **Authentication**: 
  - Role-based access control (Student, Faculty, Alumni, Senior, HOD, Creator).
  - OTP-based verification (via PHPMailer).

## Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript
- **Libraries**:
  - `phpmailer/phpmailer`: For sending emails/OTPs.
  - `vlucas/phpdotenv`: For environment variable management.

## Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL/MariaDB
- Composer
- XAMPP / WAMP / MAMP (or standalone Apache/Nginx)

### Setup Steps

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd department_system
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment Variables**:
   - Copy `.env.example` to `.env`:
     ```bash
     cp .env.example .env
     ```
   - Update the `.env` file with your database credentials and SMTP settings for PHPMailer.

4. **Database Setup**:
   - Create a database named `department_system`.
   - Import the SQL schema from `sql/department_system.sql`.

5. **Run the Application**:
   - Move the project to your web server's root directory (e.g., `htdocs` for XAMPP).
   - Access the application via `http://localhost/department_system/public/login.php`.

## Project Structure

- `app/`: Core logic, configuration, and middleware.
- `modules/`: Feature-specific logic.
- `public/`: Web-accessible entry points and assets.
- `sql/`: Database migration scripts.

## License

MIT License
