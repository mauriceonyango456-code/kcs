<?php
declare(strict_types=1);

namespace KCS\Controllers;

use KCS\Core\Auth as AuthCore;
use KCS\Core\Csrf;
use KCS\Core\Response;
use KCS\Models\ClearanceModel;
use KCS\Models\DepartmentModel;
use KCS\Models\StudentModel;
use KCS\Services\ClearanceService;

class ClearanceController
{
  private static function inputJson(): array
  {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  public static function requestClearance(): void
  {
    $auth = AuthCore::requireRole(['student']);
    $studentId = (int)($auth['student_id'] ?? 0);
    if ($studentId <= 0) {
      Response::json(['ok' => false, 'error' => 'Student profile not found'], 400);
    }

    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    if (ClearanceModel::studentHasActiveRequest($studentId)) {
      Response::json(['ok' => false, 'error' => 'You already have an active clearance request'], 409);
    }

    $departments = DepartmentModel::getAllDepartments();
    if (count($departments) === 0) {
      Response::json(['ok' => false, 'error' => 'No departments configured'], 500);
    }

    $requestId = ClearanceModel::createRequestAndStatuses($studentId, $departments);
    Response::json(['ok' => true, 'request_id' => $requestId]);
  }

  public static function myClearanceStatus(): void
  {
    $auth = AuthCore::requireRole(['student']);
    $studentId = (int)($auth['student_id'] ?? 0);
    if ($studentId <= 0) {
      Response::json(['ok' => false, 'error' => 'Student profile not found'], 400);
    }

    $latest = StudentModel::getLatestRequest($studentId);
    if (!$latest) {
      Response::json(['ok' => true, 'data' => null]);
    }

    $statuses = ClearanceModel::getStatusesByRequest((int)$latest['request_id']);
    Response::json([
      'ok' => true,
      'data' => [
        'request_id' => (int)$latest['request_id'],
        'overall_status' => (string)$latest['overall_status'],
        'submitted_at' => (string)$latest['submitted_at'],
        'statuses' => $statuses,
      ],
    ]);
  }

  public static function departmentDecision(): void
  {
    $auth = AuthCore::requireRole(['department_staff']);
    $staffUserId = (int)$auth['user_id'];
    $staffDepartmentId = null;

    $input = self::inputJson();
    $requestId = (int)($input['request_id'] ?? 0);
    $departmentId = (int)($input['department_id'] ?? 0);
    $requestedStatus = (string)($input['status'] ?? '');
    $reason = isset($input['reason']) ? (string)$input['reason'] : null;

    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    if ($requestId <= 0 || $departmentId <= 0 || !in_array($requestedStatus, ['Approved', 'Rejected'], true)) {
      Response::json(['ok' => false, 'error' => 'Invalid payload'], 422);
    }

    $staffDepartmentId = DepartmentModel::getDepartmentIdByStaffUserId($staffUserId);
    if (!$staffDepartmentId || $staffDepartmentId !== $departmentId) {
      Response::json(['ok' => false, 'error' => 'You can only update your department'], 403);
    }

    $result = ClearanceService::processDepartmentDecision(
      $requestId,
      $departmentId,
      $requestedStatus,
      $reason,
      $staffUserId
    );

    if (!($result['ok'] ?? false)) {
      Response::json(['ok' => false, 'error' => $result['error'] ?? 'Failed'], 400);
    }

    Response::json($result);
  }
}

