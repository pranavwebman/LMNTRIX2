<?php
// api/admin.php
// LMNTrix Hidden Administration Engine API

require_once __DIR__ . '/../includes/auth.php';

$admin = require_admin();
$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'dashboard';

    if ($action === 'dashboard') {
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $onlineUsers = $pdo->query("SELECT COUNT(*) FROM user_presence WHERE status = 'online'")->fetchColumn();
        $totalMessages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_deleted = 0")->fetchColumn();

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $messagesToday = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_deleted = 0 AND date(created_at) = date('now')")->fetchColumn();
        } else {
            $messagesToday = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_deleted = 0 AND DATE(created_at) = CURDATE()")->fetchColumn();
        }

        $totalImages = $pdo->query("SELECT COUNT(*) FROM attachments")->fetchColumn();

        // Settings
        $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM group_settings");
        $settings = $stmtSettings ? $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        // Recent security / admin audit logs
        $stmtLogs = $pdo->query("
            SELECT al.id, al.action, al.target_type, al.target_id, al.details, al.ip_address, al.created_at,
                   u.username as admin_username, u.display_name as admin_name
            FROM admin_logs al
            JOIN users u ON al.admin_id = u.id
            ORDER BY al.id DESC
            LIMIT 15
        ");
        $logs = $stmtLogs ? $stmtLogs->fetchAll() : [];

        send_success([
            'stats' => [
                'total_users' => (int)$totalUsers,
                'online_users' => (int)$onlineUsers,
                'total_messages' => (int)$totalMessages,
                'messages_today' => (int)$messagesToday,
                'total_images' => (int)$totalImages,
                'db_driver' => $driver
            ],
            'settings' => $settings,
            'audit_logs' => $logs
        ]);
    }

    if ($action === 'users') {
        $stmt = $pdo->query("
            SELECT u.id, u.username, u.display_name, u.email, u.role, u.is_disabled, u.is_banned, u.is_muted, u.created_at,
                   up.status as presence_status, up.last_seen,
                   (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND is_deleted = 0) as message_count
            FROM users u
            LEFT JOIN user_presence up ON u.id = up.user_id
            ORDER BY u.id ASC
        ");
        send_success(['users' => $stmt->fetchAll()]);
    }

    send_error('Invalid admin action.');
}

if ($method === 'POST') {
    check_csrf();
    $json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $json['action'] ?? '';

    if ($action === 'user_update') {
        $targetUserId = (int)($json['user_id'] ?? 0);
        $role = $json['role'] ?? 'member';
        $isDisabled = !empty($json['is_disabled']) ? 1 : 0;
        $isBanned = !empty($json['is_banned']) ? 1 : 0;
        $isMuted = !empty($json['is_muted']) ? 1 : 0;
        $displayName = trim($json['display_name'] ?? '');

        if (!$targetUserId) {
            send_error('Target user ID required.');
        }

        // Prevent admin demoting owner or themselves
        if ($targetUserId === $admin['id'] && $role !== $admin['role']) {
            send_error('You cannot change your own role.');
        }

        $stmt = $pdo->prepare("
            UPDATE users
            SET role = ?, is_disabled = ?, is_banned = ?, is_muted = ?, display_name = ?
            WHERE id = ?
        ");
        $stmt->execute([$role, $isDisabled, $isBanned, $isMuted, $displayName, $targetUserId]);

        log_admin_action($admin['id'], 'user_update', 'user', $targetUserId, "Updated role to $role, disabled=$isDisabled, banned=$isBanned, muted=$isMuted");
        send_success([], 'User updated successfully.');
    }

    if ($action === 'reset_password') {
        $targetUserId = (int)($json['user_id'] ?? 0);
        $newPassword = $json['new_password'] ?? '';

        if (!$targetUserId || strlen($newPassword) < 6) {
            send_error('Valid User ID and new password (min 6 chars) required.');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $targetUserId]);

        log_admin_action($admin['id'], 'reset_password', 'user', $targetUserId, 'Reset user password');
        send_success([], 'User password reset successfully.');
    }

    if ($action === 'delete_message') {
        $msgId = (int)($json['message_id'] ?? 0);
        if (!$msgId) send_error('Message ID required.');

        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$msgId]);

        log_admin_action($admin['id'], 'delete_message', 'message', $msgId, 'Deleted message');
        send_success([], 'Message deleted by administrator.');
    }

    if ($action === 'pin_message') {
        $msgId = (int)($json['message_id'] ?? 0);
        if (!$msgId) send_error('Message ID required.');

        try {
            $stmt = $pdo->prepare("INSERT INTO pinned_messages (message_id, pinned_by) VALUES (?, ?)");
            $stmt->execute([$msgId, $admin['id']]);
        } catch (Exception $e) {}

        log_admin_action($admin['id'], 'pin_message', 'message', $msgId, 'Pinned message');
        send_success([], 'Message pinned.');
    }

    if ($action === 'unpin_message') {
        $msgId = (int)($json['message_id'] ?? 0);
        if (!$msgId) send_error('Message ID required.');

        $stmt = $pdo->prepare("DELETE FROM pinned_messages WHERE message_id = ?");
        $stmt->execute([$msgId]);

        log_admin_action($admin['id'], 'unpin_message', 'message', $msgId, 'Unpinned message');
        send_success([], 'Message unpinned.');
    }

    if ($action === 'update_settings') {
        $settings = $json['settings'] ?? [];
        $stmt = $pdo->prepare("INSERT INTO group_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?");

        foreach ($settings as $k => $v) {
            try {
                $stmt->execute([$k, (string)$v, (string)$v]);
            } catch (Exception $e) {
                $stmtMysql = $pdo->prepare("INSERT INTO group_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmtMysql->execute([$k, (string)$v, (string)$v]);
            }
        }

        log_admin_action($admin['id'], 'update_settings', 'system', null, 'Updated system settings');
        send_success([], 'Group settings updated.');
    }

    if ($action === 'send_announcement') {
        $content = trim($json['content'] ?? '');
        if (empty($content)) send_error('Announcement content cannot be empty.');

        $stmtMsg = $pdo->prepare("INSERT INTO messages (user_id, message, is_announcement) VALUES (?, ?, 1)");
        $stmtMsg->execute([$admin['id'], $content]);
        $msgId = $pdo->lastInsertId();

        // Broadcast notification to all active users
        $users = $pdo->query("SELECT id FROM users WHERE is_disabled = 0")->fetchAll(PDO::FETCH_COLUMN);
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, type, actor_id, message_id, content) VALUES (?, 'announcement', ?, ?, ?)");
        foreach ($users as $uId) {
            $stmtNotif->execute([$uId, $admin['id'], $msgId, "ANNOUNCEMENT: {$content}"]);
        }

        log_admin_action($admin['id'], 'send_announcement', 'message', $msgId, "Sent announcement: $content");
        send_success([], 'Announcement broadcasted successfully.');
    }

    send_error('Invalid action.');
}
