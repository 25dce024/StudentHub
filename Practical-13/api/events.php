<?php
/**
 * StudentHub - Practical 13: Events JSON REST API
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(true, 200, 'Preflight OK');
}

try {
    $pdo = getApiPdo();
} catch (Exception $e) {
    sendJsonResponse(false, 500, 'Database error', null, [$e->getMessage()]);
}

$method = $_SERVER['REQUEST_METHOD'];
$rawBody = file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true) ?? [];
$input = array_merge($_POST, $jsonBody);

$overrideMethod = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $input['_method'] ?? $input['action'] ?? null;
if ($overrideMethod) {
    $overrideUpper = strtoupper($overrideMethod);
    if (in_array($overrideUpper, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
        $method = $overrideUpper;
    }
}

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $ev = $stmt->fetch();
        if ($ev) {
            sendJsonResponse(true, 200, "Event retrieved", $ev);
        } else {
            sendJsonResponse(false, 404, "Event #{$id} not found");
        }
    } else {
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(title LIKE :s1 OR venue LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }
        if (!empty($status) && $status !== 'ALL') {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $stmt = $pdo->prepare("SELECT * FROM events {$whereSql} ORDER BY event_date ASC");
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        sendJsonResponse(true, 200, "Found " . count($events) . " events", [
            'count'  => count($events),
            'events' => $events
        ]);
    }
}
elseif ($method === 'POST') {
    $title     = trim(strip_tags($input['title'] ?? ''));
    $category  = trim($input['category'] ?? 'Technical');
    $eventDate = trim($input['event_date'] ?? '');
    $eventTime = trim($input['event_time'] ?? '10:00:00');
    $venue     = trim(strip_tags($input['venue'] ?? ''));
    $capacity  = max(1, (int)($input['capacity'] ?? 100));
    $status    = trim($input['status'] ?? 'Open');
    $desc      = trim(strip_tags($input['description'] ?? ''));

    if (empty($title) || empty($eventDate) || empty($venue)) {
        sendJsonResponse(false, 422, 'Title, date, and venue are required.');
    }

    $stmt = $pdo->prepare("INSERT INTO events (title, category, event_date, event_time, venue, description, capacity, status, poster)
                           VALUES (:t, :c, :ed, :et, :v, :d, :cap, :s, 'default_poster.png')");
    $stmt->execute([
        ':t'   => $title,
        ':c'   => $category,
        ':ed'  => $eventDate,
        ':et'  => $eventTime,
        ':v'   => $venue,
        ':d'   => $desc,
        ':cap' => $capacity,
        ':s'   => $status
    ]);
    $newId = (int)$pdo->lastInsertId();
    $fetchStmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
    $fetchStmt->execute([':id' => $newId]);
    sendJsonResponse(true, 201, "Event created successfully", $fetchStmt->fetch());
}
elseif ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) sendJsonResponse(false, 400, 'Valid event ID required');
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
    $stmt->execute([':id' => $id]);
    sendJsonResponse(true, 200, "Event #{$id} deleted successfully");
}
else {
    sendJsonResponse(false, 405, "Method {$method} not allowed.");
}
