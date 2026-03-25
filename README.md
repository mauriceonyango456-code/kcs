# Online Student Clearance Management System (Kakamega High School)

This is a final-year academic project implementing:
- Student login/register, clearance requests, and clearance status tracking
- Department-level approval dashboard (with automatic financial validation gate)
- Admin system-wide department progress dashboard
- Automatic email alert when a student is fully cleared (SMTP)
- Student feedback platform (rating 1–5 + comments) and admin feedback analytics

## Tech Stack
- Backend: PHP (PDO, sessions, password hashing)
- Frontend: HTML, Bootstrap, JavaScript, Chart.js
- Database: MySQL 8+

## 1) Database Setup
1. Create a MySQL database by running:
   - `database/kcs_clearance.sql`
2. Make sure your MySQL user has permission to create/access database `kcs_clearance`.

## 2) Backend Configuration
Edit `backend/config/config.php`:
- `db` settings
- `smtp` settings:
  - Gmail requires an **App Password** (not your normal account password)

## 3) Create the Initial Admin Account
Because students can self-register but admins cannot yet be created via UI, run the one-time script:
1. Start your PHP server (examples below)
2. Open:
   - `/setup_admin.php?run=1`
3. Then login as the admin and create `department_staff` accounts.

Setup credentials are in `backend/config/config.php` under `setup`.
After setup, delete/lock `backend/public/setup_admin.php`.

## 4) Run the System
From the project root (`kcs`), start PHP’s built-in server pointing to `backend/public`:

Example:
```bash
php -S localhost:8000 -t backend/public
```

Then open:
- Student/Student registration: `http://localhost:8000/pages/login.html`
- Admin/Department dashboards after login are redirected automatically.

## API Endpoints (high-level)
- Auth: `POST /api/auth/register`, `POST /api/auth/login`, `GET /api/auth/me`
- Student clearance:
  - `POST /api/student/request-clearance`
  - `GET /api/student/clearance-status`
- Department staff decision:
  - `POST /api/department/decision`
  - `GET /api/department/students-progress`
- Admin:
  - `GET /api/admin/dashboard-progress`
  - `GET /api/admin/feedback-analytics`
- Feedback:
  - `POST /api/feedback/submit`

## Updated Objectives Coverage
1. Department-level dashboard across departments: `admin-dashboard.html` + `/api/admin/dashboard-progress`
2. Financial validation gate: implemented in `src/Services/ClearanceService.php` before setting `Approved`
3. Automated email on full clearance: implemented in `src/Services/ClearanceService.php` + `src/Services/EmailService.php`
4. Feedback platform: `frontend/pages/feedback.html` + admin analytics `/api/admin/feedback-analytics`

