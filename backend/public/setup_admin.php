<?php
declare(strict_types=1);

/**
 * One-time setup script to create the initial Admin user.
 *
 * SECURITY NOTE:
 * - Keep this script for academic setup only.
 * - After creating the admin, delete this file or lock access.
 */

if (!isset($_GET['run']) || (string)$_GET['run'] !== '1') {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Setup script is locked.\n\n";
  echo "Run once by visiting:\n";
  echo "  /setup_admin.php?run=1\n\n";
  exit;
}

require __DIR__ . '/../src/autoload.php';

use KCS\Models\UserModel;

$config = require __DIR__ . '/../config/config.php';
$setup = $config['setup'] ?? [];

$email = strtolower(trim((string)($setup['admin_email'] ?? '')));
$password = (string)($setup['admin_password'] ?? '');

header('Content-Type: text/plain; charset=utf-8');

if ($email === '' || $password === '') {
  echo "Missing admin setup config in backend/config/config.php\n";
  exit(1);
}

if (UserModel::findByEmail($email)) {
  echo "Admin already exists for email: {$email}\n";
  exit(0);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$adminId = UserModel::createUser('admin', $email, $hash);

echo "Admin created successfully.\n";
echo "Email: {$email}\n";
echo "Admin User ID: {$adminId}\n";
echo "\nNow login on /pages/login.html\n";

