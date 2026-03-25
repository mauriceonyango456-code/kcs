<?php
declare(strict_types=1);

/**
 * Very small PSR-4-like autoloader for the academic project.
 * Namespace: KCS\
 */

spl_autoload_register(function (string $class) {
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

