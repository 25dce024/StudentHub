<?php
/**
 * StudentHub - Practical 14: Role-Based Access Control (RBAC) & Middleware
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Enforce Inactivity Session Timeout (Default: 900 seconds / 15 mins)
 */
function checkSessionTimeout(int $maxIdle = 900): void {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity'])) {
            if ((time() - $_SESSION['last_activity']) > $maxIdle) {
                session_unset();
                session_destroy();
                header('Location: login.php?error=timeout');
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }
}

function isAuthenticated(): bool {
    checkSessionTimeout();
    return isset($_SESSION['user_id']);
}

/**
 * Restrict page access strictly to 'admin' users
 */
function requireAdmin(): void {
    if (!isAuthenticated()) {
        header('Location: login.php?error=unauthorized');
        exit;
    }

    $role = $_SESSION['role'] ?? 'student';
    if ($role !== 'admin') {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 Forbidden - Admin Access Required</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f6f6f1;">
            <div style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-width: 480px; text-align: center; border: 1px solid #d1d5db; border-top: 4px solid #0c4f45;">
                <div style="font-size: 3rem; margin-bottom: 12px; color: #ef4444;">&#128683;</div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">403 - Access Forbidden</h2>
                <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 24px;">
                    Your account is registered with role <strong><?= htmlspecialchars(strtoupper($role)) ?></strong>. The Executive Admin Dashboard requires administrative privileges.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <a href="login.php" class="btn btn-primary btn-sm">&#128273; Switch to Admin Account</a>
                    <a href="../Practical 10/student.php" class="btn btn-outline btn-sm">Go to Student Portal</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Generate Dynamic Navigation Menu based on user role
 */
function getDynamicMenu(string $role): array {
    if ($role === 'admin') {
        return [
            ['title' => 'Dashboard Overview', 'icon' => '&#128202;', 'url' => 'index.php', 'active' => true],
            ['title' => 'Student Management', 'icon' => '&#127891;', 'url' => '../Practical 11/index.php', 'active' => false],
            ['title' => 'Event Management', 'icon' => '&#128197;', 'url' => '../Practical 12/index.php', 'active' => false],
            ['title' => 'Async JSON Engine', 'icon' => '&#9889;', 'url' => '../Practical 13/index.html', 'active' => false],
            ['title' => 'Security Audit Logs', 'icon' => '&#128220;', 'url' => '#audit-log-section', 'active' => false],
            ['title' => 'System Settings', 'icon' => '&#9881;', 'url' => '#settings', 'active' => false],
        ];
    } elseif ($role === 'faculty') {
        return [
            ['title' => 'Faculty Home', 'icon' => '&#127979;', 'url' => '#faculty-home', 'active' => true],
            ['title' => 'Class Directory', 'icon' => '&#128101;', 'url' => '../Practical 11/index.php', 'active' => false],
            ['title' => 'Campus Events', 'icon' => '&#128197;', 'url' => '../Practical 12/index.php', 'active' => false],
            ['title' => 'Attendance Roster', 'icon' => '&#128203;', 'url' => '#attendance', 'active' => false],
        ];
    } else {
        // Default: Student role
        return [
            ['title' => 'Student Portal', 'icon' => '&#127891;', 'url' => '../Practical 10/student.php', 'active' => true],
            ['title' => 'Browse Events', 'icon' => '&#128197;', 'url' => '../Practical 12/index.php', 'active' => false],
            ['title' => 'My Registrations', 'icon' => '&#127915;', 'url' => '#my-regs', 'active' => false],
            ['title' => 'Campus FAQs', 'icon' => '&#10067;', 'url' => '../Practical 6/index.html', 'active' => false],
        ];
    }
}
