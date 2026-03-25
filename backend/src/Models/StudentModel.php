<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class StudentModel
{
  public static function createStudent(int $userId, string $fullName, string $admissionNumber, ?string $className): int
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      INSERT INTO students (user_id, full_name, admission_number, class_name)
      VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $fullName, $admissionNumber, $className]);
    return (int)$pdo->lastInsertId();
  }

  public static function getByUserId(int $userId): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT s.*
      FROM students s
      WHERE s.user_id = ?
      LIMIT 1
    ');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function getLatestRequest(int $studentId): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT *
      FROM clearance_requests
      WHERE student_id = ?
      ORDER BY submitted_at DESC
      LIMIT 1
    ');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function getStudentName(int $studentId): ?string
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT full_name FROM students WHERE student_id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (string)$row['full_name'] : null;
  }

  public static function getByAdmissionNumber(string $admissionNumber): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT *
      FROM students
      WHERE admission_number = ?
      LIMIT 1
    ');
    $stmt->execute([$admissionNumber]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }
}

