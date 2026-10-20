<?php
/**
 * StudentHub - Practical 14: Audit Trail Logging Utility
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

require_once __DIR__ . '/db.php';

function logAudit(?int $userId, string $username, string $action, string $entityType = 'general', ?int $entityId = null, ?string $details = null): bool {
    global $pdo;
    if ($pdo === null) {
        try {
            $pdo = getAdminPdo();
        } catch (Exception $e) {
            return false;
        }
    }

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $sql = "INSERT INTO audit_logs (user_id, actor_username, action, entity_type, entity_id, details, ip_address)
                VALUES (:uid, :uname, :action, :etype, :eid, :details, :ip)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':uid'     => $userId,
            ':uname'   => $username,
            ':action'  => $action,
            ':etype'   => $entityType,
            ':eid'     => $entityId,
            ':details' => $details,
            ':ip'      => $ip
        ]);
    } catch (Exception $e) {
        error_log("Audit log recording failed: " . $e->getMessage());
        return false;
    }
}
