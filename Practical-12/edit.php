<?php
/**
 * StudentHub - Practical 12: Edit Event & Replace Poster
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['p12_csrf'])) {
    $_SESSION['p12_csrf'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0 || $mysqli === null) {
    $_SESSION['p12_error'] = 'Invalid event ID requested.';
    header('Location: index.php');
    exit;
}

// Fetch existing record
$fetchStmt = $mysqli->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$res = $fetchStmt->get_result();
$event = $res->fetch_assoc();
$fetchStmt->close();

if (!$event) {
    $_SESSION['p12_error'] = "Event record #{$id} was not found.";
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['p12_csrf'], $csrf)) {
        $errors[] = 'CSRF token mismatch. Please try again.';
    }

    $title       = trim(strip_tags($_POST['title'] ?? ''));
    $category    = trim($_POST['category'] ?? 'Technical');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $eventTime   = trim($_POST['event_time'] ?? '10:00');
    $venue       = trim(strip_tags($_POST['venue'] ?? ''));
    $capacity    = max(1, (int)($_POST['capacity'] ?? 100));
    $status      = trim($_POST['status'] ?? 'Open');
    $description = trim(strip_tags($_POST['description'] ?? ''));

    // Validation
    if (empty($title) || strlen($title) < 3) {
        $errors[] = 'Event title must be at least 3 characters.';
    }

    if (empty($eventDate)) {
        $errors[] = 'Valid event date is required.';
    }

    if (empty($venue)) {
        $errors[] = 'Event venue is required.';
    }

    $allowedStatuses = ['Open', 'Upcoming', 'Closed'];
    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Invalid event status.';
    }

    $currentPoster = $event['poster'];
    $newPoster = $currentPoster;

    // Handle New Poster Upload (optional)
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error code: ' . $file['error'];
        } else {
            if ($file['size'] > 3 * 1024 * 1024) {
                $errors[] = 'Poster file size must be less than 3 MB.';
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $fileInfo = @getimagesize($file['tmp_name']);
            if (!$fileInfo || !in_array($fileInfo['mime'], $allowedTypes, true)) {
                $errors[] = 'Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.';
            }

            if (empty($errors)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFilename = 'poster_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                $destination = __DIR__ . '/uploads/' . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Unlink previous custom poster if exists and not default
                    if (!empty($currentPoster) && $currentPoster !== 'default_poster.png') {
                        $oldPath = __DIR__ . '/uploads/' . $currentPoster;
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $newPoster = $newFilename;
                } else {
                    $errors[] = 'Failed to move uploaded poster to storage directory.';
                }
            }
        }
    }

    // Database Update
    if (empty($errors)) {
        $updateSql = "UPDATE events SET title = ?, category = ?, event_date = ?, event_time = ?, venue = ?, description = ?, capacity = ?, status = ?, poster = ? WHERE id = ?";
        $updateStmt = $mysqli->prepare($updateSql);
        $updateStmt->bind_param(
            "ssssssissi",
            $title,
            $category,
            $eventDate,
            $eventTime,
            $venue,
            $description,
            $capacity,
            $status,
            $newPoster,
            $id
        );

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $_SESSION['p12_success'] = "Event '{$title}' updated successfully!";
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database failure while updating event details.';
            $updateStmt->close();
        }
    }
} else {
    // Populate form with current values
    $title       = $event['title'];
    $category    = $event['category'];
    $eventDate   = $event['event_date'];
    $eventTime   = $event['event_time'];
    $venue       = $event['venue'];
    $capacity    = $event['capacity'];
    $status      = $event['status'];
    $description = $event['description'];
    $newPoster   = $event['poster'];
}

$previewPoster = !empty($newPoster) && file_exists(__DIR__ . '/uploads/' . $newPoster)
    ? 'uploads/' . htmlspecialchars($newPoster)
    : 'uploads/default_poster.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 12: Edit Event</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Events</span>
            </a>
            <span class="badge">Practical 12 &bull; Edit Event</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="page-header" style="max-width: 760px; margin: 0 auto 28px auto;">
            <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                &larr; Back to Events List
            </a>
            <h1 style="margin-top: 8px;">Edit Event: <?= htmlspecialchars($event['title']) ?></h1>
            <p>Update event timing, capacity, venue, registration status, or replace poster artwork.</p>
        </div>

        <div class="form-card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <div>
                        <strong>Please resolve the following issues:</strong>
                        <ul style="margin: 8px 0 0 20px; font-size: 0.9rem;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="edit.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['p12_csrf']) ?>">

                <div class="form-group">
                    <label class="form-label" for="title">Event Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        value="<?= htmlspecialchars($title) ?>" 
                        required
                    >
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="category">Category *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="Technical" <?= $category === 'Technical' ? 'selected' : '' ?>>Technical</option>
                            <option value="Seminar" <?= $category === 'Seminar' ? 'selected' : '' ?>>Seminar / Summit</option>
                            <option value="Workshop" <?= $category === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                            <option value="Cultural" <?= $category === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
                            <option value="Sports" <?= $category === 'Sports' ? 'selected' : '' ?>>Sports</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Registration Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="Open" <?= $status === 'Open' ? 'selected' : '' ?>>Open</option>
                            <option value="Upcoming" <?= $status === 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="event_date">Event Date *</label>
                        <input 
                            type="date" 
                            id="event_date" 
                            name="event_date" 
                            class="form-input" 
                            value="<?= htmlspecialchars($eventDate) ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="event_time">Event Time *</label>
                        <input 
                            type="time" 
                            id="event_time" 
                            name="event_time" 
                            class="form-input" 
                            value="<?= htmlspecialchars(substr($eventTime, 0, 5)) ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="venue">Campus Venue *</label>
                        <input 
                            type="text" 
                            id="venue" 
                            name="venue" 
                            class="form-input" 
                            value="<?= htmlspecialchars($venue) ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="capacity">Seating Capacity</label>
                        <input 
                            type="number" 
                            id="capacity" 
                            name="capacity" 
                            class="form-input" 
                            min="1" 
                            value="<?= htmlspecialchars((string)$capacity) ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Poster Artwork</label>
                    <div class="poster-preview-wrapper">
                        <img src="<?= $previewPoster ?>" alt="Current Poster" class="poster-thumb">
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">
                            File: <code><?= htmlspecialchars($event['poster'] ?? 'default_poster.png') ?></code>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="poster">Replace Poster (Optional - Max 3MB)</label>
                    <input type="file" id="poster" name="poster" class="form-input" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 4px;">
                        Leave empty to keep the existing event poster.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Event Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        rows="4"
                    ><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">&#10004; Save Changes</button>
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; 2026 StudentHub &bull; Charotar University of Science and Technology (CHARUSAT)</p>
        </div>
    </footer>

</body>
</html>
