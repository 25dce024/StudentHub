<?php
/**
 * StudentHub - Practical 12: Add New Event
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['p12_csrf'])) {
    $_SESSION['p12_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = [];

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

    $old = $_POST;

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

    // Handle Poster Upload
    $posterFilename = 'default_poster.png';

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code ' . $file['error'];
        } else {
            // Validate file size (max 3 MB)
            if ($file['size'] > 3 * 1024 * 1024) {
                $errors[] = 'Poster file size must be less than 3 MB.';
            }

            // Validate MIME type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $fileInfo = @getimagesize($file['tmp_name']);
            if (!$fileInfo || !in_array($fileInfo['mime'], $allowedTypes, true)) {
                $errors[] = 'Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.';
            }

            if (empty($errors)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $posterFilename = 'poster_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                $destination = __DIR__ . '/uploads/' . $posterFilename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors[] = 'Failed to save uploaded poster image to server.';
                    $posterFilename = 'default_poster.png';
                }
            }
        }
    }

    // Database Insert
    if (empty($errors) && $mysqli !== null) {
        $insertSql = "INSERT INTO events (title, category, event_date, event_time, venue, description, capacity, status, poster)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($insertSql);
        $stmt->bind_param(
            "ssssssiss",
            $title,
            $category,
            $eventDate,
            $eventTime,
            $venue,
            $description,
            $capacity,
            $status,
            $posterFilename
        );

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['p12_success'] = "Event '{$title}' was created successfully!";
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database failure while creating event.';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 12: Create Event</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Events</span>
            </a>
            <span class="badge">Practical 12 &bull; Add Event</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="page-header" style="max-width: 760px; margin: 0 auto 28px auto;">
            <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                &larr; Back to Events List
            </a>
            <h1 style="margin-top: 8px;">Create New Campus Event</h1>
            <p>Publish an upcoming technical symposium, hackathon, sports contest, or cultural showcase.</p>
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

            <form method="POST" action="add.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['p12_csrf']) ?>">

                <div class="form-group">
                    <label class="form-label" for="title">Event Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        placeholder="e.g. HackCHARUSAT 2026 National Hackathon" 
                        value="<?= htmlspecialchars($old['title'] ?? '') ?>" 
                        required
                    >
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="category">Category *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="Technical" <?= ($old['category'] ?? '') === 'Technical' ? 'selected' : '' ?>>Technical</option>
                            <option value="Seminar" <?= ($old['category'] ?? '') === 'Seminar' ? 'selected' : '' ?>>Seminar / Summit</option>
                            <option value="Workshop" <?= ($old['category'] ?? '') === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                            <option value="Cultural" <?= ($old['category'] ?? '') === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
                            <option value="Sports" <?= ($old['category'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Registration Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="Open" <?= ($old['status'] ?? 'Open') === 'Open' ? 'selected' : '' ?>>Open</option>
                            <option value="Upcoming" <?= ($old['status'] ?? '') === 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="Closed" <?= ($old['status'] ?? '') === 'Closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="event_date">Event Date *</label>
                        <input 
                            type="date" 
                            id="event_date" 
                            name="event_date" 
                            class="form-input" 
                            value="<?= htmlspecialchars($old['event_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>" 
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
                            value="<?= htmlspecialchars($old['event_time'] ?? '10:00') ?>" 
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
                            placeholder="e.g. Central Auditorium, CHARUSAT" 
                            value="<?= htmlspecialchars($old['venue'] ?? '') ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="capacity">Seating / Participant Capacity</label>
                        <input 
                            type="number" 
                            id="capacity" 
                            name="capacity" 
                            class="form-input" 
                            min="1" 
                            value="<?= htmlspecialchars((string)($old['capacity'] ?? 150)) ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="poster">Event Poster Image (JPG, PNG, WEBP - Max 3MB)</label>
                    <input type="file" id="poster" name="poster" class="form-input" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 4px;">
                        Leave blank to use the default branded StudentHub event poster.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Event Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        rows="4" 
                        placeholder="Provide details about prerequisites, schedules, guest speakers, or guidelines..."
                    ><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">&#10004; Publish Event</button>
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
