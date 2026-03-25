# Chapter Four: Frontend and Integration

## 4.1 Introduction
The frontend is implemented using HTML, CSS, JavaScript, Bootstrap, and Chart.js for visualization. The UI communicates with the PHP backend through JSON-based API endpoints.

Frontend folder:
- `backend/public/pages/*`
- `backend/public/assets/*`

## 4.2 UI Pages (Sample)
1. `pages/login.html`
   - Student registration (login/register)
   - Student login with automatic redirect based on role
2. `pages/student-clearance.html`
   - Student clearance request submission
   - Clearance status view by department
   - Progress indicators (approved/pending/rejected)
3. `pages/feedback.html`
   - Student feedback submission (rating 1-5 + comment)
   - Enabled only after clearance status is `Cleared`
4. `pages/dept-dashboard.html`
   - Department staff dashboard for their department
   - Visual chart of Pending/Approved/Rejected
   - Decision forms for updating department status
   - Automatic messaging after submission (including financial validation denials)
5. `pages/admin-dashboard.html`
   - Department-level dashboard across all departments (UPDATED objective #1)
   - Chart.js visualization for Pending/Approved/Rejected by department
   - Feedback analytics panel (average rating + recent comments)
6. `pages/admin-management.html`
   - Admin management for departments, department staff, students, and financial records

## 4.3 Chart.js Integration
The system uses Chart.js to create:
- Department-level status charts on `dept-dashboard.html`
- System-wide department progress charts on `admin-dashboard.html`

These charts are driven by API responses:
- `/api/department/students-progress`
- `/api/admin/dashboard-progress`

## 4.4 Integration: Email Notification and Feedback
Email notification integration:
- After a department staff decision triggers a request outcome of `Cleared`, the backend calls SMTP sending logic.
- The email process is implemented in:
  - `backend/src/Services/ClearanceService.php`
  - `backend/src/Services/EmailService.php`
- Email is logged in the `notifications` table to avoid duplicates.

Feedback integration:
- Feedback is submitted only when `clearance_requests.overall_status = Cleared`.
- Feedback submissions are stored in `feedback` table.
- Admin reads analytics via:
  - `/api/admin/feedback-analytics`

