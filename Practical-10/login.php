<?php
/**
 * StudentHub - Practical 10: Secure User Authentication (Login)
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Redirect if already logged in
if (isAuthenticated()) {
    $role = $_SESSION['role'] ?? 'student';
    if ($role === 'admin') {
        header('Location: admin.php');
        exit;
    } else {
        header('Location: student.php');
        exit;
    }
}

// Generate CSRF token if needed
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$errorMsg = null;
$successMsg = null;
$warningMsg = null;

if (isset($_GET['logout'])) {
    $successMsg = 'You have been securely logged out. Session destroyed.';
}
if (isset($_GET['error']) && $_GET['error'] === 'timeout') {
    $warningMsg = 'Your session expired due to 15 minutes of inactivity. Please log in again.';
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $warningMsg = 'Please log in to access protected portal pages.';
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['login_csrf'], $csrf)) {
        $errorMsg = 'Security token invalid or expired. Please refresh and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $errorMsg = 'Please provide both your username/email and password.';
        } elseif ($pdo === null) {
            $errorMsg = 'Database connection is currently unavailable. Please verify XAMPP MySQL.';
        } else {
            try {
                // Fetch user by email or username using prepared statement
                $stmt = $pdo->prepare("SELECT id, username, email, password, role, status FROM users WHERE email = :id OR username = :id LIMIT 1");
                $stmt->execute([':id' => $identifier]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $errorMsg = 'Your portal account is deactivated. Please contact the university administrator.';
                    } else {
                        // Prevent Session Fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id']       = (int)$user['id'];
                        $_SESSION['username']      = $user['username'];
                        $_SESSION['email']         = $user['email'];
                        $_SESSION['role']          = $user['role'];
                        $_SESSION['last_activity'] = time();

                        // Record audit log
                        try {
                            $auditStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, actor_username, action, entity_type, entity_id, details, ip_address) 
                                                        VALUES (:uid, :uname, 'USER_LOGIN', 'users', :eid, 'User authenticated via password_verify()', :ip)");
                            $auditStmt->execute([
                                ':uid'   => $user['id'],
                                ':uname' => $user['username'],
                                ':eid'   => $user['id'],
                                ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                            ]);
                        } catch (Exception $e) {
                            // Non-blocking log failure
                        }

                        // Role-based redirection
                        if ($user['role'] === 'admin') {
                            header('Location: admin.php');
                            exit;
                        } else {
                            header('Location: student.php');
                            exit;
                        }
                    }
                } else {
                    // Safe non-revealing error message to prevent account enumeration
                    $errorMsg = 'Invalid username/email or password.';
                }
            } catch (PDOException $e) {
                error_log("Login Query Error: " . $e->getMessage());
                $errorMsg = 'An error occurred during authentication. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 10: Secure Portal Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <a href="login.php" class="logo">
                <span class="logo-icon">&#127891;</span>
                <span>Student Hub</span>
            </a>
            <span class="badge">Practical 10 &bull; RBAC</span>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="login-card">
            <div class="page-header">
                <span class="subheading">Authentication &amp; Session Management</span>
                <h1>StudentHub Login</h1>
                <p>Role-based authentication powered by <code>password_verify()</code> and session regeneration.</p>
            </div>

            <!-- Demo Credentials Helper -->
            <div class="demo-credentials-box">
                <strong>University Demo Credentials:</strong>
                <ul style="margin-left: 18px; margin-top: 6px;">
                    <li><strong>Admin Account:</strong> <code>admin@studenthub.edu</code> / <code>Admin@123</code></li>
                    <li><strong>Student Account:</strong> <code>25dce007@charusat.edu.in</code> / <code>Student@123</code></li>
                </ul>
            </div>

            <!-- Feedback Alerts -->
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success">&#10004; <?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>

            <?php if (!empty($warningMsg)): ?>
                <div class="alert alert-warning">&#9888; <?= htmlspecialchars($warningMsg) ?></div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error">&#10060; <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf']) ?>">

                <div class="form-group">
                    <label for="identifier">Username or Email Address</label>
                    <input 
                        type="text" 
                        id="identifier" 
                        name="identifier" 
                        placeholder="e.g. admin@studenthub.edu" 
                        value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                        required 
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter password" 
                        required 
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">&#128274; Authenticate &amp; Sign In</button>

                <div style="text-align: center; margin-top: 20px; font-size: 13px;">
                    <a href="../Practical 9/register.php" style="color: var(--brand-primary); text-decoration: none; font-weight: 600;">
                        Don't have an account? Register via Practical 9 &rarr;
                    </a>
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
