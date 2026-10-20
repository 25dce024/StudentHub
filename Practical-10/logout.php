<?php
/**
 * StudentHub - Practical 10: Secure Session Logout
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();

// Unset all session variables
$_SESSION = [];

// Delete session cookie from client
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

// Invalidate session on server
session_destroy();

// Redirect to login page with feedback
header('Location: login.php?logout=success');
exit;
