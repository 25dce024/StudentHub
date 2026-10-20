<?php
/**
 * StudentHub - Practical 11: Student Management CRUD Module
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['p11_csrf'])) {
    $_SESSION['p11_csrf'] = bin2hex(random_bytes(32));
}

$successMsg = $_SESSION['p11_success'] ?? null;
$errorMsg   = $_SESSION['p11_error'] ?? null;
unset($_SESSION['p11_success'], $_SESSION['p11_error']);

// Filter Parameters
$search     = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$status     = trim($_GET['status'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 6;
$offset     = ($page - 1) * $limit;

$students = [];
$totalStudents = 0;

if ($mysqli !== null) {
    // --------------------------------------------------------------------------
    // Construct Dynamic MySQLi Prepared Query with Filters
    // --------------------------------------------------------------------------
    $whereClauses = [];
    $paramTypes   = '';
    $paramValues  = [];

    if (!empty($search)) {
        $whereClauses[] = "(name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
        $searchWildcard = "%{$search}%";
        $paramTypes    .= "sss";
        $paramValues[]  = $searchWildcard;
        $paramValues[]  = $searchWildcard;
        $paramValues[]  = $searchWildcard;
    }

    if (!empty($department) && $department !== 'ALL') {
        $whereClauses[] = "branch = ?";
        $paramTypes    .= "s";
        $paramValues[]  = $department;
    }

    if (!empty($status) && $status !== 'ALL') {
        $whereClauses[] = "status = ?";
        $paramTypes    .= "s";
        $paramValues[]  = $status;
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // 1. Get total record count for pagination
    $countSql = "SELECT COUNT(*) FROM students {$whereSql}";
    $countStmt = $mysqli->prepare($countSql);
    if (!empty($paramValues)) {
        $countStmt->bind_param($paramTypes, ...$paramValues);
    }
    $countStmt->execute();
    $countStmt->bind_result($totalStudents);
    $countStmt->fetch();
    $countStmt->close();

    // 2. Fetch paginated records
    $dataSql = "SELECT * FROM students {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?";
    $dataStmt = $mysqli->prepare($dataSql);

    $paginatedTypes = $paramTypes . "ii";
    $paginatedValues = array_merge($paramValues, [$limit, $offset]);

    $dataStmt->bind_param($paginatedTypes, ...$paginatedValues);
    $dataStmt->execute();
    $res = $dataStmt->get_result();
    $students = $res->fetch_all(MYSQLI_ASSOC);
    $dataStmt->close();
}

$totalPages = ceil($totalStudents / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 11: Student Management CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Directory</span>
            </a>
            <span class="badge">Practical 11 &bull; MySQLi CRUD</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="crud-container">
            <div class="page-header">
                <span class="subheading">CHARUSAT &bull; FTE &bull; Practical 11</span>
                <h1>Student Management CRUD System</h1>
                <p>Complete data administration: Create, Read, Update, Delete with confirmation, Multi-field search, Department filters, Pagination, and CSV Export.</p>
            </div>

            <!-- Flash Alerts -->
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success">&#10004; <?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error">&#9888; <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <!-- Toolbar: Search, Filters & Action Buttons -->
            <div class="action-toolbar">
                <form method="GET" action="index.php" class="filter-form">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Search name, email, or ID..." 
                        value="<?= htmlspecialchars($search) ?>"
                    >

                    <select name="department" class="filter-select">
                        <option value="ALL">All Departments</option>
                        <option value="CE" <?= $department === 'CE' ? 'selected' : '' ?>>Computer Eng (CE)</option>
                        <option value="IT" <?= $department === 'IT' ? 'selected' : '' ?>>Info Tech (IT)</option>
                        <option value="AI & DS" <?= $department === 'AI & DS' ? 'selected' : '' ?>>AI &amp; Data Science</option>
                        <option value="EC" <?= $department === 'EC' ? 'selected' : '' ?>>Electronics &amp; Comm (EC)</option>
                        <option value="ME" <?= $department === 'ME' ? 'selected' : '' ?>>Mechanical (ME)</option>
                        <option value="CL" <?= $department === 'CL' ? 'selected' : '' ?>>Civil Eng (CL)</option>
                    </select>

                    <select name="status" class="filter-select">
                        <option value="ALL">All Statuses</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">&#128269; Filter</button>
                    <?php if (!empty($search) || !empty($department) || !empty($status)): ?>
                        <a href="index.php" class="btn btn-outline btn-sm">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="toolbar-buttons">
                    <a href="export.php?search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>" class="btn btn-outline btn-sm">
                        &#128190; Export to CSV
                    </a>
                    <a href="add.php" class="btn btn-success btn-sm">
                        &#43; Add New Student
                    </a>
                </div>
            </div>

            <!-- Table Card -->
            <div class="table-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <span style="font-size: 14px; font-weight: 600; color: var(--text-secondary);">
                        Showing <?= count($students) ?> of <?= $totalStudents ?> total matching students
                    </span>
                    <span style="font-size: 13px; color: var(--text-muted);">
                        Page <?= $page ?> of <?= max(1, $totalPages) ?>
                    </span>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Enrollment No</th>
                                <th>Student Name</th>
                                <th>Email Address</th>
                                <th>Program / Branch</th>
                                <th>Sem</th>
                                <th>CGPA</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                        No student records found matching the filter criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $st): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($st['student_id']) ?></code></td>
                                        <td><strong><?= htmlspecialchars($st['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($st['email']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($st['course']) ?> 
                                            <span class="badge" style="padding: 2px 6px; font-size: 11px;"><?= htmlspecialchars($st['branch'] ?? '') ?></span>
                                        </td>
                                        <td>Sem <?= htmlspecialchars((string)($st['semester'] ?? '3')) ?></td>
                                        <td>
                                            <span class="badge" style="background-color: #fef3c7; color: #92400e;">
                                                &#11088; <?= number_format((float)($st['cgpa'] ?? 8.5), 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status status-<?= strtolower(htmlspecialchars($st['status'])) ?>">
                                                <?= htmlspecialchars($st['status']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="action-links" style="justify-content: flex-end;">
                                                <a href="edit.php?id=<?= $st['id'] ?>" class="btn-icon-edit">&#9998; Edit</a>
                                                <form method="POST" action="delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete student: <?= htmlspecialchars(addslashes($st['name'])) ?> (<?= htmlspecialchars($st['student_id']) ?>)?');">
                                                    <input type="hidden" name="id" value="<?= $st['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['p11_csrf']) ?>">
                                                    <button type="submit" class="btn-icon-delete">&#128465; Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-bar">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>" class="page-btn">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>" class="page-link <?= $p === $page ? 'active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>" class="page-btn">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
