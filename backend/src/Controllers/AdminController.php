<?php
declare(strict_types=1);

namespace KCS\Controllers;

use KCS\Core\Auth as AuthCore;
use KCS\Core\Csrf;
use KCS\Core\Database;
use KCS\Core\Response;
use KCS\Models\DepartmentModel;
use KCS\Models\FinancialModel;
use KCS\Models\StudentModel;
use KCS\Models\UserModel;

class AdminController
{
  private static function inputJson(): array
  {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  public static function listDepartments(): void
  {
    AuthCore::requireRole(['admin']);
    $data = DepartmentModel::getAllDepartments();
    Response::json(['ok' => true, 'departments' => $data]);
  }

  public static function createDepartment(): void
  {
    AuthCore::requireRole(['admin']);
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    $input = self::inputJson();
    $name = trim((string)($input['name'] ?? ''));
    $sortOrder = (int)($input['sort_order'] ?? 0);

    if ($name === '') {
      Response::json(['ok' => false, 'error' => 'Department name is required'], 422);
    }

    $pdo = Database::pdo();
    $stmt = $pdo->prepare('INSERT INTO departments (name, sort_order) VALUES (?, ?)');
    $stmt->execute([$name, $sortOrder]);
    Response::json(['ok' => true]);
  }

  public static function createStudent(): void
  {
    AuthCore::requireRole(['admin']);
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    $input = self::inputJson();
    $fullName = trim((string)($input['full_name'] ?? ''));
    $admissionNumber = trim((string)($input['admission_number'] ?? ''));
    $className = isset($input['class_name']) ? (string)$input['class_name'] : null;
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');

    $feeAmount = (float)($input['fee_amount'] ?? 0);
    $amountPaid = (float)($input['amount_paid'] ?? 0);
    $balance = (float)($input['balance'] ?? ($feeAmount - $amountPaid));
    $academicYear = (string)($input['academic_year'] ?? '2025/2026');
    $termName = (string)($input['term_name'] ?? 'Term 1');

    if ($fullName === '' || $admissionNumber === '' || $email === '' || $password === '') {
      Response::json(['ok' => false, 'error' => 'Missing required fields'], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Response::json(['ok' => false, 'error' => 'Invalid email'], 422);
    }

    if (UserModel::findByEmail($email)) {
      Response::json(['ok' => false, 'error' => 'Email already registered'], 409);
    }

    if (StudentModel::getByAdmissionNumber($admissionNumber)) {
      Response::json(['ok' => false, 'error' => 'Admission number already exists'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $userId = UserModel::createUser('student', $email, $hash);
    $studentId = StudentModel::createStudent($userId, $fullName, $admissionNumber, $className);

    FinancialModel::setCurrentFinancialRecord(
      $studentId,
      $feeAmount,
      $amountPaid,
      $balance,
      $academicYear,
      $termName
    );

    Response::json(['ok' => true, 'student_id' => $studentId]);
  }

  public static function setCurrentFinancialRecord(): void
  {
    AuthCore::requireRole(['admin']);
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    $input = self::inputJson();
    $admissionNumber = trim((string)($input['admission_number'] ?? ''));
    if ($admissionNumber === '') {
      Response::json(['ok' => false, 'error' => 'Admission number is required'], 422);
    }

    $student = StudentModel::getByAdmissionNumber($admissionNumber);
    if (!$student) {
      Response::json(['ok' => false, 'error' => 'Student not found'], 404);
    }

    $feeAmount = (float)($input['fee_amount'] ?? 0);
    $amountPaid = (float)($input['amount_paid'] ?? 0);
    $balance = (float)($input['balance'] ?? ($feeAmount - $amountPaid));
    $academicYear = (string)($input['academic_year'] ?? '2025/2026');
    $termName = (string)($input['term_name'] ?? 'Term 1');

    FinancialModel::setCurrentFinancialRecord(
      (int)$student['student_id'],
      $feeAmount,
      $amountPaid,
      $balance,
      $academicYear,
      $termName
    );

    Response::json(['ok' => true]);
  }

  public static function createDepartmentStaff(): void
  {
    $auth = AuthCore::requireRole(['admin']);
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    $input = self::inputJson();
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    $departmentId = (int)($input['department_id'] ?? 0);

    if ($email === '' || $password === '' || $departmentId <= 0) {
      Response::json(['ok' => false, 'error' => 'Invalid payload'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Response::json(['ok' => false, 'error' => 'Invalid email'], 422);
    }

    if (UserModel::findByEmail($email)) {
      Response::json(['ok' => false, 'error' => 'Email already registered'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $userId = UserModel::createUser('department_staff', $email, $hash);

    $pdo = Database::pdo();
    $stmt = $pdo->prepare('INSERT INTO department_staff (user_id, department_id) VALUES (?, ?)');
    $stmt->execute([$userId, $departmentId]);

    Response::json(['ok' => true]);
  }
}

