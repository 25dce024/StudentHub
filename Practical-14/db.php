<?php
/**
 * StudentHub - Practical 14: Database Connection Helper (PDO)
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

function getAdminPdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = 'localhost';
    $dbname = 'studenthub';
    $user = 'root';
    $pass = '';
    $port = 3306;

    $cfgFile = __DIR__ . '/../Practical 8/config.php';
    if (file_exists($cfgFile)) {
        $cfg = require $cfgFile;
        $host = $cfg['host'] ?? $host;
        $dbname = $cfg['dbname'] ?? $dbname;
        $user = $cfg['username'] ?? $user;
        $pass = $cfg['password'] ?? $pass;
        $port = (int)($cfg['port'] ?? $port);
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Practical 14 PDO Error: " . $e->getMessage());
        throw new Exception("Database offline. Ensure MySQL is running in XAMPP.");
    }
}

try {
    $pdo = getAdminPdo();
} catch (Exception $e) {
    $pdo = null;
    $dbError = $e->getMessage();
}
