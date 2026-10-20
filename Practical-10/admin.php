<?php
/**
 * StudentHub - Practical 10: Protected Administrator Portal
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Server-side Role Check: Only 'admin' role allowed
requireRole('admin');

$user = getCurrentUser();

$studentCount = 0;
$eventCount = 0;
$regCount = 0;

if ($pdo !== null) {
    try {
        $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $eventCount   = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
        $regCount     = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
    } catch (Exception $e) {
        // Fallback
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 10: Administrator Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="admin.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Admin</span>
            </a>
            <div class="header-right">
                <div class="user-badge">
                    <span>&#128100; <strong><?= htmlspecialchars($user['username']) ?></strong></span>
                    <span class="role-tag role-admin">Administrator</span>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline">&#128682; Log Out</a>
            </div>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="portal-container">
            <div class="page-header">
                <span class="subheading">Access Control: Role = 'admin' (Verified)</span>
                <h1>Protected Administrator Console</h1>
                <p>Authenticated server-side session verified. Unauthenticated visitors and standard student roles are strictly blocked from this route.</p>
            </div>

            <!-- RBAC Verification Notice -->
            <div class="alert alert-success">
                &#10004; <strong>RBAC Policy Enforced:</strong> Access granted to user <code><?= htmlspecialchars($user['username']) ?></code> (Session ID: <code><?= session_id() ?></code>). Inactivity timeout is set to 15 minutes.
            </div>

            <!-- Admin Overview Metrics -->
            <div class="portal-grid">
                <div class="portal-card">
                    <h3>&#128101; Student Management</h3>
                    <p style="font-size: 28px; font-weight: 700; color: var(--brand-primary); margin: 8px 0;"><?= $studentCount ?> Records</p>
                    <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 16px;">Create, inspect, update, filter and export student directory.</p>
                    <a href="../Practical 11/index.php" class="btn btn-sm btn-primary">Open Student CRUD (Practical 11) &rarr;</a>
                </div>

                <div class="portal-card">
                    <h3>&#128197; Event Management</h3>
                    <p style="font-size: 28px; font-weight: 700; color: var(--brand-primary); margin: 8px 0;"><?= $eventCount ?> Events</p>
                    <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 16px;">Schedule events, validate and upload poster images.</p>
                    <a href="../Practical 12/index.php" class="btn btn-sm btn-primary">Open Event CRUD (Practical 12) &rarr;</a>
                </div>

                <div class="portal-card">
                    <h3>&#128202; Master Admin Dashboard</h3>
                    <p style="font-size: 28px; font-weight: 700; color: var(--brand-primary); margin: 8px 0;"><?= $regCount ?> Bookings</p>
                    <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 16px;">Full executive analytics, dynamic navigation &amp; audit trails.</p>
                    <a href="../Practical 14/index.php" class="btn btn-sm btn-primary">Open Admin Dashboard (Practical 14) &rarr;</a>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <p style="color: var(--text-muted); font-size: 13px;">Security Demonstration: Try opening <code>admin.php</code> in an incognito window or logging in as a student to observe the 403 Forbidden interceptor.</p>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; 2026 StudentHub &bull; Charotar University of Science and Technology (CHARUSAT)</p>
            <p>Faculty of Technology and Engineering (FTE) &bull; Subject: ITUE203 Web Development Frameworks</p>
        </div>
    </footer>

</body>
</html>
