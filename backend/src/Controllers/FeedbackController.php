<?php
declare(strict_types=1);

namespace KCS\Controllers;

use KCS\Core\Auth as AuthCore;
use KCS\Core\Csrf;
use KCS\Core\Response;
use KCS\Models\ClearanceModel;
use KCS\Models\FeedbackModel;

class FeedbackController
{
  private static function inputJson(): array
  {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  public static function submit(): void
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

    $input = self::inputJson();
    $requestId = (int)($input['request_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $comment = isset($input['comment']) ? (string)$input['comment'] : null;

    if ($requestId <= 0 || $rating < 1 || $rating > 5) {
      Response::json(['ok' => false, 'error' => 'Invalid payload'], 422);
    }

    $request = ClearanceModel::getRequest($requestId);
    if (!$request || (int)$request['student_id'] !== $studentId) {
      Response::json(['ok' => false, 'error' => 'Request not found'], 404);
    }

    if (($request['overall_status'] ?? '') !== 'Cleared') {
      Response::json(['ok' => false, 'error' => 'Feedback is available after successful clearance only'], 409);
    }

    if (FeedbackModel::hasFeedbackForRequest($requestId)) {
      Response::json(['ok' => false, 'error' => 'Feedback already submitted for this request'], 409);
    }

    FeedbackModel::submitFeedback($studentId, $requestId, $rating, $comment);
    Response::json(['ok' => true]);
  }

  public static function adminAnalytics(): void
  {
    AuthCore::requireRole(['admin']);
    $data = FeedbackModel::getAdminAnalytics(20);
    Response::json(['ok' => true, 'analytics' => $data, 'data' => $data]);
  }
}

