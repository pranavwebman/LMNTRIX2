<?php
// api/profile.php
// LMNTrix User Profile Management API

require_once __DIR__ . '/../includes/auth.php';

check_csrf();
$user = require_login();
$pdo = get_db_connection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $targetUsername = $_GET['username'] ?? null;
    if ($targetUsername) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.display_name, u.avatar, u.status, u.bio, u.role, u.accent_hue, u.created_at,
                   up.status as presence_status, up.last_seen,
                   (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND is_deleted = 0) as message_count
            FROM users u
            LEFT JOIN user_presence up ON u.id = up.user_id
            WHERE u.username = ?
        ");
        $stmt->execute([$targetUsername]);
        $profile = $stmt->fetch();
        if (!$profile) {
            send_error('User profile not found.', 404);
        }
        send_success(['profile' => $profile]);
    } else {
        // Return current user full profile
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.display_name, u.email, u.avatar, u.status, u.bio, u.role, u.accent_hue, u.theme_pref, u.created_at,
                   up.status as presence_status, up.last_seen,
                   (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND is_deleted = 0) as message_count
            FROM users u
            LEFT JOIN user_presence up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();
        send_success(['profile' => $profile]);
    }
}

if ($method === 'POST' || $method === 'PUT') {
    $json = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $action = $json['action'] ?? 'update_profile';

    if ($action === 'change_password') {
        $currentPassword = $json['current_password'] ?? '';
        $newPassword = $json['new_password'] ?? '';
        $confirmPassword = $json['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            send_error('Please fill in both current and new passwords.');
        }

        if ($newPassword !== $confirmPassword) {
            send_error('New passwords do not match.');
        }

        if (strlen($newPassword) < 6) {
            send_error('New password must be at least 6 characters.');
        }

        $stmtPass = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmtPass->execute([$user['id']]);
        $hash = $stmtPass->fetchColumn();

        if (!password_verify($currentPassword, $hash)) {
            send_error('Current password is incorrect.');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updatePass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updatePass->execute([$newHash, $user['id']]);

        send_success([], 'Password updated successfully.');
    }

    $displayName = isset($json['display_name']) ? trim($json['display_name']) : $user['display_name'];
    $status = isset($json['status']) ? trim($json['status']) : $user['status'];
    $bio = isset($json['bio']) ? trim($json['bio']) : $user['bio'];
    $accentHue = isset($json['accent_hue']) ? (int)$json['accent_hue'] : $user['accent_hue'];
    $themePref = isset($json['theme_pref']) ? trim($json['theme_pref']) : $user['theme_pref'];

    if (empty($displayName)) {
        send_error('Display name cannot be empty.');
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE users
        SET display_name = ?, status = ?, bio = ?, accent_hue = ?, theme_pref = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$displayName, $status, $bio, $accentHue, $themePref, $user['id']]);

    send_success(['user' => get_current_user_data()], 'Profile updated successfully.');
}
