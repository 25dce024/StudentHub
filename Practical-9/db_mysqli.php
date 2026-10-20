<?php
/**
 * StudentHub - Practical 9: MySQLi Database Connection Module
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

function getMysqliConnection(): mysqli {
    static $mysqliInstance = null;

    if ($mysqliInstance !== null) {
        return $mysqliInstance;
    }

    $host   = 'localhost';
    $port   = 3306;
    $dbname = 'studenthub';
    $user   = 'root';
    $pass   = '';

    // Check shared config if present
    $sharedConfigFile = __DIR__ . '/../Practical 8/config.php';
    if (file_exists($sharedConfigFile)) {
        $cfg = require $sharedConfigFile;
      $host   = $cfg['host'] ?? $host;
        $port   = (int)($cfg['port'] ?? $port);
        $dbname = $cfg['dbname'] ?? $dbname;
        $user   = $cfg['username'] ?? $user;
        $pass   = $cfg['password'] ?? $pass;
    }

    // Report errors as exceptions
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

    try {
        $mysqliInstance = new mysqli($host, $user, $pass, $dbname, $port);
        $mysqliInstance->set_charset('utf8mb4');
        return $mysqliInstance;
    } catch (mysqli_sql_exception $e) {
        error_log("MySQLi Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed. Please ensure MySQL is running in XAMPP and 'studenthub' database is imported.");
    }
}

try {
    $mysqli = getMysqliConnection();
} catch (Exception $e) {
    $mysqli = null;
    $dbError = $e->getMessage();
}
