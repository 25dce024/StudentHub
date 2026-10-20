<?php
/**
 * StudentHub - Practical 14: Secure Logout & Session Termination
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit_logger.php';

if (isset($_SESSION['user_id'])) {
    logAudit(
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'unknown',
        'AUTH_LOGOUT',
        'user',
        $_SESSION['user_id'],
        'User explicitly terminated session via logout'
    );
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header('Location: login.php?logout=1');
exit;
