<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class UserModel
{
  public static function getRoleIdByName(string $roleName): int
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT role_id FROM roles WHERE role_name = ? LIMIT 1');
    $stmt->execute([$roleName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      throw new \RuntimeException('Role not found.');
    }
    return (int)$row['role_id'];
  }

  public static function findByEmail(string $email): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT u.user_id, u.role_id, u.email, u.password_hash, u.is_active, r.role_name
      FROM users u
      JOIN roles r ON r.role_id = u.role_id
      WHERE u.email = ?
      LIMIT 1
    ');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function createUser(string $roleName, string $email, string $passwordHash): int
  {
    $pdo = Database::pdo();
    $roleId = self::getRoleIdByName($roleName);

    $stmt = $pdo->prepare('
      INSERT INTO users (role_id, email, password_hash, is_active)
      VALUES (?, ?, ?, 1)
    ');
    $stmt->execute([$roleId, $email, $passwordHash]);
    return (int)$pdo->lastInsertId();
  }

  public static function getUserAuth(int $userId): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT u.user_id, r.role_name
      FROM users u
      JOIN roles r ON r.role_id = u.role_id
      WHERE u.user_id = ? AND u.is_active = 1
      LIMIT 1
    ');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }
}

