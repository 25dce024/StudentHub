<?php
/**
 * StudentHub - Practical 11: Add New Student
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['p11_csrf'])) {
    $_SESSION['p11_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = [];

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

    $old = $_POST;

    // Validation
    if (empty($studentId)) {
        $errors[] = 'Enrollment Number (Student ID) is required.';
    } elseif (!preg_match('/^[0-9]{2}[A-Z]{2,4}[0-9]{3}$/', $studentId)) {
        $errors[] = 'Invalid enrollment number format (e.g. 25DCE050).';
    }

    if (empty($name)) {
        $errors[] = 'Student Name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s]{3,50}$/', $name)) {
        $errors[] = 'Name must be 3-50 alphabetic characters and spaces.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errors[] = 'Valid 10-digit mobile number required starting with 6-9.';
    }

    if ($mysqli === null) {
        $errors[] = 'Database is offline.';
    }

    // Check duplicates via MySQLi Prepared Statement
    if (empty($errors) && $mysqli !== null) {
        $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
        $checkStmt->bind_param("ss", $studentId, $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $errors[] = 'A student with this Enrollment Number or Email address already exists.';
        }
        $checkStmt->close();
    }

    // Insert Student via MySQLi Prepared Statement
    if (empty($errors) && $mysqli !== null) {
        $college = 'CSPIT / DEPSTAR, CHARUSAT';
        $insertSql = "INSERT INTO students (student_id, name, email, course, mobile, college, branch, semester, academic_year, cgpa, address, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($insertSql);
        $stmt->bind_param(
            "sssssssisdss",
            $studentId,
            $name,
            $email,
            $course,
            $mobile,
            $college,
            $branch,
            $semester,
            $academicYear,
            $cgpa,
            $address,
            $status
        );

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['p11_success'] = "Student '{$name}' ({$studentId}) was created successfully!";
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Failed to insert student record into database.';
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
    <title>StudentHub - Practical 11: Add New Student</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub &bull; Directory</span>
            </a>
            <span class="badge">Practical 11 &bull; Create</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="crud-container">
            <div class="form-card">
                <div class="page-header" style="text-align: left;">
                    <span class="subheading">Student Directory &bull; INSERT Operation</span>
                    <h1>Add New Student Record</h1>
                    <p>Enter the student's personal, enrollment, and academic attributes. All database writes utilize parameterized MySQLi prepared statements.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <div>
                            <strong>Validation Errors:</strong>
                            <ul style="margin-left: 20px; margin-top: 6px;">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="add.php" method="POST" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['p11_csrf']) ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_id">Enrollment Number (Student ID) *</label>
                            <input type="text" id="student_id" name="student_id" placeholder="e.g. 25DCE055" value="<?= htmlspecialchars($old['student_id'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="name">Full Student Name *</label>
                            <input type="text" id="name" name="name" placeholder="e.g. Harshil Shah" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Student Email *</label>
                            <input type="email" id="email" name="email" placeholder="e.g. harshil@charusat.edu.in" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="mobile">Mobile Number *</label>
                            <input type="tel" id="mobile" name="mobile" placeholder="e.g. 9876543210" maxlength="10" value="<?= htmlspecialchars($old['mobile'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="branch">Department / Branch *</label>
                            <select id="branch" name="branch" required>
                                <option value="CE" <?= ($old['branch'] ?? '') === 'CE' ? 'selected' : '' ?>>Computer Engineering (CE)</option>
                                <option value="IT" <?= ($old['branch'] ?? '') === 'IT' ? 'selected' : '' ?>>Information Technology (IT)</option>
                                <option value="AI & DS" <?= ($old['branch'] ?? '') === 'AI & DS' ? 'selected' : '' ?>>AI &amp; Data Science (AI &amp; DS)</option>
                                <option value="EC" <?= ($old['branch'] ?? '') === 'EC' ? 'selected' : '' ?>>Electronics &amp; Comm (EC)</option>
                                <option value="ME" <?= ($old['branch'] ?? '') === 'ME' ? 'selected' : '' ?>>Mechanical Engineering (ME)</option>
                                <option value="CL" <?= ($old['branch'] ?? '') === 'CL' ? 'selected' : '' ?>>Civil Engineering (CL)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="course">Program Title *</label>
                            <input type="text" id="course" name="course" value="<?= htmlspecialchars($old['course'] ?? 'B.Tech Computer Engineering') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="semester">Semester *</label>
                            <select id="semester" name="semester" required>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ($old['semester'] ?? 3) == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="academic_year">Academic Year *</label>
                            <select id="academic_year" name="academic_year" required>
                                <option value="1st Year" <?= ($old['academic_year'] ?? '') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= ($old['academic_year'] ?? '2nd Year') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= ($old['academic_year'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= ($old['academic_year'] ?? '') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cgpa">Current CGPA (0.00 - 10.00)</label>
                            <input type="number" step="0.01" min="0" max="10" id="cgpa" name="cgpa" value="<?= htmlspecialchars((string)($old['cgpa'] ?? 8.50)) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Account Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?= ($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Residential Address / City</label>
                        <input type="text" id="address" name="address" placeholder="e.g. Anand, Gujarat" value="<?= htmlspecialchars($old['address'] ?? '') ?>">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">&#10004; Save Student Record</button>
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
