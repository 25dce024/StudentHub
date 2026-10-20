<?php
/**
 * StudentHub - Practical 11: Delete Student Record
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
if (empty($_SESSION['p11_csrf']) || !hash_equals($_SESSION['p11_csrf'], $csrf)) {
    $_SESSION['p11_error'] = 'Security validation failed (CSRF token mismatch). Please try again.';
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['p11_error'] = 'Invalid student ID specified for deletion.';
    header('Location: index.php');
    exit;
}

if ($mysqli === null) {
    $_SESSION['p11_error'] = 'Database connection error. Record could not be deleted.';
    header('Location: index.php');
    exit;
}

// Fetch student details before deletion for friendly flash message
$fetchStmt = $mysqli->prepare("SELECT name, student_id FROM students WHERE id = ? LIMIT 1");
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$fetchStmt->bind_result($studentName, $studentId);
$found = $fetchStmt->fetch();
$fetchStmt->close();

if (!$found) {
    $_SESSION['p11_error'] = "Student record #{$id} was not found or has already been removed.";
    header('Location: index.php');
    exit;
}

// Execute DELETE via MySQLi prepared statement
$delStmt = $mysqli->prepare("DELETE FROM students WHERE id = ?");
$delStmt->bind_param("i", $id);

if ($delStmt->execute()) {
    $delStmt->close();
    $_SESSION['p11_success'] = "Student '{$studentName}' ({$studentId}) was permanently removed.";
} else {
    $_SESSION['p11_error'] = 'Database failure while deleting student record.';
    $delStmt->close();
}

header('Location: index.php');
exit;
