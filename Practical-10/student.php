<?php
/**
 * StudentHub - Practical 10: Protected Student Portal
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Role Check: Requires logged-in student (or admin/faculty testing)
requireRole(['student', 'admin', 'faculty']);

$user = getCurrentUser();

$studentProfile = null;
$myEvents = [];

if ($pdo !== null) {
    try {
        // Fetch student details if available
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $user['email']]);
        $studentProfile = $stmt->fetch();

        // Fetch upcoming events
        $eventStmt = $pdo->query("SELECT id, title, category, event_date, venue FROM events WHERE status = 'Open' ORDER BY event_date ASC LIMIT 4");
        $myEvents = $eventStmt->fetchAll();
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
    <title>StudentHub - Practical 10: Student Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="student.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub</span>
            </a>
            <div class="header-right">
                <div class="user-badge">
                    <span>&#128100; <strong><?= htmlspecialchars($user['username']) ?></strong></span>
                    <span class="role-tag role-student"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline">&#128682; Log Out</a>
            </div>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="portal-container">
            <div class="page-header">
                <span class="subheading">Access Control: Role = 'student' (Verified)</span>
                <h1>Welcome, <?= htmlspecialchars($studentProfile['name'] ?? $user['username']) ?>!</h1>
                <p>Authenticated Student Portal &bull; Department of Computer Engineering &bull; CHARUSAT FTE</p>
            </div>

            <div class="alert alert-success">
                &#10004; <strong>Active Secure Session:</strong> Logged in as <code><?= htmlspecialchars($user['email']) ?></code> (Session ID: <code><?= session_id() ?></code>).
            </div>

            <!-- Student Cards Grid -->
            <div class="portal-grid">
                <!-- Academic Card -->
                <div class="portal-card">
                    <h3>&#128218; Academic Details</h3>
                    <p><strong>Enrollment No:</strong> <code><?= htmlspecialchars($studentProfile['student_id'] ?? '25DCE007') ?></code></p>
                    <p><strong>Program:</strong> <?= htmlspecialchars($studentProfile['course'] ?? 'B.Tech Computer Engineering') ?></p>
                    <p><strong>Branch / Sem:</strong> <?= htmlspecialchars($studentProfile['branch'] ?? 'CE') ?> &bull; Semester <?= htmlspecialchars((string)($studentProfile['semester'] ?? 3)) ?></p>
                    <p><strong>Current CGPA:</strong> <span class="role-tag role-student">&#11088; <?= htmlspecialchars(number_format((float)($studentProfile['cgpa'] ?? 9.24), 2)) ?></span></p>
                </div>

                <!-- Campus Events -->
                <div class="portal-card">
                    <h3>&#128197; Upcoming Campus Events</h3>
                    <ul style="list-style: none; margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($myEvents as $ev): ?>
                            <li style="border-bottom: 1px solid var(--border-color); padding-bottom: 6px; font-size: 13px;">
                                <strong><?= htmlspecialchars($ev['title']) ?></strong><br>
                                <span style="color: var(--text-muted);">&#128197; <?= htmlspecialchars($ev['event_date']) ?> &bull; <?= htmlspecialchars($ev['venue']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- RBAC Security Test Card -->
                <div class="portal-card" style="border-left: 4px solid #dc2626;">
                    <h3 style="color: #dc2626;">&#128683; RBAC Authorization Test</h3>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 14px;">
                        Try clicking the link below to access the restricted Administrator page. The server will intercept your student session and issue an HTTP 403 Forbidden.
                    </p>
                    <a href="admin.php" class="btn btn-sm btn-outline" style="color: #dc2626; border-color: #dc2626;">
                        Attempt Unauthorized Access to admin.php &rarr;
                    </a>
                </div>
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
