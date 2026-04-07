<?php
session_start();
$password = 'KcsAdmin2026!'; // Secure password to access the viewer

if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['db_admin_auth'] = true;
    } else {
        $error = "Incorrect Password!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: db-admin.php");
    exit;
}

if (!isset($_SESSION['db_admin_auth'])) {
    echo "<h2>KCS Database Secure Viewer</h2>";
    if (isset($error)) echo "<p style='color:red;'>$error</p>";
    echo "<form method='POST'>Password: <input type='password' name='password'> <button type='submit'>Login</button></form>";
    exit;
}

// Connect to SQLite
$dbPath = __DIR__ . '/../../database/kcs.sqlite';
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$isNew = !file_exists($dbPath) || filesize($dbPath) === 0;

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Auto-migrate if it's wiped!
    if ($isNew) {
        $sqlPath = __DIR__ . '/../../database/kcs_clearance.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
            $sql = preg_replace('/USE [^;]+;/i', '', $sql);
            $sql = preg_replace('/SET SQL_MODE[^;]+;/i', '', $sql);
            $sql = preg_replace('/SET time_zone[^;]+;/i', '', $sql);
            $sql = str_ireplace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
            $sql = preg_replace('/INT(\s+UNSIGNED)?(\s+AUTOINCREMENT)?\s+PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/ENGINE=InnoDB/i', '', $sql);
            $sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sql);
            $sql = preg_replace('/ON DUPLICATE KEY UPDATE[^;]+;/i', ';', $sql);
            $pdo->exec($sql);
        }
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$tablesQuery = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
$tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);

$selectedTable = $_GET['table'] ?? ($tables[0] ?? null);

echo "<h2>KCS Secure Database Viewer</h2>";
echo "<p><a href='?logout=1'>Logout</a></p>";
echo "<h3>Tables:</h3><ul>";
foreach ($tables as $table) {
    echo "<li><a href='?table=$table'>$table</a></li>";
}
echo "</ul><hr>";

if ($selectedTable && in_array($selectedTable, $tables)) {
    echo "<h3>Viewing Table: $selectedTable</h3>";
    $stmt = $pdo->query("SELECT * FROM $selectedTable LIMIT 100");
    $rows = $stmt->fetchAll();
    
    if (count($rows) === 0) {
        echo "<p>Table is empty.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%; text-align:left;'>";
        echo "<tr style='background:#f4f4f4;'>";
        foreach (array_keys($rows[0]) as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars((string)$val) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>
