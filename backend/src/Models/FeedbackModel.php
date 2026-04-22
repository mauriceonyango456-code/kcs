<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class FeedbackModel
{
  public static function hasFeedbackForRequest(int $requestId): bool
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM feedback WHERE request_id = ? LIMIT 1');
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ((int)$row['cnt']) > 0;
  }

  public static function submitFeedback(int $studentId, int $requestId, int $rating, ?string $comment): void
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      INSERT INTO feedback (student_id, request_id, rating, comment)
      VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$studentId, $requestId, $rating, $comment]);
  }

  public static function getAdminAnalytics(int $limitComments = 20): array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->query('
      SELECT
        AVG(rating) AS avg_rating,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS rating_1_cnt,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS rating_2_cnt,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS rating_3_cnt,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS rating_4_cnt,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS rating_5_cnt,
        COUNT(*) AS total_cnt
      FROM feedback
    ');
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->query('
      SELECT f.rating, f.comment, f.created_at, s.full_name, s.admission_number
      FROM feedback f
      JOIN students s ON s.student_id = f.student_id
      ORDER BY f.created_at DESC
      LIMIT ' . (int)$limitComments . '
    ');
    $comments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    return [
      'summary' => $summary,
      'comments' => $comments,
    ];
  }
}

