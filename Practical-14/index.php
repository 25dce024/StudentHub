<?php
/**
 * StudentHub - Practical 14: Executive Admin Dashboard
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit_logger.php';

// Strict Role Guard: Only authenticated users with role 'admin' can access
requireAdmin();

$currentUsername = $_SESSION['username'] ?? 'Administrator';
$currentUserRole = $_SESSION['role'] ?? 'admin';
$currentUserId   = $_SESSION['user_id'] ?? 1;

// Flash feedback
$statusMsg = null;

// Allow manual test audit action from UI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_audit_action'])) {
    $actionName = trim(strip_tags($_POST['action_name'] ?? 'MANUAL_TEST_EVENT'));
    $actionDetail = trim(strip_tags($_POST['action_detail'] ?? 'Administrator initiated manual audit trail record.'));
    if (logAudit($currentUserId, $currentUsername, $actionName, 'system', null, $actionDetail)) {
        $statusMsg = "Audit action '{$actionName}' recorded successfully!";
    }
}

// --------------------------------------------------------------------------
// 1. Dynamic Database Statistics Loading (Theory Q1)
// --------------------------------------------------------------------------
$totalUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;
$latestRegistrations = [];
$auditLogs = [];
$deptBreakdown = [];

if ($pdo !== null) {
    try {
        // Metric 1: Total Users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int)$stmt->fetchColumn();

        // Metric 2: Total Students
        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $totalStudents = (int)$stmt->fetchColumn();

        // Metric 3: Total Events
        $stmt = $pdo->query("SELECT COUNT(*) FROM events");
        $totalEvents = (int)$stmt->fetchColumn();

        // Metric 4: Total Registrations
        $stmt = $pdo->query("SELECT COUNT(*) FROM registrations");
        $totalRegistrations = (int)$stmt->fetchColumn();

        // Latest Event Registrations with Join
        $regSql = "SELECT r.id, r.status, r.registration_date, s.name AS student_name, s.student_id, e.title AS event_title, e.category 
                   FROM registrations r 
                   JOIN students s ON r.student_id = s.id 
                   JOIN events e ON r.event_id = e.id 
                   ORDER BY r.id DESC LIMIT 6";
        $latestRegistrations = $pdo->query($regSql)->fetchAll();

        // Audit Logs Stream
        $auditSql = "SELECT * FROM audit_logs ORDER BY id DESC LIMIT 8";
        $auditLogs = $pdo->query($auditSql)->fetchAll();

        // Department breakdown for chart
        $deptSql = "SELECT branch, COUNT(*) as cnt FROM students GROUP BY branch ORDER BY cnt DESC LIMIT 5";
        $deptBreakdown = $pdo->query($deptSql)->fetchAll();

    } catch (PDOException $e) {
        $errorMsg = "Database error fetching stats: " . $e->getMessage();
    }
}

// --------------------------------------------------------------------------
// 2. Role-Based Dynamic Navigation Menu (Theory Q3)
// --------------------------------------------------------------------------
$dynamicMenu = getDynamicMenu($currentUserRole);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 14: Executive Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="app-layout">
        <!-- Sidebar Navigation with Dynamic Role-Based Menu -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <span style="font-size: 1.5rem;">&#127891;</span>
                    <span>StudentHub</span>
                </a>
            </div>

            <!-- Authenticated User Profile Summary -->
            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUsername, 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($currentUsername) ?></div>
                    <span class="user-role-badge">&#128272; <?= htmlspecialchars($currentUserRole) ?></span>
                </div>
            </div>

            <!-- Dynamic Role-Based Navigation Items -->
            <ul class="sidebar-menu">
                <li class="menu-label">Role Menu: <?= htmlspecialchars(ucfirst($currentUserRole)) ?></li>
                <?php foreach ($dynamicMenu as $menuItem): ?>
                    <li class="menu-item">
                        <a href="<?= htmlspecialchars($menuItem['url']) ?>" class="menu-link <?= $menuItem['active'] ? 'active' : '' ?>">
                            <span><?= $menuItem['icon'] ?></span>
                            <span><?= htmlspecialchars($menuItem['title']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link">
                    <span>&#128682;</span>
                    <span>Sign Out (Logout)</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Navigation Bar -->
            <header class="top-bar">
                <div class="breadcrumbs">
                    <span>StudentHub Console</span> &rsaquo; <strong>Executive Admin Dashboard</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        &#128197; <?= date('l, F j, Y') ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline btn-sm">&#128682; Logout</a>
                </div>
            </header>

            <div class="content-body">
                <div style="margin-bottom: 24px;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em;">
                        CHARUSAT &bull; CSPIT / FTE &bull; Practical 14
                    </span>
                    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-primary); margin: 4px 0;">
                        Administrative Command Console
                    </h1>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">
                        Real-time metrics, student enrollment, campus events, and tamper-resistant security audit trails.
                    </p>
                </div>

                <?php if (!empty($statusMsg)): ?>
                    <div class="alert alert-success">&#10004; <?= htmlspecialchars($statusMsg) ?></div>
                <?php endif; ?>

                <!-- Dynamic KPI Metrics (Theory Q1) -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div>
                            <div class="metric-title">Registered Users</div>
                            <div class="metric-val"><?= $totalUsers ?></div>
                        </div>
                        <div class="metric-icon icon-indigo">&#128100;</div>
                    </div>

                    <div class="metric-card">
                        <div>
                            <div class="metric-title">Enrolled Students</div>
                            <div class="metric-val"><?= $totalStudents ?></div>
                        </div>
                        <div class="metric-icon icon-emerald">&#127891;</div>
                    </div>

                    <div class="metric-card">
                        <div>
                            <div class="metric-title">Campus Events</div>
                            <div class="metric-val"><?= $totalEvents ?></div>
                        </div>
                        <div class="metric-icon icon-amber">&#128197;</div>
                    </div>

                    <div class="metric-card">
                        <div>
                            <div class="metric-title">Total Registrations</div>
                            <div class="metric-val"><?= $totalRegistrations ?></div>
                        </div>
                        <div class="metric-icon icon-sky">&#127915;</div>
                    </div>
                </div>

                <!-- Dual Dashboard Panels -->
                <div class="dashboard-grid">
                    <!-- Left Panel: Latest Registrations -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h2 class="dash-card-title">&#128221; Recent Event Registrations</h2>
                            <a href="../Practical 12/index.php" class="btn btn-outline btn-sm">Manage Events &rsaquo;</a>
                        </div>
                        <div class="dash-card-body" style="padding: 0;">
                            <div class="table-wrapper">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Event Title</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($latestRegistrations)): ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                                    No event registrations recorded yet.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($latestRegistrations as $reg): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($reg['student_name']) ?></strong><br>
                                                        <code style="font-size: 0.75rem;"><?= htmlspecialchars($reg['student_id']) ?></code>
                                                    </td>
                                                    <td><?= htmlspecialchars($reg['event_title']) ?></td>
                                                    <td>
                                                        <span class="badge" style="font-size: 11px; padding: 2px 6px;">
                                                            <?= htmlspecialchars($reg['category']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge-status badge-<?= strtolower(htmlspecialchars($reg['status'])) ?>">
                                                            <?= htmlspecialchars($reg['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td style="font-size: 0.8rem; color: var(--text-muted);">
                                                        <?= date('M d, H:i', strtotime($reg['registration_date'])) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Security Audit Logs (Theory Q4) -->
                    <div class="dash-card" id="audit-log-section">
                        <div class="dash-card-header">
                            <h2 class="dash-card-title">&#128220; Security Audit Trail</h2>
                            <span class="badge" style="background: #f1f5f9; color: var(--text-secondary);">Live Stream</span>
                        </div>
                        <div class="dash-card-body">
                            <ul class="audit-list">
                                <?php if (empty($auditLogs)): ?>
                                    <li style="text-align: center; padding: 20px; color: var(--text-muted);">
                                        No audit entries logged yet.
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($auditLogs as $log): ?>
                                        <li class="audit-item">
                                            <span style="font-size: 1.2rem;">&#9881;</span>
                                            <div style="flex: 1;">
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <span class="audit-action-tag"><?= htmlspecialchars($log['action']) ?></span>
                                                    <span class="audit-time"><?= date('H:i:s &bull; M d', strtotime($log['created_at'])) ?></span>
                                                </div>
                                                <div class="audit-detail" style="margin-top: 4px;">
                                                    <strong><?= htmlspecialchars($log['actor_username']) ?></strong>: <?= htmlspecialchars($log['details'] ?? 'Executed system routine') ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                                    IP: <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?> &bull; Entity: <?= htmlspecialchars($log['entity_type'] ?? 'general') ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>

                            <!-- Interactive Audit Action Recorder -->
                            <form method="POST" action="index.php" style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border);">
                                <input type="hidden" name="test_audit_action" value="1">
                                <div style="display: flex; gap: 8px;">
                                    <input 
                                        type="text" 
                                        name="action_name" 
                                        class="form-input" 
                                        value="ADMIN_MANUAL_CHECK" 
                                        style="flex: 1; font-size: 0.8rem; padding: 6px 10px;"
                                        placeholder="Action Code"
                                        required
                                    >
                                    <button type="submit" class="btn btn-primary btn-sm">&#43; Log Event</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Academic Department Enrollment Breakdown -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title">&#128202; Student Enrollment by Department</h2>
                        <a href="../Practical 11/index.php" class="btn btn-outline btn-sm">Manage Student Records &rsaquo;</a>
                    </div>
                    <div class="dash-card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                            <?php foreach ($deptBreakdown as $b): ?>
                                <div style="background: var(--background); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);"><?= htmlspecialchars($b['branch']) ?></div>
                                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 4px;"><?= $b['cnt'] ?> Students</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>
