<?php
/**
 * StudentHub - Practical 12: Event Management System with CRUD & Poster Upload
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['p12_csrf'])) {
    $_SESSION['p12_csrf'] = bin2hex(random_bytes(32));
}

$successMsg = $_SESSION['p12_success'] ?? null;
$errorMsg   = $_SESSION['p12_error'] ?? null;
unset($_SESSION['p12_success'], $_SESSION['p12_error']);

// Filter Parameters
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');

$events = [];
$stats = ['total' => 0, 'open' => 0, 'upcoming' => 0, 'closed' => 0];

if ($mysqli !== null) {
    // 1. Gather Summary Statistics
    $statRes = $mysqli->query("SELECT status, COUNT(*) as cnt FROM events GROUP BY status");
    if ($statRes) {
        while ($r = $statRes->fetch_assoc()) {
            $st = strtolower($r['status']);
            $count = (int)$r['cnt'];
            $stats['total'] += $count;
            if (isset($stats[$st])) {
                $stats[$st] = $count;
            }
        }
        $statRes->close();
    }

    // 2. Build Filtered Query
    $whereClauses = [];
    $paramTypes   = '';
    $paramValues  = [];

    if (!empty($search)) {
        $whereClauses[] = "(title LIKE ? OR venue LIKE ? OR description LIKE ?)";
        $searchWildcard = "%{$search}%";
        $paramTypes    .= "sss";
        $paramValues[]  = $searchWildcard;
        $paramValues[]  = $searchWildcard;
        $paramValues[]  = $searchWildcard;
    }

    if (!empty($category) && $category !== 'ALL') {
        $whereClauses[] = "category = ?";
        $paramTypes    .= "s";
        $paramValues[]  = $category;
    }

    if (!empty($status) && $status !== 'ALL') {
        $whereClauses[] = "status = ?";
        $paramTypes    .= "s";
        $paramValues[]  = $status;
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
    $querySql = "SELECT * FROM events {$whereSql} ORDER BY event_date ASC, event_time ASC";

    $stmt = $mysqli->prepare($querySql);
    if (!empty($paramValues)) {
        $stmt->bind_param($paramTypes, ...$paramValues);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $events = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 12: Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Events</span>
            </a>
            <span class="badge">Practical 12 &bull; MySQLi &amp; Poster Upload</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="page-header">
            <span class="subheading">CHARUSAT &bull; FTE &bull; Practical 12</span>
            <h1>Campus Event Management System</h1>
            <p>Full CRUD administration: Add, edit, remove events, upload posters, manage capacity, and search/filter dynamically.</p>
        </div>

        <!-- Flash Alerts -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">&#10004; <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error">&#9888; <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <?php if ($mysqli === null): ?>
            <div class="alert alert-error">&#9888; Database is offline. Ensure MySQL is running in XAMPP.</div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">&#128197;</div>
                <div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon open">&#10004;</div>
                <div>
                    <div class="stat-value"><?= $stats['open'] ?></div>
                    <div class="stat-label">Open Registrations</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon upcoming">&#9200;</div>
                <div>
                    <div class="stat-value"><?= $stats['upcoming'] ?></div>
                    <div class="stat-label">Upcoming Events</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon closed">&#128274;</div>
                <div>
                    <div class="stat-value"><?= $stats['closed'] ?></div>
                    <div class="stat-label">Past / Closed</div>
                </div>
            </div>
        </div>

        <!-- Toolbar: Search, Filters & Action Button -->
        <div class="action-toolbar">
            <form method="GET" action="index.php" class="filter-form">
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search by title, venue, or description..." 
                    value="<?= htmlspecialchars($search) ?>"
                >

                <select name="category" class="filter-select">
                    <option value="ALL">All Categories</option>
                    <option value="Technical" <?= $category === 'Technical' ? 'selected' : '' ?>>Technical</option>
                    <option value="Seminar" <?= $category === 'Seminar' ? 'selected' : '' ?>>Seminar / Summit</option>
                    <option value="Workshop" <?= $category === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                    <option value="Cultural" <?= $category === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
                    <option value="Sports" <?= $category === 'Sports' ? 'selected' : '' ?>>Sports</option>
                </select>

                <select name="status" class="filter-select">
                    <option value="ALL">All Statuses</option>
                    <option value="Open" <?= $status === 'Open' ? 'selected' : '' ?>>Open Only</option>
                    <option value="Upcoming" <?= $status === 'Upcoming' ? 'selected' : '' ?>>Upcoming Only</option>
                    <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Closed Only</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">&#128269; Filter</button>
                <?php if (!empty($search) || !empty($category) || !empty($status)): ?>
                    <a href="index.php" class="btn btn-outline btn-sm">Reset</a>
                <?php endif; ?>
            </form>

            <div class="toolbar-buttons">
                <a href="add.php" class="btn btn-success btn-sm">
                    &#43; Create New Event
                </a>
            </div>
        </div>

        <!-- Events Cards Grid -->
        <?php if (empty($events)): ?>
            <div style="background: #fff; padding: 48px; border-radius: var(--radius-lg); text-align: center; border: 1px solid var(--border);">
                <div style="font-size: 3rem; margin-bottom: 12px;">&#128197;</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">No Events Found</h3>
                <p style="color: var(--text-muted); margin-bottom: 20px;">No event records matched your filter criteria or the database is empty.</p>
                <a href="add.php" class="btn btn-primary btn-sm">&#43; Add the First Event</a>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $ev): ?>
                    <?php 
                        $posterPath = !empty($ev['poster']) && file_exists(__DIR__ . '/uploads/' . $ev['poster'])
                            ? 'uploads/' . htmlspecialchars($ev['poster'])
                            : 'uploads/default_poster.png';
                        $statusClass = 'status-' . strtolower(htmlspecialchars($ev['status']));
                        $formattedDate = date('M d, Y', strtotime($ev['event_date']));
                        $formattedTime = date('h:i A', strtotime($ev['event_time'] ?? '10:00:00'));
                    ?>
                    <div class="event-card">
                        <div class="event-poster-wrap">
                            <img src="<?= $posterPath ?>" alt="<?= htmlspecialchars($ev['title']) ?>" class="event-poster-img">
                            <span class="poster-category"><?= htmlspecialchars($ev['category']) ?></span>
                            <span class="poster-status badge-status <?= $statusClass ?>">
                                <?= htmlspecialchars($ev['status']) ?>
                            </span>
                        </div>
                        <div class="event-body">
                            <div class="event-date-row">
                                <span>&#128197; <?= $formattedDate ?></span>
                                <span>&bull;</span>
                                <span>&#9200; <?= $formattedTime ?></span>
                            </div>
                            <h3 class="event-title"><?= htmlspecialchars($ev['title']) ?></h3>
                            <div class="event-venue">
                                <span>&#128205;</span>
                                <span><?= htmlspecialchars($ev['venue']) ?></span>
                            </div>
                            <p class="event-desc">
                                <?= htmlspecialchars($ev['description'] ?? 'No description provided.') ?>
                            </p>
                            <div class="event-footer">
                                <span class="event-capacity">
                                    &#128101; Cap: <?= (int)$ev['capacity'] ?> seats
                                </span>
                                <div class="card-actions">
                                    <a href="edit.php?id=<?= $ev['id'] ?>" class="btn-icon-edit">&#9998; Edit</a>
                                    <form method="POST" action="delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete event: <?= htmlspecialchars(addslashes($ev['title'])) ?>?');">
                                        <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['p12_csrf']) ?>">
                                        <button type="submit" class="btn-icon-delete">&#128465; Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; 2026 StudentHub &bull; Charotar University of Science and Technology (CHARUSAT)</p>
            <p>Faculty of Technology and Engineering (FTE) &bull; Subject: ITUE203 Web Development Frameworks</p>
        </div>
    </footer>

</body>
</html>
