<?php
declare(strict_types=1);

namespace KCS\Services;

use KCS\Models\ClearanceModel;
use KCS\Models\DepartmentModel;
use KCS\Models\FinancialModel;
use KCS\Models\NotificationModel;
use KCS\Models\StudentModel;

class ClearanceService
{
  /**
   * @param string $requestedStatus Expected: Approved|Rejected
   */
  public static function processDepartmentDecision(
    int $requestId,
    int $departmentId,
    string $requestedStatus,
    ?string $reason,
    int $updatedByUserId
  ): array {
    $request = ClearanceModel::getRequest($requestId);
    if (!$request) {
      return ['ok' => false, 'error' => 'Clearance request not found'];
    }
    if ($request['overall_status'] === 'Cleared') {
      return ['ok' => false, 'error' => 'Clearance request is already fully completed and cannot be updated.'];
    }

    $finalStatus = $requestedStatus;
    $finalReason = $reason;

    // Updated objective #2: Financial validation gate
    if ($requestedStatus === 'Approved') {
      $studentId = (int)$request['student_id'];
      $balance = FinancialModel::getCurrentBalance($studentId);
      if ($balance > 0) {
        $finalStatus = 'Rejected';
        $finalReason = 'Fees balance not cleared (balance > 0). Clearance approval denied.';
      }
    }

    ClearanceModel::updateDepartmentStatus(
      $requestId,
      $departmentId,
      $finalStatus,
      $finalReason,
      $updatedByUserId
    );

    // Update overall request outcome after this department decision
    $outcome = ClearanceModel::computeRequestOutcome($requestId);
    ClearanceModel::setRequestOverallStatus($requestId, $outcome['overall_status'], $outcome['completed_reason']);

    // Updated objective #3: Automated email alert on successful clearance
    if ($outcome['overall_status'] === 'Cleared') {
      $studentId = (int)$request['student_id'];

      $type = 'clearance_success';
      if (!NotificationModel::isSent($studentId, $requestId, $type)) {
        $emailTo = NotificationModel::getStudentEmail($studentId);
        $studentName = StudentModel::getStudentName($studentId);

        // ALWAYS route to the user's verified Gmail account to avoid bouncing on dummy seeds.
        $actualEmailTo = 'mauriceonyango456@gmail.com';

        if ($emailTo && $studentName) {
          $subject = 'Kakamega High School Clearance Successful';
          $body = "Dear {$studentName},\n\nYou have been successfully cleared. Congratulations!\n\nRegards,\nKakamega High School Clearance System";
          EmailService::sendMail($actualEmailTo, $studentName, $subject, $body);

          NotificationModel::logSent(
            $studentId,
            $requestId,
            $type,
            $actualEmailTo,
            $body,
            date('Y-m-d H:i:s')
          );
        }
      }
    }

    return [
      'ok' => true,
      'overall_status' => $outcome['overall_status'],
      'department_status' => $finalStatus,
      'message' => $outcome['overall_status'] === 'Cleared'
        ? 'Student fully cleared. Email notification triggered.'
        : ($finalStatus === 'Rejected' ? 'Department decision recorded as Rejected.' : 'Department decision recorded as Approved.'),
    ];
  }
}

