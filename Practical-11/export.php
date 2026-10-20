<?php
/**
 * StudentHub - Practical 11: Export Students to CSV
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/db.php';

$search     = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$status     = trim($_GET['status'] ?? '');

if ($mysqli === null) {
    die("Database connection failed. Unable to generate CSV export.");
}

$whereClauses = [];
$paramTypes   = '';
$paramValues  = [];

if (!empty($search)) {
    $whereClauses[] = "(name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $searchWildcard = "%{$search}%";
    $paramTypes    .= "sss";
    $paramValues[]  = $searchWildcard;
    $paramValues[]  = $searchWildcard;
    $paramValues[]  = $searchWildcard;
}

if (!empty($department) && $department !== 'ALL') {
    $whereClauses[] = "branch = ?";
    $paramTypes    .= "s";
    $paramValues[]  = $department;
}

if (!empty($status) && $status !== 'ALL') {
    $whereClauses[] = "status = ?";
    $paramTypes    .= "s";
    $paramValues[]  = $status;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
$querySql = "SELECT id, student_id, name, email, mobile, course, branch, semester, academic_year, cgpa, status, address, created_at FROM students {$whereSql} ORDER BY id ASC";

$stmt = $mysqli->prepare($querySql);
if (!empty($paramValues)) {
    $stmt->bind_param($paramTypes, ...$paramValues);
}
$stmt->execute();
$result = $stmt->get_result();

$filename = 'students_export_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Row
fputcsv($output, [
    'System ID',
    'Enrollment Number',
    'Student Name',
    'Email Address',
    'Mobile Number',
    'Degree Course',
    'Department / Branch',
    'Semester',
    'Academic Year',
    'CGPA',
    'Account Status',
    'Address',
    'Registration Date'
]);

// Anti-Formula Injection Sanitizer
function sanitizeCsvValue($val): string {
    $str = (string)($val ?? '');
    if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
        return "'" . $str;
    }
    return $str;
}

while ($row = $result->fetch_assoc()) {
    $sanitized = [
        sanitizeCsvValue($row['id']),
        sanitizeCsvValue($row['student_id']),
        sanitizeCsvValue($row['name']),
        sanitizeCsvValue($row['email']),
        sanitizeCsvValue($row['mobile']),
        sanitizeCsvValue($row['course']),
        sanitizeCsvValue($row['branch']),
        sanitizeCsvValue($row['semester']),
        sanitizeCsvValue($row['academic_year']),
        sanitizeCsvValue(number_format((float)$row['cgpa'], 2)),
        sanitizeCsvValue(ucfirst($row['status'])),
        sanitizeCsvValue($row['address']),
        sanitizeCsvValue($row['created_at'])
    ];
    fputcsv($output, $sanitized);
}

$stmt->close();
fclose($output);
exit;
