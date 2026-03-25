# Chapter Two: Database Design

## 2.1 Introduction
This chapter presents the database design for the Online Student Clearance Management System. The database is implemented in MySQL 8+ using relational tables with foreign key constraints.

## 2.2 ER Diagram (Text Representation)
The relationship structure can be summarized as:

1. `roles` -> `users`
   - A role is assigned to each user.
2. `users` -> `students`
   - A user account may represent a student.
3. `users` -> `department_staff` -> `departments`
   - A department staff user is mapped to exactly one department.
4. `students` -> `financial_records`
   - A student has financial records; `is_current = 1` is the active record used for validation.
5. `students` -> `clearance_requests`
   - A student submits one or more clearance requests.
6. `clearance_requests` -> `clearance_status` -> `departments`
   - Each request stores a per-department decision (Pending/Approved/Rejected).
7. `students` -> `feedback` -> `clearance_requests`
   - Feedback is submitted for a specific request after clearance is cleared.
8. `students` -> `notifications` (email log)
   - Email notifications are logged to ensure the email is sent once per request.

## 2.3 Database Tables
The database script is located at:
- `database/kcs_clearance.sql`

Main tables:
- `users` (authentication)
- `students` (student profile linked to user account)
- `departments` (department list)
- `clearance_requests` (student request instances)
- `clearance_status` (per-request, per-department status)
- `financial_records` (fee amount, paid, and balance; current record flagged)
- `feedback` (rating and comment)
- `notifications` (record of email notifications)

## 2.4 SQL Script
The full MySQL schema is implemented in `database/kcs_clearance.sql`.

During system evaluation, the departments and roles are seeded automatically, while user creation is done via the application UI or the one-time setup script for the initial admin account.

## 2.5 Design Rationale
- Using `clearance_requests` allows students to submit new requests over time.
- Using `clearance_status` stores department decisions per request.
- Using `financial_records` with `is_current` makes validation consistent and quick.
- Storing `notifications` prevents duplicate email messages for the same request.

