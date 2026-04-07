<?php
/**
 * migrate.php
 * Endpoint to initialize the database tables on Railway.
 */
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

try {
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

    // Read the SQL schema file
    $sqlPath = __DIR__ . '/../../database/kcs_clearance.sql';
    if (!file_exists($sqlPath)) {
        die("Error: SQL schema file not found at $sqlPath");
    }

    $sql = file_get_contents($sqlPath);

    // Some shared SQL files have DELIMITER or CREATE DATABASE which PDO doesn't like directly
    // Let's strip CREATE DATABASE just in case since Railway already creates it
    $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
    $sql = preg_replace('/USE [^;]+;/i', '', $sql);
    
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
