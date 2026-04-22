<?php
/**
 * One-time student seed script.
 * Run: http://localhost/kcs/backend/public/seed_students.php
 * Safe to run multiple times – skips existing records.
 */
declare(strict_types=1);
ob_start();
session_start();
require __DIR__ . '/../src/autoload.php';

use KCS\Core\Database;

header('Content-Type: text/html; charset=utf-8');

// ── Student data ─────────────────────────────────────────────────────────────
// Password for every student = their admission number (bcrypt hashed below)
$students = [
  ['name'=>'Maurice Onyango',    'adm'=>'KHS/2024/001', 'class'=>'Form 4A', 'email'=>'mauriceonyango456@gmail.com'],
  ['name'=>'Brian Wekesa',       'adm'=>'KHS/2024/002', 'class'=>'Form 4A', 'email'=>'adm002@kcs.ac.ke'],
  ['name'=>'Faith Achieng',      'adm'=>'KHS/2024/003', 'class'=>'Form 4B', 'email'=>'adm003@kcs.ac.ke'],
  ['name'=>'Dennis Odhiambo',    'adm'=>'KHS/2024/004', 'class'=>'Form 4B', 'email'=>'adm004@kcs.ac.ke'],
  ['name'=>'Grace Nafula',       'adm'=>'KHS/2024/005', 'class'=>'Form 4C', 'email'=>'adm005@kcs.ac.ke'],
  ['name'=>'Kevin Barasa',       'adm'=>'KHS/2024/006', 'class'=>'Form 4C', 'email'=>'adm006@kcs.ac.ke'],
  ['name'=>'Lydia Anyango',      'adm'=>'KHS/2024/007', 'class'=>'Form 4D', 'email'=>'adm007@kcs.ac.ke'],
  ['name'=>'Samuel Mulama',      'adm'=>'KHS/2024/008', 'class'=>'Form 4D', 'email'=>'adm008@kcs.ac.ke'],
  ['name'=>'Esther Nekesa',      'adm'=>'KHS/2024/009', 'class'=>'Form 4A', 'email'=>'adm009@kcs.ac.ke'],
  ['name'=>'Victor Lutomia',     'adm'=>'KHS/2024/010', 'class'=>'Form 4B', 'email'=>'adm010@kcs.ac.ke'],
  ['name'=>'Mercy Khayali',      'adm'=>'KHS/2024/011', 'class'=>'Form 4C', 'email'=>'adm011@kcs.ac.ke'],
  ['name'=>'Patrick Simiyu',     'adm'=>'KHS/2024/012', 'class'=>'Form 4D', 'email'=>'adm012@kcs.ac.ke'],
  ['name'=>'Alice Wangari',      'adm'=>'KHS/2024/013', 'class'=>'Form 4A', 'email'=>'adm013@kcs.ac.ke'],
  ['name'=>'James Mwangi',       'adm'=>'KHS/2024/014', 'class'=>'Form 4B', 'email'=>'adm014@kcs.ac.ke'],
  ['name'=>'Caroline Auma',      'adm'=>'KHS/2024/015', 'class'=>'Form 4C', 'email'=>'adm015@kcs.ac.ke'],
  ['name'=>'Henry Mutisya',      'adm'=>'KHS/2024/016', 'class'=>'Form 4D', 'email'=>'adm016@kcs.ac.ke'],
  ['name'=>'Irene Chebet',       'adm'=>'KHS/2024/017', 'class'=>'Form 4A', 'email'=>'adm017@kcs.ac.ke'],
  ['name'=>'Francis Mwenda',     'adm'=>'KHS/2024/018', 'class'=>'Form 4B', 'email'=>'adm018@kcs.ac.ke'],
  ['name'=>'Diana Njeri',        'adm'=>'KHS/2024/019', 'class'=>'Form 4C', 'email'=>'adm019@kcs.ac.ke'],
  ['name'=>'Stanley Otieno',     'adm'=>'KHS/2024/020', 'class'=>'Form 4D', 'email'=>'adm020@kcs.ac.ke'],
];

// Random fee balances (0 – 20 000 KSh, some fully paid at 0)
$balances = [0, 3500, 8750, 0, 12400, 500, 0, 19800, 6300, 0,
             4200, 11000, 0, 7650, 2100, 0, 15500, 9800, 3000, 0];

$pdo = Database::pdo();

// Fetch role IDs
$roleStmt = $pdo->query('SELECT role_id, role_name FROM roles');
$roles = [];
foreach ($roleStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $roles[$r['role_name']] = (int)$r['role_id'];
}
$studentRoleId = $roles['student'] ?? null;
if (!$studentRoleId) {
    die('<p style="color:red">ERROR: "student" role not found. Run the SQL schema first.</p>');
}

$inserted = 0;
$skipped  = 0;
$errors   = [];

foreach ($students as $i => $s) {
    $email    = strtolower($s['email']);
    $adm      = $s['adm'];
    $balance  = $balances[$i];
    $feeAmt   = 20000.00; // standard fee
    $paid     = $feeAmt - $balance;

    try {
        // Check existing user
        $chk = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        $existingUser = $chk->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $skipped++;
            continue;
        }

        // Check existing admission number
        $chk2 = $pdo->prepare('SELECT student_id FROM students WHERE admission_number = ? LIMIT 1');
        $chk2->execute([$adm]);
        if ($chk2->fetch()) {
            $skipped++;
            continue;
        }

        $hash = password_hash($adm, PASSWORD_BCRYPT);

        // Insert user
        $ins = $pdo->prepare('INSERT INTO users (role_id, email, password_hash, is_active) VALUES (?,?,?,1)');
        $ins->execute([$studentRoleId, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        // Insert student
        $ins2 = $pdo->prepare('INSERT INTO students (user_id, full_name, admission_number, class_name) VALUES (?,?,?,?)');
        $ins2->execute([$userId, $s['name'], $adm, $s['class']]);
        $studentId = (int)$pdo->lastInsertId();

        // Insert financial record
        $ins3 = $pdo->prepare('
            INSERT INTO financial_records
              (student_id, academic_year, term_name, fee_amount, amount_paid, balance, is_current)
            VALUES (?,?,?,?,?,?,1)
        ');
        $ins3->execute([$studentId, '2025/2026', 'Term 1', $feeAmt, $paid, (float)$balance]);

        $inserted++;
    } catch (Throwable $e) {
        $errors[] = "Row ".($i+1)." ({$s['name']}): " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>KCS – Student Seed</title>
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
  .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;}
  .badge-green{background:rgba(74,222,128,0.15);color:#4ade80;}
  .badge-amber{background:rgba(251,191,36,0.15);color:#fbbf24;}
  .badge-red{background:rgba(248,113,113,0.15);color:#f87171;}
</style>
</head>
<body>
<h1>🎓 KCS – Student Seed</h1>
<div class="card">
  <p class="ok">✅ Inserted: <strong><?= $inserted ?></strong></p>
  <p class="skip">⏭ Skipped (already exist): <strong><?= $skipped ?></strong></p>
  <?php if ($errors): ?>
    <p class="err">❌ Errors: <?= count($errors) ?></p>
    <ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  <?php endif; ?>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>#</th><th>Name</th><th>Admission No.</th><th>Email</th><th>Class</th><th>Balance (KSh)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $i => $s):
        $bal = $balances[$i];
        $badgeCls = $bal === 0 ? 'badge-green' : ($bal < 5000 ? 'badge-amber' : 'badge-red');
      ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= htmlspecialchars($s['adm']) ?></td>
        <td><?= htmlspecialchars($s['email']) ?></td>
        <td><?= htmlspecialchars($s['class']) ?></td>
        <td><span class="badge <?= $badgeCls ?>">KSh <?= number_format($bal, 2) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p style="font-size:12px;color:#475569;">Password for each student = their admission number (e.g. KHS/2024/001). Delete or rename this file after seeding.</p>
</body>
</html>
