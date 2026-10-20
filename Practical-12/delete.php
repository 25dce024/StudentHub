<?php
/**
 * StudentHub - Practical 12: Delete Event Record & Remove Poster
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verify CSRF token
$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['p12_csrf']) || !hash_equals($_SESSION['p12_csrf'], $csrf)) {
    $_SESSION['p12_error'] = 'Security validation failed (CSRF token mismatch).';
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || $mysqli === null) {
    $_SESSION['p12_error'] = 'Invalid event ID specified for deletion.';
    header('Location: index.php');
    exit;
}

// Fetch event details and poster before removal
$fetchStmt = $mysqli->prepare("SELECT title, poster FROM events WHERE id = ? LIMIT 1");
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$fetchStmt->bind_result($eventTitle, $eventPoster);
$found = $fetchStmt->fetch();
$fetchStmt->close();

if (!$found) {
    $_SESSION['p12_error'] = "Event #{$id} was not found or was already deleted.";
    header('Location: index.php');
    exit;
}

// Delete from database using MySQLi prepared statement
$delStmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
$delStmt->bind_param("i", $id);

if ($delStmt->execute()) {
    $delStmt->close();

    // Clean up uploaded poster file if custom
    if (!empty($eventPoster) && $eventPoster !== 'default_poster.png') {
        $posterPath = __DIR__ . '/uploads/' . $eventPoster;
        if (file_exists($posterPath)) {
            @unlink($posterPath);
        }
    }

    $_SESSION['p12_success'] = "Event '{$eventTitle}' and associated poster were successfully deleted.";
} else {
    $_SESSION['p12_error'] = 'Database failure while deleting event.';
    $delStmt->close();
}

header('Location: index.php');
exit;
