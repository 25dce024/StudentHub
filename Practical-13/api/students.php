<?php
/**
 * StudentHub - Practical 13: Students JSON REST API
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/db.php';

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(true, 200, 'Preflight OK');
}

try {
    $pdo = getApiPdo();
} catch (Exception $e) {
    sendJsonResponse(false, 500, 'Database connection error', null, [$e->getMessage()]);
}

$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming payload
$rawBody = file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true) ?? [];
$input = array_merge($_POST, $jsonBody);

// Allow method override via header or body (useful for older clients)
$overrideMethod = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $input['_method'] ?? $input['action'] ?? null;
if ($overrideMethod) {
    $overrideUpper = strtoupper($overrideMethod);
    if (in_array($overrideUpper, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
        $method = $overrideUpper;
    }
}

// --------------------------------------------------------------------------
// 1. GET Request: List all or retrieve single student
// --------------------------------------------------------------------------
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $student = $stmt->fetch();

        if ($student) {
            sendJsonResponse(true, 200, "Student retrieved successfully", $student);
        } else {
            sendJsonResponse(false, 404, "Student #{$id} not found");
        }
    } else {
        $search = trim($_GET['search'] ?? '');
        $branch = trim($_GET['branch'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(name LIKE :s1 OR email LIKE :s2 OR student_id LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        if (!empty($branch) && $branch !== 'ALL') {
            $where[] = "branch = :branch";
            $params[':branch'] = $branch;
        }

        if (!empty($status) && $status !== 'ALL') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $query = "SELECT * FROM students {$whereSql} ORDER BY id DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll();

        sendJsonResponse(true, 200, "Found " . count($students) . " students", [
            'count'    => count($students),
            'students' => $students
        ]);
    }
}

// --------------------------------------------------------------------------
// 2. POST Request: Add new student
// --------------------------------------------------------------------------
elseif ($method === 'POST') {
    $studentId    = strtoupper(trim(strip_tags($input['student_id'] ?? '')));
    $name         = trim(strip_tags($input['name'] ?? ''));
    $email        = trim($input['email'] ?? '');
    $mobile       = trim($input['mobile'] ?? '');
    $course       = trim(strip_tags($input['course'] ?? 'Computer Engineering'));
    $branch       = trim($input['branch'] ?? 'CE');
    $semester     = (int)($input['semester'] ?? 3);
    $academicYear = trim($input['academic_year'] ?? '2nd Year');
    $cgpa         = (float)($input['cgpa'] ?? 8.5);
    $status       = trim($input['status'] ?? 'active');
    $address      = trim(strip_tags($input['address'] ?? ''));

    $errors = [];

    if (empty($studentId) || !preg_match('/^[0-9]{2}[A-Z]{2,4}[0-9]{3}$/', $studentId)) {
        $errors[] = 'Enrollment Number must follow university pattern (e.g. 25DCE050).';
    }

    if (empty($name) || !preg_match('/^[a-zA-Z\s]{3,50}$/', $name)) {
        $errors[] = 'Student name must be 3-50 alphabetic characters.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errors[] = 'Valid 10-digit mobile number required starting with 6-9.';
    }

    if (!empty($errors)) {
        sendJsonResponse(false, 422, 'Validation errors occurred', null, $errors);
    }

    // Check duplicate student_id or email
    $dupStmt = $pdo->prepare("SELECT id FROM students WHERE student_id = :sid OR email = :email LIMIT 1");
    $dupStmt->execute([':sid' => $studentId, ':email' => $email]);
    if ($dupStmt->fetch()) {
        sendJsonResponse(false, 409, 'A student with this Enrollment Number or Email already exists.');
    }

    // Insert student
    $insertSql = "INSERT INTO students (student_id, name, email, course, mobile, college, branch, semester, academic_year, cgpa, address, status)
                  VALUES (:sid, :name, :email, :course, :mobile, 'CSPIT / DEPSTAR, CHARUSAT', :branch, :sem, :ayear, :cgpa, :address, :status)";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':sid'     => $studentId,
        ':name'    => $name,
        ':email'   => $email,
        ':course'  => $course,
        ':mobile'  => $mobile,
        ':branch'  => $branch,
        ':sem'     => $semester,
        ':ayear'   => $academicYear,
        ':cgpa'    => $cgpa,
        ':address' => $address,
        ':status'  => $status
    ]);

    $newId = (int)$pdo->lastInsertId();
    $fetchStmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $fetchStmt->execute([':id' => $newId]);
    $newStudent = $fetchStmt->fetch();

    sendJsonResponse(true, 201, "Student '{$name}' created successfully", $newStudent);
}

// --------------------------------------------------------------------------
// 3. PUT Request: Update existing student
// --------------------------------------------------------------------------
elseif ($method === 'PUT') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 400, 'Student ID is required for update operation.');
    }

    // Verify existence
    $checkStmt = $pdo->prepare("SELECT * FROM students WHERE id = :id LIMIT 1");
    $checkStmt->execute([':id' => $id]);
    $existing = $checkStmt->fetch();
    if (!$existing) {
        sendJsonResponse(false, 404, "Student #{$id} does not exist.");
    }

    $studentId    = strtoupper(trim(strip_tags($input['student_id'] ?? $existing['student_id'])));
    $name         = trim(strip_tags($input['name'] ?? $existing['name']));
    $email        = trim($input['email'] ?? $existing['email']);
    $mobile       = trim($input['mobile'] ?? $existing['mobile']);
    $course       = trim(strip_tags($input['course'] ?? $existing['course']));
    $branch       = trim($input['branch'] ?? $existing['branch']);
    $semester     = (int)($input['semester'] ?? $existing['semester']);
    $academicYear = trim($input['academic_year'] ?? $existing['academic_year']);
    $cgpa         = (float)($input['cgpa'] ?? $existing['cgpa']);
    $status       = trim($input['status'] ?? $existing['status']);
    $address      = trim(strip_tags($input['address'] ?? $existing['address']));

    $errors = [];

    if (empty($studentId) || !preg_match('/^[0-9]{2}[A-Z]{2,4}[0-9]{3}$/', $studentId)) {
        $errors[] = 'Invalid enrollment number format.';
    }
    if (empty($name) || !preg_match('/^[a-zA-Z\s]{3,50}$/', $name)) {
        $errors[] = 'Student name must be 3-50 characters.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address required.';
    }
    if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errors[] = 'Valid 10-digit mobile number required.';
    }

    if (!empty($errors)) {
        sendJsonResponse(false, 422, 'Validation errors occurred', null, $errors);
    }

    // Check duplicate email or enrollment belonging to another student
    $dupStmt = $pdo->prepare("SELECT id FROM students WHERE (student_id = :sid OR email = :email) AND id != :id LIMIT 1");
    $dupStmt->execute([':sid' => $studentId, ':email' => $email, ':id' => $id]);
    if ($dupStmt->fetch()) {
        sendJsonResponse(false, 409, 'Another student already holds this Enrollment Number or Email.');
    }

    $updateSql = "UPDATE students SET 
                    student_id = :sid, 
                    name = :name, 
                    email = :email, 
                    course = :course, 
                    mobile = :mobile, 
                    branch = :branch, 
                    semester = :sem, 
                    academic_year = :ayear, 
                    cgpa = :cgpa, 
                    address = :address, 
                    status = :status 
                  WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':sid'     => $studentId,
        ':name'    => $name,
        ':email'   => $email,
        ':course'  => $course,
        ':mobile'  => $mobile,
        ':branch'  => $branch,
        ':sem'     => $semester,
        ':ayear'   => $academicYear,
        ':cgpa'    => $cgpa,
        ':address' => $address,
        ':status'  => $status,
        ':id'      => $id
    ]);

    $fetchStmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $fetchStmt->execute([':id' => $id]);
    $updatedStudent = $fetchStmt->fetch();

    sendJsonResponse(true, 200, "Student '{$name}' updated successfully", $updatedStudent);
}

// --------------------------------------------------------------------------
// 4. DELETE Request: Remove student
// --------------------------------------------------------------------------
elseif ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 400, 'Valid Student ID is required for deletion.');
    }

    $fetchStmt = $pdo->prepare("SELECT name, student_id FROM students WHERE id = :id LIMIT 1");
    $fetchStmt->execute([':id' => $id]);
    $student = $fetchStmt->fetch();

    if (!$student) {
        sendJsonResponse(false, 404, "Student #{$id} was not found or already deleted.");
    }

    $delStmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
    $delStmt->execute([':id' => $id]);

    sendJsonResponse(true, 200, "Student '{$student['name']}' ({$student['student_id']}) deleted successfully", [
        'deleted_id' => $id
    ]);
}

// --------------------------------------------------------------------------
// 5. Unsupported Methods
// --------------------------------------------------------------------------
else {
    sendJsonResponse(false, 405, "HTTP method {$method} is not supported.");
}
