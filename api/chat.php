<?php
// api/chat.php
// LMNTrix Chat Messages API (Get, Send, Edit, Delete)

require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Message fetching with pagination
    $beforeId = isset($_GET['before_id']) ? (int)$_GET['before_id'] : null;
    $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : null;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;

    $query = "
        SELECT m.id, m.user_id, m.reply_to_id, m.message, m.attachment_id,
               m.is_edited, m.is_deleted, m.is_announcement, m.created_at, m.updated_at,
               u.username, u.display_name, u.avatar, u.role, u.accent_hue,
               a.file_path, a.original_name, a.mime_type, a.file_size,
               rm.message as reply_message, ru.display_name as reply_user_name
        FROM messages m
        JOIN users u ON m.user_id = u.id
        LEFT JOIN attachments a ON m.attachment_id = a.id
        LEFT JOIN messages rm ON m.reply_to_id = rm.id
        LEFT JOIN users ru ON rm.user_id = ru.id
        WHERE 1=1
    ";
    $params = [];

    if ($beforeId) {
        $query .= " AND m.id < ?";
        $params[] = $beforeId;
    }
    if ($sinceId) {
        $query .= " AND m.id > ?";
        $params[] = $sinceId;
    }

    $query .= " ORDER BY m.id DESC LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rawMessages = $stmt->fetchAll();

    // Fetch reactions for these messages
    $messageIds = array_column($rawMessages, 'id');
    $reactionsByMsg = [];
    if (!empty($messageIds)) {
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $stmtReact = $pdo->prepare("
            SELECT mr.message_id, mr.emoji, mr.user_id, u.username, u.display_name
            FROM message_reactions mr
            JOIN users u ON mr.user_id = u.id
            WHERE mr.message_id IN ($placeholders)
        ");
        $stmtReact->execute($messageIds);
        $reactList = $stmtReact->fetchAll();
        foreach ($reactList as $r) {
            $msgId = $r['message_id'];
            if (!isset($reactionsByMsg[$msgId])) {
                $reactionsByMsg[$msgId] = [];
            }
            $emoji = $r['emoji'];
            if (!isset($reactionsByMsg[$msgId][$emoji])) {
                $reactionsByMsg[$msgId][$emoji] = [
                    'emoji' => $emoji,
                    'count' => 0,
                    'users' => [],
                    'reacted' => false
                ];
            }
            $reactionsByMsg[$msgId][$emoji]['count']++;
            $reactionsByMsg[$msgId][$emoji]['users'][] = $r['display_name'];
            if ((int)$r['user_id'] === $user['id']) {
                $reactionsByMsg[$msgId][$emoji]['reacted'] = true;
            }
        }
    }

    // Format output
    $messages = [];
    foreach ($rawMessages as $m) {
        $msgId = $m['id'];
        $reacts = isset($reactionsByMsg[$msgId]) ? array_values($reactionsByMsg[$msgId]) : [];
        $messages[] = [
            'id' => (int)$m['id'],
            'user_id' => (int)$m['user_id'],
            'username' => $m['username'],
            'display_name' => $m['display_name'],
            'avatar' => $m['avatar'],
            'role' => $m['role'],
            'accent_hue' => (int)$m['accent_hue'],
            'reply_to' => $m['reply_to_id'] ? [
                'id' => (int)$m['reply_to_id'],
                'user_name' => $m['reply_user_name'],
                'snippet' => mb_strimwidth($m['reply_message'] ?? '', 0, 40, '...')
            ] : null,
            'message' => $m['is_deleted'] ? 'This message was deleted.' : $m['message'],
            'attachment' => $m['attachment_id'] ? [
                'id' => (int)$m['attachment_id'],
                'file_path' => $m['file_path'],
                'original_name' => $m['original_name'],
                'mime_type' => $m['mime_type'],
                'file_size' => (int)$m['file_size']
            ] : null,
            'is_edited' => (bool)$m['is_edited'],
            'is_deleted' => (bool)$m['is_deleted'],
            'is_announcement' => (bool)$m['is_announcement'],
            'reactions' => $reacts,
            'created_at' => $m['created_at'],
            'updated_at' => $m['updated_at']
        ];
    }

    // Return chronological order for UI display
    $messages = array_reverse($messages);

    // Fetch pinned messages
    $stmtPinned = $pdo->query("
        SELECT pm.id as pin_id, m.id as message_id, m.message, u.display_name
        FROM pinned_messages pm
        JOIN messages m ON pm.message_id = m.id
        JOIN users u ON m.user_id = u.id
        ORDER BY pm.id DESC
    ");
    $pinned = $stmtPinned ? $stmtPinned->fetchAll() : [];

    send_success([
        'messages' => $messages,
        'pinned_messages' => $pinned
    ]);
}

if ($method === 'POST') {
    check_csrf();
    if ($user['is_muted'] || $user['is_banned']) {
        send_error('You are currently muted or banned from sending messages.', 403);
    }

    check_rate_limit('send_message', 20, 30);

    $json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $messageText = trim($json['message'] ?? '');
    $replyToId = !empty($json['reply_to_id']) ? (int)$json['reply_to_id'] : null;
    $attachmentId = !empty($json['attachment_id']) ? (int)$json['attachment_id'] : null;
    $isAnnouncement = !empty($json['is_announcement']) && in_array($user['role'], ['admin', 'owner'], true);

    if (empty($messageText) && !$attachmentId) {
        send_error('Message content or attachment is required.');
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO messages (user_id, reply_to_id, message, attachment_id, is_announcement)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$user['id'], $replyToId, $messageText, $attachmentId, $isAnnouncement ? 1 : 0]);
    $newMessageId = (int)$pdo->lastInsertId();

    // Mentions processing (@username)
    preg_match_all('/@([a-zA-Z0-9_]+)/', $messageText, $matches);
    if (!empty($matches[1])) {
        $mentionedUsernames = array_unique($matches[1]);
        $placeholders = implode(',', array_fill(0, count($mentionedUsernames), '?'));
        $stmtMentions = $pdo->prepare("SELECT id FROM users WHERE username IN ($placeholders)");
        $stmtMentions->execute($mentionedUsernames);
        $mentionedIds = $stmtMentions->fetchAll(PDO::FETCH_COLUMN);

        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, type, actor_id, message_id, content)
            VALUES (?, 'mention', ?, ?, ?)
        ");
        foreach ($mentionedIds as $mUserId) {
            if ($mUserId != $user['id']) {
                $stmtNotif->execute([
                    $mUserId,
                    $user['id'],
                    $newMessageId,
                    "{$user['display_name']} mentioned you in chat."
                ]);
            }
        }
    }

    // Reply notification
    if ($replyToId) {
        $stmtReplyUser = $pdo->prepare("SELECT user_id FROM messages WHERE id = ?");
        $stmtReplyUser->execute([$replyToId]);
        $targetReplyUserId = $stmtReplyUser->fetchColumn();
        if ($targetReplyUserId && $targetReplyUserId != $user['id']) {
            $stmtNotifReply = $pdo->prepare("
                INSERT INTO notifications (user_id, type, actor_id, message_id, content)
                VALUES (?, 'reply', ?, ?, ?)
            ");
            $stmtNotifReply->execute([
                $targetReplyUserId,
                $user['id'],
                $newMessageId,
                "{$user['display_name']} replied to your message."
            ]);
        }
    }

    send_success(['message_id' => $newMessageId], 'Message sent successfully.');
}

if ($method === 'PUT') {
    check_csrf();
    $json = json_decode(file_get_contents('php://input'), true);
    $messageId = (int)($json['message_id'] ?? 0);
    $newText = trim($json['message'] ?? '');

    if (!$messageId || empty($newText)) {
        send_error('Message ID and updated text are required.');
    }

    $stmtCheck = $pdo->prepare("SELECT user_id, is_deleted FROM messages WHERE id = ?");
    $stmtCheck->execute([$messageId]);
    $msg = $stmtCheck->fetch();

    if (!$msg || $msg['is_deleted']) {
        send_error('Message not found or deleted.', 44);
    }

    if ($msg['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner'], true)) {
        send_error('You can only edit your own messages.', 403);
    }

    $stmtUpdate = $pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ?");
    $stmtUpdate->execute([$newText, $messageId]);

    send_success([], 'Message updated successfully.');
}

if ($method === 'DELETE') {
    check_csrf();
    $json = json_decode(file_get_contents('php://input'), true) ?? $_GET;
    $messageId = (int)($json['message_id'] ?? 0);

    if (!$messageId) {
        send_error('Message ID required.');
    }

    $stmtCheck = $pdo->prepare("SELECT user_id FROM messages WHERE id = ?");
    $stmtCheck->execute([$messageId]);
    $msg = $stmtCheck->fetch();

    if (!$msg) {
        send_error('Message not found.', 404);
    }

    if ($msg['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'owner'], true)) {
        send_error('You do not have permission to delete this message.', 403);
    }

    $stmtDelete = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
    $stmtDelete->execute([$messageId]);

    send_success([], 'Message deleted.');
}
