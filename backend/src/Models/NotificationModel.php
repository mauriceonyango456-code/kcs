<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class NotificationModel
{
  public static function isSent(int $studentId, int $requestId, string $type): bool
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT COUNT(*) AS cnt
      FROM notifications
      WHERE student_id = ? AND request_id = ? AND type = ?
      LIMIT 1
    ');
    $stmt->execute([$studentId, $requestId, $type]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ((int)$row['cnt']) > 0;
  }

  public static function logSent(int $studentId, int $requestId, string $type, ?string $emailTo, ?string $message, ?string $sentAt = null): void
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      INSERT INTO notifications (student_id, request_id, type, email_to, message, sent_at)
      VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$studentId, $requestId, $type, $emailTo, $message, $sentAt]);
  }

  public static function getStudentEmail(int $studentId): ?string
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT u.email
      FROM students s
      JOIN users u ON u.user_id = s.user_id
      WHERE s.student_id = ?
      LIMIT 1
    ');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (string)$row['email'] : null;
  }
}

