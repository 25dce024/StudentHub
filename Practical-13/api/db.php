<?php
/**
 * StudentHub - Practical 13: JSON API Database Connection
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

function getApiPdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = 'localhost';
    $dbname = 'studenthub';
    $user = 'root';
    $pass = '';
    $port = 3306;

    $cfgFile = __DIR__ . '/../../Practical 8/config.php';
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

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

/**
 * Helper to output standardized JSON responses
 */
function sendJsonResponse(bool $success, int $statusCode, string $message, $data = null, array $errors = []): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    $response = [
        'success'   => $success,
        'status'    => $statusCode,
        'message'   => $message,
        'timestamp' => date('c')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
