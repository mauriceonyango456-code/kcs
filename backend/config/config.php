<?php
/**
 * Central configuration.
 * Update DB and SMTP values before running.
 */

declare(strict_types=1);

return [
  // Database
  'db' => [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'name' => getenv('DB_NAME') ?: 'kcs_clearance',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'port' => getenv('DB_PORT') ?: 3306,
    'charset' => 'utf8mb4',
  ],

  // Email (SMTP - Gmail example)
  // IMPORTANT: Use a Gmail App Password, not your normal password.
  'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-gmail-address@gmail.com',
    'password' => 'your-gmail-app-password',
    'from_email' => 'your-gmail-address@gmail.com',
    'from_name' => 'Kakamega High School Clearance',
  ],

  // One-time bootstrap for initial admin creation (for academic setup).
  // After you create the admin, delete or lock this script.
  'setup' => [
    'admin_email' => 'admin@kcs.local',
    'admin_password' => 'ChangeMe-Admin123!',
  ],
];

