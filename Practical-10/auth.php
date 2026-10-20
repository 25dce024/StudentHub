<?php
/**
 * StudentHub - Practical 10: Authentication & Session Middleware
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure Session Cookie Configuration
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie (expires when browser closes)
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true, // Prevents JavaScript from reading session cookie via document.cookie
        'samesite' => 'Lax' // Mitigates CSRF vulnerabilities
    ]);
    session_start();
}

/**
 * Enforce Inactivity Session Timeout (Default: 15 minutes = 900 seconds)
 */
function checkSessionTimeout(int $maxIdleSeconds = 900): void {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity'])) {
            $idleTime = time() - $_SESSION['last_activity'];
            if ($idleTime > $maxIdleSeconds) {
                // Inactivity threshold exceeded
                session_unset();
                session_destroy();
                header('Location: login.php?error=timeout');
                exit;
            }
        }
        $_SESSION['last_activity'] = time(); // Refresh activity timestamp
    }
}

/**
 * Check if a user is currently logged in
 */
function isAuthenticated(): bool {
    checkSessionTimeout();
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if user is not authenticated
 */
function requireLogin(): void {
    if (!isAuthenticated()) {
        header('Location: login.php?error=unauthorized');
        exit;
    }
}

/**
 * Enforce Role-Based Access Control (RBAC) on the server side
 * @param string|array $allowedRoles Single role or array of allowed roles (e.g. 'admin', ['admin', 'faculty'])
 */
function requireRole($allowedRoles): void {
    requireLogin();

    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $currentRole = $_SESSION['role'] ?? 'guest';

    if (!in_array($currentRole, $roles, true)) {
        http_response_code(403);
        renderAccessDeniedPage($currentRole, $roles);
        exit;
    }
}

/**
 * Return current session user details
 */
function getCurrentUser(): ?array {
    if (!isAuthenticated()) return null;
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'email'    => $_SESSION['email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'guest'
    ];
}

/**
 * Render 403 Forbidden Error Screen with clear feedback
 */
function renderAccessDeniedPage(string $currentRole, array $allowedRoles): void {
    $allowedStr = implode(', ', $allowedRoles);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Access Denied</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body style="background-color: #fef2f2;">
        <div style="max-width: 600px; margin: 80px auto; padding: 40px; background: #ffffff; border-radius: 12px; border: 1px solid #fca5a5; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <div style="font-size: 48px; color: #dc2626; margin-bottom: 16px;">&#128683; 403</div>
            <h1 style="color: #991b1b; font-size: 26px; margin-bottom: 12px;">Access Denied (Forbidden)</h1>
            <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
                Your account role (<strong><?= htmlspecialchars($currentRole) ?></strong>) does not have authorization to view this restricted page. 
                Required privilege: <strong><?= htmlspecialchars($allowedStr) ?></strong>.
            </p>
            <div style="background-color: #fef2f2; padding: 12px; border-radius: 6px; font-size: 13px; color: #b91c1c; margin-bottom: 24px;">
                Server-side authorization rule enforced: Unauthorized URL access blocked.
            </div>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <?php if ($currentRole === 'student'): ?>
                    <a href="student.php" class="btn btn-primary">&larr; Return to Student Portal</a>
                <?php elseif ($currentRole === 'admin'): ?>
                    <a href="admin.php" class="btn btn-primary">&larr; Return to Admin Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">&larr; Return to Login</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline">Log Out</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
