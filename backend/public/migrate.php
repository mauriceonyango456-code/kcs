<?php
/**
 * migrate.php
 * Endpoint to initialize the database tables on Railway.
 */
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

try {
// Check if we are using SQLite or MySQL
if (isset($config['db']['driver']) && $config['db']['driver'] === 'sqlite') {
    // Ensure the database directory exists
    $dbDir = dirname($config['db']['path']);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }
    
    // Connect to SQLite
    $pdo = new PDO("sqlite:" . $config['db']['path']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;"); // enable foreign keys
} else {
    // MySQL connection
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['name'],
        $config['db']['charset']
    );

    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

    // Read the SQL schema file
    $sqlPath = __DIR__ . '/../../database/kcs_clearance.sql';
    if (!file_exists($sqlPath)) {
        die("Error: SQL schema file not found at $sqlPath");
    }

    $sql = file_get_contents($sqlPath);

    // Filter out common MySQL statements that break SQLite
    $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
    $sql = preg_replace('/USE [^;]+;/i', '', $sql);

    if (isset($config['db']['driver']) && $config['db']['driver'] === 'sqlite') {
        // Translate MySQL syntax to SQLite
        $sql = str_ireplace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
        $sql = preg_replace('/INT(\s+UNSIGNED)?(\s+AUTOINCREMENT)?\s+PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/ENGINE=InnoDB/i', '', $sql);
        $sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sql);
        $sql = preg_replace('/ON DUPLICATE KEY UPDATE[^;]+;/i', ';', $sql); // Basic strip for inserts
    }

    // Execute the queries
    $pdo->exec($sql);
    
    echo "<h1 style='color:green;'>✅ Database Schema Initialized Successfully!</h1>";
    echo "<p>All tables have been created on the Railway MySQL database.</p>";
    echo "<p>Please <a href='/pages/login.html'>return to the login page</a> and try creating your account again.</p>";
    echo "<p><strong>Security Warning:</strong> For security, please delete this 'migrate.php' file from your codebase and push back to GitHub now.</p>";

} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Database Connection Error</h1>";
    echo "<p>It looks like the deployment variables on Railway are missing or incorrect.</p>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Please ensure you added these variables in your Railway App:</h3>";
    echo "<ul>";
    echo "<li><b>DB_HOST</b> = \${{MySQL.MYSQLHOST}}</li>";
    echo "<li><b>DB_PORT</b> = \${{MySQL.MYSQLPORT}}</li>";
    echo "<li><b>DB_USER</b> = \${{MySQL.MYSQLUSER}}</li>";
    echo "<li><b>DB_PASS</b> = \${{MySQL.MYSQLPASSWORD}}</li>";
    echo "<li><b>DB_NAME</b> = \${{MySQL.MYSQLDATABASE}}</li>";
    echo "</ul>";
}
