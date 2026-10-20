<?php
/**
 * StudentHub - Practical 10: Database Connection Helper
 * Subject: Web Development Frameworks (ITUE203)
 */

function getP10Connection(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = 'localhost';
    $dbname = 'studenthub';
    $user = 'root';
    $pass = '';
    $port = 3306;

    $cfgFile = __DIR__ . '/../Practical 8/config.php';
    if (file_exists($cfgFile)) {
        $c = require $cfgFile;
        $host = $c['host'] ?? $host;
        $dbname = $c['dbname'] ?? $dbname;
        $user = $c['username'] ?? $user;
        $pass = $c['password'] ?? $pass;
        $port = $c['port'] ?? $port;
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Practical 10 DB Error: " . $e->getMessage());
        throw new PDOException("Database connection error. Ensure MySQL is running in XAMPP.");
    }
}

try {
    $pdo = getP10Connection();
} catch (PDOException $e) {
    $pdo = null;
    $dbError = $e->getMessage();
}
