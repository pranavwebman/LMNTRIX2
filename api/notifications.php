<?php
// api/notifications.php
// LMNTrix Notifications API

require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT n.id, n.type, n.content, n.is_read, n.created_at, n.message_id,
               u.username as actor_username, u.display_name as actor_name, u.avatar as actor_avatar
        FROM notifications n
        LEFT JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.id DESC
        LIMIT 30
    ");
    $stmt->execute([$user['id']]);
    $list = $stmt->fetchAll();

    $unreadCount = 0;
    foreach ($list as $item) {
        if (!$item['is_read']) {
            $unreadCount++;
        }
    }

    send_success([
        'notifications' => $list,
        'unread_count' => $unreadCount
    ]);
}

if ($method === 'POST') {
    check_csrf();
    $json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $notificationId = (int)($json['notification_id'] ?? 0);
    $markAll = !empty($json['mark_all']);

    if ($markAll) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        send_success([], 'All notifications marked as read.');
    } elseif ($notificationId) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $user['id']]);
        send_success([], 'Notification marked as read.');
    } else {
        send_error('Invalid request.');
    }
}
