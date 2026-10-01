<?php
// api/presence.php
// LMNTrix Online Presence & Typing Indicator API

require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$pdo = get_db_connection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    check_csrf();
    $json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $status = $json['status'] ?? 'online'; // 'online', 'away', 'offline'
    $isTyping = !empty($json['is_typing']);

    // Update presence
    try {
        $stmtPresence = $pdo->prepare("
            INSERT INTO user_presence (user_id, status, last_seen)
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT(user_id) DO UPDATE SET status = ?, last_seen = CURRENT_TIMESTAMP
        ");
        $stmtPresence->execute([$user['id'], $status, $status]);
    } catch (Exception $e) {
        $stmtMysql = $pdo->prepare("
            INSERT INTO user_presence (user_id, status) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE status = ?, last_seen = CURRENT_TIMESTAMP
        ");
        $stmtMysql->execute([$user['id'], $status, $status]);
    }

    // Update typing status
    if ($isTyping) {
        try {
            $stmtType = $pdo->prepare("
                INSERT INTO typing_status (user_id, last_typed) VALUES (?, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id) DO UPDATE SET last_typed = CURRENT_TIMESTAMP
            ");
            $stmtType->execute([$user['id']]);
        } catch (Exception $e) {
            $stmtTypeMysql = $pdo->prepare("
                INSERT INTO typing_status (user_id) VALUES (?)
                ON DUPLICATE KEY UPDATE last_typed = CURRENT_TIMESTAMP
            ");
            $stmtTypeMysql->execute([$user['id']]);
        }
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM typing_status WHERE user_id = ?");
        $stmtDel->execute([$user['id']]);
    }

    send_success([], 'Presence updated.');
}

if ($method === 'GET') {
    // Return all users presence and active typing users
    $stmtUsers = $pdo->query("
        SELECT u.id, u.username, u.display_name, u.avatar, u.role, u.accent_hue, u.status as bio_status,
               COALESCE(up.status, 'offline') as presence_status, up.last_seen
        FROM users u
        LEFT JOIN user_presence up ON u.id = up.user_id
        WHERE u.is_disabled = 0 AND u.is_banned = 0
        ORDER BY u.display_name ASC
    ");
    $users = $stmtUsers->fetchAll();

    // Query active typing users in the last 6 seconds
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $stmtTyping = $pdo->prepare("
            SELECT u.id, u.username, u.display_name
            FROM typing_status ts
            JOIN users u ON ts.user_id = u.id
            WHERE ts.user_id != ? AND datetime(ts.last_typed) >= datetime('now', '-6 seconds')
        ");
    } else {
        $stmtTyping = $pdo->prepare("
            SELECT u.id, u.username, u.display_name
            FROM typing_status ts
            JOIN users u ON ts.user_id = u.id
            WHERE ts.user_id != ? AND ts.last_typed >= NOW() - INTERVAL 6 SECOND
        ");
    }
    $stmtTyping->execute([$user['id']]);
    $typingUsers = $stmtTyping->fetchAll();

    send_success([
        'users' => $users,
        'typing_users' => $typingUsers,
        'server_time' => date('Y-m-d H:i:s')
    ]);
}
