<?php
declare(strict_types=1);

namespace KCS\Models;

use KCS\Core\Database;
use PDO;

class ClearanceModel
{
  public static function studentHasActiveRequest(int $studentId): bool
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT COUNT(*) AS cnt
      FROM clearance_requests
      WHERE student_id = ? AND overall_status = "InProgress"
    ');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ((int)$row['cnt']) > 0;
  }

  public static function createRequestAndStatuses(int $studentId, array $departments): int
  {
    $pdo = Database::pdo();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('
        INSERT INTO clearance_requests (student_id, overall_status)
        VALUES (?, "InProgress")
      ');
      $stmt->execute([$studentId]);
      $requestId = (int)$pdo->lastInsertId();

      $stmtStatus = $pdo->prepare('
        INSERT INTO clearance_status (request_id, student_id, department_id, status, updated_by)
        VALUES (?, ?, ?, "Pending", NULL)
      ');
      foreach ($departments as $dept) {
        $stmtStatus->execute([$requestId, $studentId, (int)$dept['department_id']]);
      }

      $pdo->commit();
      return $requestId;
    } catch (\Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  public static function getRequest(int $requestId): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT * FROM clearance_requests WHERE request_id = ? LIMIT 1');
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function getStatusesByRequest(int $requestId): array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT
        cs.department_id,
        d.name AS department_name,
        cs.status,
        cs.rejection_reason,
        cs.updated_at
      FROM clearance_status cs
      JOIN departments d ON d.department_id = cs.department_id
      WHERE cs.request_id = ?
      ORDER BY d.sort_order ASC, d.name ASC
    ');
    $stmt->execute([$requestId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getDepartmentStatus(int $requestId, int $departmentId): ?array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT *
      FROM clearance_status
      WHERE request_id = ? AND department_id = ?
      LIMIT 1
    ');
    $stmt->execute([$requestId, $departmentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function updateDepartmentStatus(
    int $requestId,
    int $departmentId,
    string $newStatus,
    ?string $reason,
    int $updatedByUserId
  ): void {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      UPDATE clearance_status
      SET status = ?,
          rejection_reason = ?,
          updated_by = ?,
          updated_at = CURRENT_TIMESTAMP
      WHERE request_id = ? AND department_id = ?
    ');
    $stmt->execute([$newStatus, $reason, $updatedByUserId, $requestId, $departmentId]);
  }

  public static function computeRequestOutcome(int $requestId): array
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT
        SUM(CASE WHEN status = "Approved" THEN 1 ELSE 0 END) AS approved_cnt,
        SUM(CASE WHEN status = "Rejected" THEN 1 ELSE 0 END) AS rejected_cnt,
        COUNT(*) AS total_cnt
      FROM clearance_status
      WHERE request_id = ?
    ');
    $stmt->execute([$requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $approved = (int)$row['approved_cnt'];
    $rejected = (int)$row['rejected_cnt'];
    $total = (int)$row['total_cnt'];

    if ($total <= 0) {
      return ['overall_status' => 'InProgress', 'completed_reason' => null];
    }

    if ($approved === $total) {
      return ['overall_status' => 'Cleared', 'completed_reason' => 'All departments approved'];
    }

    if ($rejected > 0) {
      return ['overall_status' => 'Rejected', 'completed_reason' => 'At least one department rejected'];
    }

    return ['overall_status' => 'InProgress', 'completed_reason' => null];
  }

  public static function setRequestOverallStatus(int $requestId, string $overallStatus, ?string $reason): void
  {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      UPDATE clearance_requests
      SET overall_status = ?,
          completed_reason = ?,
          completed_at = CASE WHEN ? IN ("Cleared", "Rejected") THEN CURRENT_TIMESTAMP ELSE NULL END
      WHERE request_id = ?
    ');
    $stmt->execute([$overallStatus, $reason, $overallStatus, $requestId]);
  }

  public static function getDepartmentStudentsProgress(int $departmentId, string $latestOnly = 'latest'): array
  {
    // Uses the latest clearance_request per student.
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT
        s.student_id,
        s.full_name,
        s.admission_number,
        cr.request_id,
        cr.overall_status,
        cs.status AS dept_status,
        cs.rejection_reason
      FROM students s
      JOIN (
        SELECT student_id, MAX(submitted_at) AS max_submitted_at
        FROM clearance_requests
        GROUP BY student_id
      ) x ON x.student_id = s.student_id
      JOIN clearance_requests cr ON cr.student_id = x.student_id AND cr.submitted_at = x.max_submitted_at
      LEFT JOIN clearance_status cs
        ON cs.request_id = cr.request_id AND cs.department_id = ?
      ORDER BY s.full_name ASC
    ');
    $stmt->execute([$departmentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getAdminProgressByDepartment(): array
  {
    // For each department: counts of dept_status based on latest request per student.
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('
      SELECT
        d.department_id,
        d.name AS department_name,
        SUM(CASE WHEN cs.status = "Pending" THEN 1 ELSE 0 END) AS pending_cnt,
        SUM(CASE WHEN cs.status = "Approved" THEN 1 ELSE 0 END) AS approved_cnt,
        SUM(CASE WHEN cs.status = "Rejected" THEN 1 ELSE 0 END) AS rejected_cnt,
        COUNT(cs.clearance_id) AS total_cnt
      FROM departments d
      LEFT JOIN clearance_status cs
        ON cs.department_id = d.department_id
      LEFT JOIN clearance_requests cr
        ON cr.request_id = cs.request_id
      LEFT JOIN (
        SELECT student_id, MAX(submitted_at) AS max_submitted_at
        FROM clearance_requests
        GROUP BY student_id
      ) x ON x.student_id = cr.student_id
        AND x.max_submitted_at = cr.submitted_at
      GROUP BY d.department_id, d.name
      ORDER BY d.sort_order ASC, d.name ASC
    ');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

