<?php
declare(strict_types=1);

namespace KCS\Core;

class Router
{
  /** @var array<string, array<string, callable>> */
  private array $routes = [];

  public function add(string $method, string $path, callable $handler): void
  {
    $method = strtoupper($method);
    $path = '/' . ltrim($path, '/');
    $this->routes[$method][$path] = $handler;
  }

  public function dispatch(string $method, string $uri): void
  {
    $method = strtoupper($method);
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    
    // Strip subfolder prefix if running on something like XAMPP (e.g. /kcs/backend/public/api/...)
    $apiPos = strpos($path, '/api/');
    if ($apiPos !== false) {
      $path = substr($path, $apiPos);
    } else {
      $path = '/' . ltrim($path, '/');
    }

    // Redirect bare root to the front page.
    if ($path === '/') {
      header('Location: /pages/index.html', true, 302);
      exit;
    }

    // Handle API routes.
    if (isset($this->routes[$method][$path])) {
      call_user_func($this->routes[$method][$path]);
      return;
    }

    Response::json(['ok' => false, 'error' => 'Not Found'], 404);
  }
}

