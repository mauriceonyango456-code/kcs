<?php
declare(strict_types=1);

namespace KCS\Controllers;

use KCS\Core\Auth as AuthCore;
use KCS\Core\Database;
use KCS\Core\Response;
use KCS\Models\ClearanceModel;
use KCS\Models\DepartmentModel;
use KCS\Models\FinancialModel;
use KCS\Models\StudentModel;
use PDO;

class DashboardController
{
  public static function departmentStudentsProgress(): void
  {
    $auth = AuthCore::requireRole(['department_staff']);
    $userId = (int)$auth['user_id'];
    $deptId = DepartmentModel::getDepartmentIdByStaffUserId($userId);
    if (!$deptId) {
      Response::json(['ok' => false, 'error' => 'Department assignment not found'], 403);
    }

    // Get department name for Finance detection on frontend
    $pdo = Database::pdo();
    $dRow = $pdo->prepare('SELECT name FROM departments WHERE department_id = ? LIMIT 1');
    $dRow->execute([$deptId]);
    $deptName = (string)(($dRow->fetch(PDO::FETCH_ASSOC))['name'] ?? '');

    $rows = ClearanceModel::getDepartmentStudentsProgress($deptId);

    // Attach fee balance to each row so Finance can display/update it
    $balanceStmt = $pdo->prepare('
      SELECT f.balance FROM financial_records f
      JOIN students s ON s.student_id = f.student_id
      WHERE s.student_id = ? AND f.is_current = 1 LIMIT 1
    ');
    foreach ($rows as &$r) {
      $balanceStmt->execute([(int)$r['student_id']]);
      $bRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
      $r['fee_balance'] = $bRow ? (float)$bRow['balance'] : 0.0;
    }
    unset($r);

    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
    foreach ($rows as $r) {
      $st = (string)($r['dept_status'] ?? 'Pending');
      if (!isset($counts[$st])) {
        $st = 'Pending';
      }
      $counts[$st]++;
    }

    Response::json([
      'ok' => true,
      'department_id' => $deptId,
      'department_name' => $deptName,
      'counts' => $counts,
      'students' => $rows,
    ]);
  }

  public static function adminProgressByDepartment(): void
  {
    AuthCore::requireRole(['admin']);
    $data = ClearanceModel::getAdminProgressByDepartment();

    Response::json(['ok' => true, 'departments' => $data]);
  }

  public static function studentDashboard(): void
  {
    $auth      = AuthCore::requireRole(['student']);
    $userId    = (int)$auth['user_id'];
    $studentId = (int)($auth['student_id'] ?? 0);

    $student = StudentModel::getByUserId($userId);
    if (!$student) {
      Response::json(['ok' => false, 'error' => 'Student profile not found'], 404);
    }
    $actualStudentId = (int)$student['student_id'];

    // Get email from users table
    $pdo   = Database::pdo();
    $uStmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ? LIMIT 1');
    $uStmt->execute([$userId]);
    $uRow  = $uStmt->fetch(PDO::FETCH_ASSOC);
    $email = $uRow ? (string)$uRow['email'] : '';

    $financial = FinancialModel::getFullRecord($actualStudentId);
    $latest    = StudentModel::getLatestRequest($actualStudentId);

    Response::json([
      'ok' => true,
      'data' => [
        'student'    => array_merge($student, ['email' => $email]),
        'financial'  => $financial,
        'clearance'  => $latest ? [
          'request_id'     => (int)$latest['request_id'],
          'overall_status' => (string)$latest['overall_status'],
          'submitted_at'   => (string)$latest['submitted_at'],
        ] : null,
      ],
    ]);
  }
}

