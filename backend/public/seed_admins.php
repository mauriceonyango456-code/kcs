<?php
/**
 * One-time admin and staff seed script.
 * Run: http://localhost/kcs/backend/public/seed_admins.php
 * Safe to run multiple times – skips existing records.
 */
declare(strict_types=1);
ob_start();
session_start();
require __DIR__ . '/../src/autoload.php';

use KCS\Core\Database;

header('Content-Type: text/html; charset=utf-8');

$pdo = Database::pdo();

// Fetch role IDs
$roleStmt = $pdo->query('SELECT role_id, role_name FROM roles');
$roles = [];
foreach ($roleStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $roles[$r['role_name']] = (int)$r['role_id'];
}
$adminRoleId = $roles['admin'] ?? null;
$staffRoleId = $roles['department_staff'] ?? null;

if (!$adminRoleId || !$staffRoleId) {
    die('<p style="color:red">ERROR: roles not found.</p>');
}

// Fetch department IDs
$deptStmt = $pdo->query('SELECT department_id, name FROM departments');
$departments = [];
foreach ($deptStmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $departments[$d['name']] = (int)$d['department_id'];
}

// Predefined accounts
$accounts = [
    [
        'email' => 'admin@kcs.ac.ke',
        'password' => 'Admin@2024',
        'role' => $adminRoleId,
        'role_name' => 'System Admin',
        'dept' => null
    ],
    [
        'email' => 'finance@kcs.ac.ke',
        'password' => 'Finance@2024',
        'role' => $staffRoleId,
        'role_name' => 'Department Staff',
        'dept' => $departments['Finance'] ?? null
    ],
    [
        'email' => 'library@kcs.ac.ke',
        'password' => 'Library@2024',
        'role' => $staffRoleId,
        'role_name' => 'Department Staff',
        'dept' => $departments['Library'] ?? null
    ],
    [
        'email' => 'laboratory@kcs.ac.ke',
        'password' => 'Lab@2024',
        'role' => $staffRoleId,
        'role_name' => 'Department Staff',
        'dept' => $departments['Laboratory'] ?? null
    ],
    [
        'email' => 'examinations@kcs.ac.ke',
        'password' => 'Exams@2024',
        'role' => $staffRoleId,
        'role_name' => 'Department Staff',
        'dept' => $departments['Examinations'] ?? null
    ],
    [
        'email' => 'discipline@kcs.ac.ke',
        'password' => 'Discipline@2024',
        'role' => $staffRoleId,
        'role_name' => 'Department Staff',
        'dept' => $departments['Discipline'] ?? null
    ]
];

$inserted = 0;
$skipped  = 0;
$errors   = [];

foreach ($accounts as $acc) {
    try {
        $chk = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$acc['email']]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $skipped++;
            continue;
        }

        $hash = password_hash($acc['password'], PASSWORD_BCRYPT);
        
        $ins = $pdo->prepare('INSERT INTO users (role_id, email, password_hash, is_active) VALUES (?, ?, ?, 1)');
        $ins->execute([$acc['role'], $acc['email'], $hash]);
        $userId = (int)$pdo->lastInsertId();

        if ($acc['dept']) {
            $insDept = $pdo->prepare('INSERT INTO department_staff (user_id, department_id) VALUES (?, ?)');
            $insDept->execute([$userId, $acc['dept']]);
        }

        $inserted++;
    } catch (Throwable $e) {
        $errors[] = $acc['email'] . ': ' . $e->getMessage();
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>KCS – Admin & Staff Seed</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  body{font-family:Inter,sans-serif;background:#0f172a;color:#e2e8f0;padding:40px;margin:0;}
  h1{color:#38bdf8;margin-bottom:24px;}
  .card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;margin-bottom:20px;}
  .ok{color:#4ade80;} .skip{color:#fbbf24;} .err{color:#f87171;}
  table{width:100%;border-collapse:collapse;font-size:13px;}
  th{text-align:left;padding:10px 12px;background:#0f172a;color:#94a3b8;font-weight:600;border-bottom:1px solid #334155;}
  td{padding:10px 12px;border-bottom:1px solid #1e293b;}
  tr:hover td{background:#243043;}
</style>
</head>
<body>
<h1>🛡️ KCS – Admin & Staff Seed</h1>
<div class="card">
  <p class="ok">✅ Inserted: <strong><?= $inserted ?></strong></p>
  <p class="skip">⏭ Skipped (already exist): <strong><?= $skipped ?></strong></p>
  <?php if ($errors): ?>
    <p class="err">❌ Errors: <?= count($errors) ?></p>
    <ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Login Credentials</h2>
  <table>
    <thead>
      <tr>
        <th>Role</th><th>Department</th><th>Email</th><th>Password</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($accounts as $a): ?>
      <tr>
        <td><?= htmlspecialchars($a['role_name']) ?></td>
        <td><?= $a['dept'] ? htmlspecialchars(array_search($a['dept'], $departments)) : '—' ?></td>
        <td style="font-weight:600;color:#38bdf8;"><?= htmlspecialchars($a['email']) ?></td>
        <td style="font-family:monospace;"><?= htmlspecialchars($a['password']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
