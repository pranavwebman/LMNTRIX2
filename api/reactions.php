<?php
// api/reactions.php
// LMNTrix Message Reactions Toggle API

require_once __DIR__ . '/../includes/auth.php';

check_csrf();
$user = require_login();

if ($user['is_muted'] || $user['is_banned']) {
    send_error('You are currently muted or banned.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', 405);
}

$json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$messageId = (int)($json['message_id'] ?? 0);
$emoji = trim($json['emoji'] ?? '');

if (!$messageId || empty($emoji)) {
    send_error('Message ID and Emoji are required.');
}

$pdo = get_db_connection();

// Verify message exists
$stmtMsg = $pdo->prepare("SELECT user_id, is_deleted FROM messages WHERE id = ?");
$stmtMsg->execute([$messageId]);
$msg = $stmtMsg->fetch();
if (!$msg || $msg['is_deleted']) {
    send_error('Message not found.', 404);
}

// Check existing reaction
$stmtCheck = $pdo->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
$stmtCheck->execute([$messageId, $user['id'], $emoji]);
$existing = $stmtCheck->fetchColumn();

if ($existing) {
    // Remove reaction (toggle off)
    $stmtDel = $pdo->prepare("DELETE FROM message_reactions WHERE id = ?");
    $stmtDel->execute([$existing]);
    $action = 'removed';
} else {
    // Add reaction
    $stmtAdd = $pdo->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
    $stmtAdd->execute([$messageId, $user['id'], $emoji]);
    $action = 'added';

    // Send notification to message author if not self
    if ($msg['user_id'] != $user['id']) {
        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, type, actor_id, message_id, content)
            VALUES (?, 'reaction', ?, ?, ?)
        ");
        $stmtNotif->execute([
            $msg['user_id'],
            $user['id'],
            $messageId,
            "{$user['display_name']} reacted {$emoji} to your message."
        ]);
    }
}

send_success(['action' => $action, 'emoji' => $emoji], "Reaction {$action}.");
