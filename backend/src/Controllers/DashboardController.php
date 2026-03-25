<?php
declare(strict_types=1);

namespace KCS\Controllers;

use KCS\Core\Auth as AuthCore;
use KCS\Core\Response;
use KCS\Models\ClearanceModel;
use KCS\Models\DepartmentModel;

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

    $rows = ClearanceModel::getDepartmentStudentsProgress($deptId);

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
}

