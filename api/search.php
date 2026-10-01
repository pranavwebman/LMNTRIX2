<?php
// api/search.php
// LMNTrix Full-Text Search API (Messages, Users, Dates)

require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$pdo = get_db_connection();

$q = trim($_GET['q'] ?? '');

if (empty($q)) {
    send_success(['results' => []]);
}

$likeParam = '%' . $q . '%';

// Search Messages
$stmtMsg = $pdo->prepare("
    SELECT m.id, m.message, m.created_at, u.username, u.display_name, u.avatar, u.accent_hue
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.is_deleted = 0 AND (m.message LIKE ? OR u.username LIKE ? OR u.display_name LIKE ?)
    ORDER BY m.id DESC
    LIMIT 20
");
$stmtMsg->execute([$likeParam, $likeParam, $likeParam]);
$messages = $stmtMsg->fetchAll();

// Search Users
$stmtUsers = $pdo->prepare("
    SELECT id, username, display_name, avatar, accent_hue, status, role
    FROM users
    WHERE is_disabled = 0 AND is_banned = 0 AND (username LIKE ? OR display_name LIKE ? OR bio LIKE ?)
    LIMIT 10
");
$stmtUsers->execute([$likeParam, $likeParam, $likeParam]);
$users = $stmtUsers->fetchAll();

send_success([
    'messages' => $messages,
    'users' => $users
]);
