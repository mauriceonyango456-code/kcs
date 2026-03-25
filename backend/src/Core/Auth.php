<?php
declare(strict_types=1);

namespace KCS\Core;

use KCS\Core\Response;

class Auth
{
  public static function requireAuth(): array
  {
    $auth = $_SESSION['auth'] ?? null;
    if (!is_array($auth) || empty($auth['user_id']) || empty($auth['role_name'])) {
      Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
    return $auth;
  }

  public static function requireRole(array $allowedRoles): array
  {
    $auth = self::requireAuth();
    if (!in_array((string)$auth['role_name'], $allowedRoles, true)) {
      Response::json(['ok' => false, 'error' => 'Forbidden'], 403);
    }
    return $auth;
  }
}

