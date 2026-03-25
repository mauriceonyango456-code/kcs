<?php
declare(strict_types=1);

namespace KCS\Controllers;

use KCS\Core\Response;
use KCS\Core\Csrf;
use KCS\Core\Database;
use KCS\Models\StudentModel;
use KCS\Models\UserModel;

class AuthController
{
  private static function inputJson(): array
  {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  public static function register(): void
  {
    $data = self::inputJson();

    $fullName = trim((string)($data['full_name'] ?? ''));
    $admissionNumber = trim((string)($data['admission_number'] ?? ''));
    $className = isset($data['class_name']) ? (string)$data['class_name'] : null;
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');

    if ($fullName === '' || $admissionNumber === '' || $email === '' || $password === '') {
      Response::json(['ok' => false, 'error' => 'Missing required fields'], 422);
    }

    // Basic CSRF protection for write requests
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Csrf::check($csrf ? (string)$csrf : null)) {
      Response::json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Response::json(['ok' => false, 'error' => 'Invalid email'], 422);
    }

    if (UserModel::findByEmail($email)) {
      Response::json(['ok' => false, 'error' => 'Email already registered'], 409);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $userId = UserModel::createUser('student', $email, $passwordHash);

    try {
      StudentModel::createStudent($userId, $fullName, $admissionNumber, $className);
    } catch (\Throwable $e) {
      Response::json(['ok' => false, 'error' => 'Student registration failed'], 500);
    }

    Response::json(['ok' => true]);
  }

  public static function login(): void
  {
    $data = self::inputJson();
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');

    if ($email === '' || $password === '') {
      Response::json(['ok' => false, 'error' => 'Missing credentials'], 422);
    }

    $user = UserModel::findByEmail($email);
    if (!$user || !($user['is_active'] ?? 0)) {
      Response::json(['ok' => false, 'error' => 'Invalid login'], 401);
    }

    if (!password_verify($password, $user['password_hash'])) {
      Response::json(['ok' => false, 'error' => 'Invalid login'], 401);
    }

    $auth = [
      'user_id' => (int)$user['user_id'],
      'role_name' => (string)$user['role_name'],
    ];

    if ($auth['role_name'] === 'student') {
      $student = StudentModel::getByUserId($auth['user_id']);
      if ($student) {
        $auth['student_id'] = (int)$student['student_id'];
      }
    }

    $_SESSION['auth'] = $auth;
    Response::json(['ok' => true, 'role_name' => $auth['role_name']]);
  }

  public static function logout(): void
  {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    Response::json(['ok' => true]);
  }

  public static function me(): void
  {
    $auth = $_SESSION['auth'] ?? null;
    if (!is_array($auth) || empty($auth['user_id']) || empty($auth['role_name'])) {
      Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
    }

    $payload = [
      'user_id' => (int)$auth['user_id'],
      'role_name' => (string)$auth['role_name'],
    ];

    if ($auth['role_name'] === 'student' && isset($auth['student_id'])) {
      $student = StudentModel::getByUserId((int)$auth['user_id']);
      $payload['student'] = $student ?: null;
    }

    Response::json(['ok' => true, 'data' => $payload]);
  }

  public static function csrf(): void
  {
    $token = Csrf::token();
    Response::json(['ok' => true, 'csrf_token' => $token]);
  }
}

