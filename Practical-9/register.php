<?php
/**
 * StudentHub - Practical 9: Secure User Registration with MySQLi & Hashing
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

session_start();
require_once __DIR__ . '/db_mysqli.php';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = $_SESSION['p9_errors'] ?? [];
$success = $_SESSION['p9_success'] ?? null;
$old = $_SESSION['p9_old'] ?? [];

unset($_SESSION['p9_errors'], $_SESSION['p9_success'], $_SESSION['p9_old']);

// Handle POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedCsrf)) {
        $_SESSION['p9_errors'] = ['Security check failed: Invalid or expired CSRF token.'];
        header('Location: register.php');
        exit;
    }

    $username        = trim(strip_tags($_POST['username'] ?? ''));
    $fullName        = trim(strip_tags($_POST['fullName'] ?? ''));
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $role            = trim($_POST['role'] ?? 'student');
    $studentId       = strtoupper(trim(strip_tags($_POST['studentId'] ?? '')));

    $formErrors = [];
    $oldData = [
        'username'  => $username,
        'fullName'  => $fullName,
        'email'     => $email,
        'role'      => $role,
        'studentId' => $studentId
    ];

    // Validation
    if (empty($username)) {
        $formErrors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $formErrors[] = 'Username must be between 3 and 30 characters (letters, numbers, underscore only).';
    }

    if (empty($fullName)) {
        $formErrors[] = 'Full name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s]{3,50}$/', $fullName)) {
        $formErrors[] = 'Full name must contain alphabetic letters and spaces only.';
    }

    if (empty($email)) {
        $formErrors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Email address format is invalid.';
    }

    if (empty($password)) {
        $formErrors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $formErrors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=]/', $password)) {
        $formErrors[] = 'Password must include uppercase, lowercase, number, and special character.';
    }

    if ($password !== $confirmPassword) {
        $formErrors[] = 'Passwords do not match.';
    }

    $allowedRoles = ['student', 'faculty'];
    if (!in_array($role, $allowedRoles, true)) {
        $formErrors[] = 'Invalid account role selected.';
    }

    if ($mysqli === null) {
        $formErrors[] = 'Database is currently offline. Please ensure MySQL is running in XAMPP.';
    }

    // Database duplicate checks (MySQLi Prepared Statements)
    if (empty($formErrors) && $mysqli !== null) {
        // 1. Check duplicate Email
        $emailStmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $emailStmt->bind_param("s", $email);
        $emailStmt->execute();
        $emailStmt->store_result();
        if ($emailStmt->num_rows > 0) {
            $formErrors[] = 'An account with this email address already exists. Please log in or use a different email.';
        }
        $emailStmt->close();

        // 2. Check duplicate Username
        $userStmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $userStmt->bind_param("s", $username);
        $userStmt->execute();
        $userStmt->store_result();
        if ($userStmt->num_rows > 0) {
            $formErrors[] = 'This username is already taken. Please choose another username.';
        }
        $userStmt->close();
    }

    // If validation failed, redirect back with errors
    if (!empty($formErrors)) {
        $_SESSION['p9_errors'] = $formErrors;
        $_SESSION['p9_old'] = $oldData;
        header('Location: register.php');
        exit;
    }

    // --------------------------------------------------------------------------
    // Secure Password Hashing & Insertion via MySQLi Prepared Statement
    // --------------------------------------------------------------------------
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    try {
        $insertStmt = $mysqli->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        $insertStmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
        $insertStmt->execute();
        $newUserId = $insertStmt->insert_id;
        $insertStmt->close();

        // If role is student, insert/sync basic profile in students table
        if ($role === 'student' && !empty($studentId)) {
            $studentStmt = $mysqli->prepare("INSERT INTO students (student_id, name, email, course, mobile, status) 
                                             VALUES (?, ?, ?, 'Computer Engineering', '9876543210', 'active') 
                                             ON DUPLICATE KEY UPDATE name = VALUES(name)");
            $studentStmt->bind_param("sss", $studentId, $fullName, $email);
            $studentStmt->execute();
            $studentStmt->close();
        }

        // Record security audit log entry
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $logAction = 'USER_REGISTRATION';
        $details = "Registered new {$role} account (Username: {$username})";
        $auditStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, actor_username, action, entity_type, entity_id, details, ip_address) 
                                       VALUES (?, ?, ?, 'users', ?, ?, ?)");
        $auditStmt->bind_param("isssis", $newUserId, $username, $logAction, $newUserId, $details, $ip);
        $auditStmt->execute();
        $auditStmt->close();

        // Refresh CSRF token after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['p9_success'] = "Account successfully registered for user '{$username}'! Your password was securely hashed using BCrypt.";
        header('Location: register.php');
        exit;

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            $_SESSION['p9_errors'] = ['Duplicate entry detected at database level. Email or username already exists.'];
        } else {
            error_log("Registration DB Error: " . $e->getMessage());
            $_SESSION['p9_errors'] = ['An unexpected database error occurred while creating your account. Please try again later.'];
        }
        $_SESSION['p9_old'] = $oldData;
        header('Location: register.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 9: Secure User Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="register.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub</span>
            </a>
            <span class="badge">Practical 9 &bull; MySQLi</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="registration-card">
            <div class="form-header">
                <span class="subheading">Database Security &amp; Prepared Statements</span>
                <h1>Secure Account Registration</h1>
                <p>Features MySQLi prepared statement parameter binding, duplicate email/username checks, CSRF protection, and BCrypt password hashing.</p>
            </div>

            <!-- Success Alert -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <div class="alert-content">
                        <h4>&#10004; Registration Successful!</h4>
                        <p><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
                        <p style="margin-top: 10px;">
                            <a href="../Practical 10/login.php" class="btn btn-sm btn-primary">Proceed to Practical 10 Login &rarr;</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Error Alerts -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <div class="alert-content">
                        <h4>&#9888; Registration Errors:</h4>
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form action="register.php" method="POST" class="form-grid" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <!-- Row 1: Username & Full Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Portal Username <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="e.g. avibeladiya" 
                            value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="fullName">Full Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="fullName" 
                            name="fullName" 
                            placeholder="e.g. Avi Beladiya" 
                            value="<?= htmlspecialchars($old['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Row 2: Email & Role -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="e.g. avi@charusat.edu.in" 
                            value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="role">Account Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="student" <?= ($old['role'] ?? 'student') === 'student' ? 'selected' : '' ?>>Student Portal User</option>
                            <option value="faculty" <?= ($old['role'] ?? '') === 'faculty' ? 'selected' : '' ?>>Faculty Member</option>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Enrollment ID (Optional) -->
                <div class="form-group">
                    <label for="studentId">Enrollment Number (If Student)</label>
                    <input 
                        type="text" 
                        id="studentId" 
                        name="studentId" 
                        placeholder="e.g. 25DCE007" 
                        value="<?= htmlspecialchars($old['studentId'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <!-- Row 4: Password & Confirm Password -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Min 8 chars (Upper, Lower, Number, Symbol)" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="confirmPassword" 
                            name="confirmPassword" 
                            placeholder="Re-enter password" 
                            required
                        >
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <a href="../Practical 10/login.php" class="btn btn-outline">Already Registered? Log In</a>
                    <button type="submit" class="btn btn-primary">&#128274; Create Secure Account</button>
                </div>
            </form>
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
