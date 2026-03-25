<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class DepartmentModel
{
  public static function getAllDepartments(): array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->query('SELECT department_id, name, sort_order FROM departments ORDER BY sort_order ASC, name ASC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getDepartmentIdByStaffUserId(int $userId): ?int
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT department_id
      FROM department_staff
      WHERE user_id = ?
      LIMIT 1
    ');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['department_id'] : null;
  }

  public static function getDepartmentName(int $departmentId): ?string
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT name FROM departments WHERE department_id = ? LIMIT 1');
    $stmt->execute([$departmentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (string)$row['name'] : null;
  }
}

