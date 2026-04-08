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
      $sqlitePath = $db['path'];
      $needsInit  = !file_exists($sqlitePath);

      // Auto-create parent directory if missing (e.g. first deploy)
      $dir = dirname($sqlitePath);
      if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
      }

      $dsn  = 'sqlite:' . $sqlitePath;
      $user = null;
      $pass = null;
    } else {
      $needsInit = false;
      $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'] ?? 3306,
        $db['name'],
        $db['charset']
      );
      $user = $db['user'] ?? null;
      $pass = $db['pass'] ?? null;
    }

    try {
      self::$pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    } catch (PDOException $e) {
      throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
    }

    // Bootstrap SQLite schema on first run
    if ($needsInit) {
      self::initSqlite(self::$pdo);
    }

    return self::$pdo;
  }

  private static function initSqlite(PDO $pdo): void
  {
    $pdo->exec("PRAGMA journal_mode=WAL;");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS roles (
        role_id    INTEGER PRIMARY KEY AUTOINCREMENT,
        role_name  TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
      );
    ");
    $pdo->exec("INSERT OR IGNORE INTO roles (role_name) VALUES ('admin'),('department_staff'),('student');");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS users (
        user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
        role_id       INTEGER NOT NULL REFERENCES roles(role_id),
        email         TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        is_active     INTEGER NOT NULL DEFAULT 1,
        created_at    TEXT NOT NULL DEFAULT (datetime('now'))
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS students (
        student_id       INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id          INTEGER NOT NULL UNIQUE REFERENCES users(user_id),
        full_name        TEXT NOT NULL,
        admission_number TEXT NOT NULL UNIQUE,
        class_name       TEXT,
        created_at       TEXT NOT NULL DEFAULT (datetime('now'))
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS departments (
        department_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name          TEXT NOT NULL UNIQUE,
        sort_order    INTEGER NOT NULL DEFAULT 0,
        created_at    TEXT NOT NULL DEFAULT (datetime('now'))
      );
    ");
    $pdo->exec("INSERT OR IGNORE INTO departments (name, sort_order) VALUES
      ('Library',10),('Finance',20),('Laboratory',30),('Examinations',40),('Discipline',50);");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS department_staff (
        user_id       INTEGER PRIMARY KEY REFERENCES users(user_id),
        department_id INTEGER NOT NULL REFERENCES departments(department_id)
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS financial_records (
        financial_record_id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id    INTEGER NOT NULL REFERENCES students(student_id),
        academic_year TEXT NOT NULL DEFAULT '2025/2026',
        term_name     TEXT NOT NULL DEFAULT 'Term 1',
        fee_amount    REAL NOT NULL DEFAULT 0.00,
        amount_paid   REAL NOT NULL DEFAULT 0.00,
        balance       REAL NOT NULL DEFAULT 0.00,
        is_current    INTEGER NOT NULL DEFAULT 1,
        created_at    TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (student_id, is_current)
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS clearance_requests (
        request_id       INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id       INTEGER NOT NULL REFERENCES students(student_id),
        submitted_at     TEXT NOT NULL DEFAULT (datetime('now')),
        overall_status   TEXT NOT NULL DEFAULT 'InProgress',
        completed_at     TEXT,
        completed_reason TEXT
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS clearance_status (
        clearance_id     INTEGER PRIMARY KEY AUTOINCREMENT,
        request_id       INTEGER NOT NULL REFERENCES clearance_requests(request_id),
        student_id       INTEGER NOT NULL REFERENCES students(student_id),
        department_id    INTEGER NOT NULL REFERENCES departments(department_id),
        status           TEXT NOT NULL DEFAULT 'Pending',
        rejection_reason TEXT,
        updated_by       INTEGER REFERENCES users(user_id),
        updated_at       TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (request_id, department_id)
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS notifications (
        notification_id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id  INTEGER NOT NULL REFERENCES students(student_id),
        request_id  INTEGER REFERENCES clearance_requests(request_id),
        type        TEXT NOT NULL,
        email_to    TEXT,
        message     TEXT,
        sent_at     TEXT,
        created_at  TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (student_id, request_id, type)
      );
    ");
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS feedback (
        feedback_id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id  INTEGER NOT NULL REFERENCES students(student_id),
        request_id  INTEGER NOT NULL REFERENCES clearance_requests(request_id),
        rating      INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
        comment     TEXT,
        created_at  TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (request_id)
      );
    ");

    // Auto-create the admin user from config (so no manual setup step needed)
    try {
      $cfg   = require __DIR__ . '/../../config/config.php';
      $setup = $cfg['setup'] ?? [];
      $email = strtolower(trim((string)($setup['admin_email'] ?? '')));
      $pw    = (string)($setup['admin_password'] ?? '');

      if ($email !== '' && $pw !== '') {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if (!$check->fetch()) {
          $hash        = password_hash($pw, PASSWORD_BCRYPT);
          $roleStmt    = $pdo->prepare('SELECT role_id FROM roles WHERE role_name = ? LIMIT 1');
          $roleStmt->execute(['admin']);
          $role        = $roleStmt->fetch();
          if ($role) {
            $ins = $pdo->prepare('INSERT INTO users (role_id, email, password_hash, is_active) VALUES (?,?,?,1)');
            $ins->execute([$role['role_id'], $email, $hash]);
          }
        }
      }
    } catch (\Throwable $e) {
      // Non-fatal: admin can be created via setup_admin.php if this fails
    }
  }
}
