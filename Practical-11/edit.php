<?php
/**
 * StudentHub - Practical 11: Edit Existing Student Record
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['p11_csrf'])) {
    $_SESSION['p11_csrf'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0 || $mysqli === null) {
    $_SESSION['p11_error'] = 'Invalid student ID specified.';
    header('Location: index.php');
    exit;
}

// Fetch existing record
$fetchStmt = $mysqli->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$res = $fetchStmt->get_result();
$student = $res->fetch_assoc();
$fetchStmt->close();

if (!$student) {
    $_SESSION['p11_error'] = "Student record with ID #{$id} was not found.";
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['p11_csrf'], $csrf)) {
        $errors[] = 'CSRF token mismatch. Please try again.';
    }

    $studentId    = strtoupper(trim(strip_tags($_POST['student_id'] ?? '')));
    $name         = trim(strip_tags($_POST['name'] ?? ''));
    $email        = trim($_POST['email'] ?? '');
    $mobile       = trim($_POST['mobile'] ?? '');
    $course       = trim(strip_tags($_POST['course'] ?? ''));
    $branch       = trim($_POST['branch'] ?? 'CE');
    $semester     = (int)($_POST['semester'] ?? 3);
    $academicYear = trim($_POST['academic_year'] ?? '2nd Year');
    $cgpa         = (float)($_POST['cgpa'] ?? 8.5);
    $status       = trim($_POST['status'] ?? 'active');
    $address      = trim(strip_tags($_POST['address'] ?? ''));

    // Validation
    if (empty($studentId) || !preg_match('/^[0-9]{2}[A-Z]{2,4}[0-9]{3}$/', $studentId)) {
        $errors[] = 'Invalid enrollment number format (e.g. 25DCE050).';
    }

    if (empty($name) || !preg_match('/^[a-zA-Z\s]{3,50}$/', $name)) {
        $errors[] = 'Name must be 3-50 alphabetic characters and spaces.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errors[] = 'Valid 10-digit mobile number required.';
    }

    // Check duplicate email or enrollment belonging to another student
    if (empty($errors)) {
        $dupStmt = $mysqli->prepare("SELECT id FROM students WHERE (student_id = ? OR email = ?) AND id != ? LIMIT 1");
        $dupStmt->bind_param("ssi", $studentId, $email, $id);
        $dupStmt->execute();
        $dupStmt->store_result();
        if ($dupStmt->num_rows > 0) {
            $errors[] = 'Another student already holds this Enrollment Number or Email address.';
        }
        $dupStmt->close();
    }

    // Execute UPDATE statement
    if (empty($errors)) {
        $updateSql = "UPDATE students SET student_id = ?, name = ?, email = ?, course = ?, mobile = ?, branch = ?, semester = ?, academic_year = ?, cgpa = ?, address = ?, status = ? WHERE id = ?";
        $updateStmt = $mysqli->prepare($updateSql);
        $updateStmt->bind_param(
            "ssssssisdssi",
            $studentId,
            $name,
            $email,
            $course,
            $mobile,
            $branch,
            $semester,
            $academicYear,
            $cgpa,
            $address,
            $status,
            $id
        );

        if ($updateStmt->execute()) {
            $updateStmt->close();
            $_SESSION['p11_success'] = "Student '{$name}' ({$studentId}) updated successfully!";
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error updating student record.';
            $updateStmt->close();
        }
    }
} else {
    // Populate form with existing student values
    $studentId    = $student['student_id'];
    $name         = $student['name'];
    $email        = $student['email'];
    $mobile       = $student['mobile'];
    $course       = $student['course'];
    $branch       = $student['branch'];
    $semester     = $student['semester'];
    $academicYear = $student['academic_year'];
    $cgpa         = $student['cgpa'];
    $status       = $student['status'];
    $address      = $student['address'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 11: Edit Student #<?= $id ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Directory</span>
            </a>
            <span class="badge">Practical 11 &bull; Edit</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="crud-container">
            <div class="form-card">
                <div class="page-header" style="text-align: left;">
                    <span class="subheading">Student Directory &bull; UPDATE Operation</span>
                    <h1>Edit Student Record #<?= $id ?></h1>
                    <p>Modify student information and save changes using MySQLi parameterized prepared statements.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <div>
                            <strong>Update Errors:</strong>
                            <ul style="margin-left: 20px; margin-top: 6px;">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="edit.php?id=<?= $id ?>" method="POST" class="form-grid">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['p11_csrf']) ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_id">Enrollment Number (Student ID) *</label>
                            <input type="text" id="student_id" name="student_id" value="<?= htmlspecialchars($studentId) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="name">Full Student Name *</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Student Email *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="mobile">Mobile Number *</label>
                            <input type="tel" id="mobile" name="mobile" maxlength="10" value="<?= htmlspecialchars($mobile) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="branch">Department / Branch *</label>
                            <select id="branch" name="branch" required>
                                <option value="CE" <?= $branch === 'CE' ? 'selected' : '' ?>>Computer Engineering (CE)</option>
                                <option value="IT" <?= $branch === 'IT' ? 'selected' : '' ?>>Information Technology (IT)</option>
                                <option value="AI & DS" <?= $branch === 'AI & DS' ? 'selected' : '' ?>>AI &amp; Data Science (AI &amp; DS)</option>
                                <option value="EC" <?= $branch === 'EC' ? 'selected' : '' ?>>Electronics &amp; Comm (EC)</option>
                                <option value="ME" <?= $branch === 'ME' ? 'selected' : '' ?>>Mechanical Engineering (ME)</option>
                                <option value="CL" <?= $branch === 'CL' ? 'selected' : '' ?>>Civil Engineering (CL)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="course">Program Title *</label>
                            <input type="text" id="course" name="course" value="<?= htmlspecialchars($course) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="semester">Semester *</label>
                            <select id="semester" name="semester" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= $semester == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="academic_year">Academic Year *</label>
                            <select id="academic_year" name="academic_year" required>
                                <option value="1st Year" <?= $academicYear === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= $academicYear === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= $academicYear === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= $academicYear === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cgpa">Current CGPA</label>
                            <input type="number" step="0.01" min="0" max="10" id="cgpa" name="cgpa" value="<?= htmlspecialchars((string)$cgpa) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Account Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Residential Address / City</label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($address) ?>">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">&#10004; Update Student Details</button>
                    </div>
                </form>
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
