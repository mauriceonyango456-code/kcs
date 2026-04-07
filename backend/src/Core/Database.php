<?php
declare(strict_types=1);

namespace KCS\Core;

use PDO;
use PDOException;

class Database
{
  private static ?PDO $pdo = null;

  public static function pdo(): PDO
  {
    if (self::$pdo instanceof PDO) {
      return self::$pdo;
    }

    $config = require __DIR__ . '/../../config/config.php';
    $db = $config['db'];

    if (isset($db['driver']) && $db['driver'] === 'sqlite') {
      $dsn = 'sqlite:' . $db['path'];
    } else {
      $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['name'],
        $db['charset']
      );
    }

    try {
      self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    } catch (PDOException $e) {
      // Do not expose sensitive details in production. For academic, keep message generic.
      throw new \RuntimeException('Database connection failed.');
    }

    return self::$pdo;
  }
}

