<?php
declare(strict_types=1);

/**
 * Very small PSR-4-like autoloader for the academic project.
 * Namespace: KCS\
 */

spl_autoload_register(function (string $class) {
  if (str_starts_with($class, 'PHPMailer\\PHPMailer\\')) {
    $relative = substr($class, strlen('PHPMailer\\PHPMailer\\'));
    $path = __DIR__ . '/PHPMailer/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require_once $path;
    return;
  }

  $prefix = 'KCS\\';
  if (!str_starts_with($class, $prefix)) {
    return;
  }

  $relative = substr($class, strlen($prefix)); // Core\Router => Core/Router
  $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

  if (is_file($path)) {
    require_once $path;
  }
});

