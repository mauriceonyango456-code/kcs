-- Online Student Clearance Management System (Kakamega High School)
-- Database schema for MySQL 8+
-- Notes:
-- 1) Run in MySQL CLI or Workbench.
-- 2) This script creates roles + departments seed data. It does NOT seed admin/staff users
--    because password hashes are environment-specific (PHP bcrypt). Create users via the UI/setup.

SET SQL_MODE = "STRICT_ALL_TABLES";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS kcs_clearance
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE kcs_clearance;

-- -----------------------------
-- Roles & Users
-- -----------------------------
CREATE TABLE IF NOT EXISTS roles (
  role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (role_name) VALUES
  ('admin'),
  ('department_staff'),
  ('student')
ON DUPLICATE KEY UPDATE role_name = role_name;

CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id INT UNSIGNED NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- -----------------------------
-- Students & Department Staff
-- -----------------------------
CREATE TABLE IF NOT EXISTS students (
  student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  admission_number VARCHAR(50) NOT NULL UNIQUE,
  class_name VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
  department_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO departments (name, sort_order) VALUES
  ('Library', 10),
  ('Finance', 20),
  ('Laboratory', 30),
  ('Examinations', 40),
  ('Discipline', 50)
ON DUPLICATE KEY UPDATE name = name;

CREATE TABLE IF NOT EXISTS department_staff (
  user_id INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_deptstaff_user FOREIGN KEY (user_id) REFERENCES users(user_id),
  CONSTRAINT fk_deptstaff_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

-- -----------------------------
-- Financial Records
-- -----------------------------
CREATE TABLE IF NOT EXISTS financial_records (
  financial_record_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
  term_name VARCHAR(50) NOT NULL DEFAULT 'Term 1',
  fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  is_current TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_financial_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT uq_financial_current UNIQUE (student_id, is_current)
) ENGINE=InnoDB;

-- -----------------------------
-- Clearance Requests & Status
-- -----------------------------
CREATE TABLE IF NOT EXISTS clearance_requests (
  request_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  overall_status ENUM('InProgress', 'Cleared', 'Rejected') NOT NULL DEFAULT 'InProgress',
  completed_at TIMESTAMP NULL DEFAULT NULL,
  completed_reason VARCHAR(255) NULL DEFAULT NULL,
  CONSTRAINT fk_clearance_request_student FOREIGN KEY (student_id) REFERENCES students(student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clearance_status (
  clearance_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  rejection_reason VARCHAR(255) NULL DEFAULT NULL,
  updated_by INT UNSIGNED NULL DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_clearance_status_request FOREIGN KEY (request_id) REFERENCES clearance_requests(request_id),
  CONSTRAINT fk_clearance_status_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT fk_clearance_status_department FOREIGN KEY (department_id) REFERENCES departments(department_id),
  CONSTRAINT uq_clearance_status_per_request UNIQUE (request_id, department_id),
  CONSTRAINT fk_clearance_status_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE INDEX idx_clearance_status_student ON clearance_status(student_id);
CREATE INDEX idx_clearance_status_request ON clearance_status(request_id);
CREATE INDEX idx_clearance_status_department ON clearance_status(department_id);

-- -----------------------------
-- Notifications (email log)
-- -----------------------------
CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  request_id INT UNSIGNED NULL,
  type VARCHAR(50) NOT NULL,
  email_to VARCHAR(150) NULL,
  message TEXT NULL,
  sent_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT fk_notifications_request FOREIGN KEY (request_id) REFERENCES clearance_requests(request_id),
  CONSTRAINT uq_notifications_unique UNIQUE (student_id, request_id, type)
) ENGINE=InnoDB;

-- -----------------------------
-- Feedback (post-clearance)
-- -----------------------------
CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  request_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_feedback_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT fk_feedback_request FOREIGN KEY (request_id) REFERENCES clearance_requests(request_id),
  CONSTRAINT chk_feedback_rating CHECK (rating >= 1 AND rating <= 5),
  CONSTRAINT uq_feedback_per_request UNIQUE (request_id)
) ENGINE=InnoDB;

-- -----------------------------
-- Minimal seed data for testing financial validation logic
-- (Students and users are created via UI; this seed only provides departments/roles.)
-- -----------------------------

-- Quick check query:
-- SELECT * FROM departments ORDER BY sort_order;

