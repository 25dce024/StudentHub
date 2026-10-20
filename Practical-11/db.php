<?php
/**
 * StudentHub - Practical 11: MySQLi Database Connection
 * Subject: Web Development Frameworks (ITUE203)
 */

function getP11Connection(): mysqli {
    static $mysqli = null;
    if ($mysqli !== null) return $mysqli;

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

    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

    try {
        $mysqli = new mysqli($host, $user, $pass, $dbname, $port);
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    } catch (mysqli_sql_exception $e) {
        error_log("Practical 11 MySQLi Error: " . $e->getMessage());
        throw new Exception("Database connection error. Ensure MySQL is running in XAMPP.");
    }
}

try {
    $mysqli = getP11Connection();
} catch (Exception $e) {
    $mysqli = null;
    $dbError = $e->getMessage();
}
