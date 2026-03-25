# Chapter One: System Overview

## 1.1 Introduction
The Online Student Clearance Management System is designed for Kakamega High School (or any similar academic institution) to digitize and simplify the student clearance process. In traditional settings, clearance is handled manually through paper files and manual verification across multiple departments such as Library, Finance, Laboratory, Examinations, and Discipline.

This project provides a structured online workflow that enables students to submit clearance requests, enables department staff to approve or reject clearance, and ensures that students are cleared only when their financial status meets institutional requirements.

## 1.2 Problem Statement
Manual clearance management can lead to:
- Delays in approvals across departments.
- Errors in tracking the clearance state of students.
- Difficulty in verifying whether a student has settled required fees before clearance.
- Lack of automatic notifications to students after clearance completion.
- Limited feedback collection to improve the clearance experience.

## 1.3 Objectives
The system implements the following UPDATED specific objectives:
- Department-level dashboard to display clearance progress across all departments.
- Automatic financial validation before clearance approval.
- Automated email alert after a student is fully cleared.
- Student feedback platform including rating (1-5) and comments, and admin analytics.

## 1.4 Scope
The scope of the system includes:
- Authentication and authorization for Admin, Department Staff, and Students.
- Clearance request creation and department-level status tracking.
- Financial validation gate integrated into the approval flow.
- Email notifications through SMTP when a student is fully cleared.
- Feedback submission after full clearance and admin feedback analytics.

## 1.5 System Users and Roles
1. Admin
   - Manages departments and department staff
   - Creates student accounts and sets financial records
   - Views system-wide department progress dashboard
   - Views feedback analytics and recent comments
2. Department Staff
   - Views students in their assigned department
   - Updates their department clearance decision (Approved/Rejected)
   - Approval is automatically blocked when financial conditions are not met
3. Student
   - Registers and logs in
   - Submits clearance requests
   - Views clearance progress per department
   - Receives email upon successful clearance
   - Submits feedback after clearance is fully approved

## 1.6 Key Modules
- Student Module
- Admin Module
- Department Dashboard Module
- Financial Validation Module
- Email Notification Module
- Feedback Platform Module

