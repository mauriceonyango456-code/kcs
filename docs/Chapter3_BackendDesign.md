# Chapter Three: Backend Design

## 3.1 Introduction
The backend is implemented in PHP using PDO for database access and PHP sessions for authentication.

The backend code is structured into:
- `src/Core` for routing, database connection, and session helpers.
- `src/Controllers` for API endpoints.
- `src/Models` for database operations.
- `src/Services` for business logic (clearance workflow + email).

The API entry point is:
- `backend/public/index.php`

## 3.2 System Architecture (Text Diagram)
Client (Browser)
  -> calls -> PHP API endpoints
      -> controllers -> models -> MySQL
      -> services (Clearance workflow)
          -> financial validation gate
          -> update clearance status + overall request outcome
          -> email notification service via SMTP
          -> logs to notifications table

## 3.3 Authentication & Authorization
- Passwords are hashed using `password_hash(..., PASSWORD_BCRYPT)` during registration.
- Login uses `password_verify`.
- Role checks are performed in each API controller using session `role_name`.

## 3.4 Clearance Workflow Logic
### Department decision flow
When a department staff user updates the clearance status to `Approved` or `Rejected`:
1. The backend validates the request is still `InProgress`.
2. If the requested decision is `Approved`, the system automatically validates finances:
   - If `financial_records.balance > 0`, approval is denied and the status becomes `Rejected`.
3. The backend recalculates the overall request outcome:
   - Cleared if all departments are Approved.
   - Rejected if at least one department is Rejected.

### Financial validation module (UPDATED objective #2)
Implemented in:
- `backend/src/Services/ClearanceService.php`

Key rule:
- If fee balance > 0 (or no current financial record), the approval is denied.

### Automated email alert (UPDATED objective #3)
Implemented in:
- `backend/src/Services/ClearanceService.php`
- `backend/src/Services/EmailService.php`

When the request becomes `Cleared`:
- The system sends an email to the student via SMTP.
- Email sending is done only once per request by checking the `notifications` table.

## 3.5 API Endpoints (Implemented)
High-level API routes (from `backend/public/index.php`):
- Auth:
  - `POST /api/auth/register`
  - `POST /api/auth/login`
  - `GET /api/auth/me`
  - `GET /api/auth/csrf`
  - `POST /api/auth/logout`
- Student:
  - `POST /api/student/request-clearance`
  - `GET /api/student/clearance-status`
- Department staff:
  - `POST /api/department/decision`
  - `GET /api/department/students-progress`
- Admin:
  - `GET /api/admin/dashboard-progress` (department-level dashboard across all departments)
  - `GET /api/admin/feedback-analytics`
  - `POST /api/admin/students/create` (manage students)
  - `POST /api/admin/departments/create` (manage departments)
  - `POST /api/admin/create-department-staff` (manage department staff)
  - `POST /api/admin/financial/set-current` (manage financial records)
- Feedback:
  - `POST /api/feedback/submit`

