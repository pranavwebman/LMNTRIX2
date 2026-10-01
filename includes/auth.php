<?php
// includes/auth.php
// LMNTrix Session & User Authorization Helper

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/response.php';

function get_current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function get_current_user_data(): ?array {
    $userId = get_current_user_id();
    if (!$userId) {
        return null;
    }
    static $cachedUser = null;
    if ($cachedUser !== null && $cachedUser['id'] === $userId) {
        return $cachedUser;
    }

    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.display_name, u.email, u.avatar, u.status, u.bio,
               u.role, u.accent_hue, u.theme_pref, u.is_disabled, u.is_banned, u.is_muted, u.created_at
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user && ($user['is_disabled'] || $user['is_banned'])) {
        session_unset();
        session_destroy();
        return null;
    }

    $cachedUser = $user ?: null;
    return $cachedUser;
}

function require_login(): array {
    $user = get_current_user_data();
    if (!$user) {
        send_error('Authentication required. Please log in.', 401);
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if (!in_array($user['role'], ['admin', 'owner'], true)) {
        send_error('Access denied. Administrator privileges required.', 403);
    }
    return $user;
}

function check_rate_limit(string $key, int $maxRequests = 30, int $windowSeconds = 60): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $now = time();
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [];
    }

    // Filter out old timestamps
    $_SESSION['rate_limits'][$key] = array_filter(
        $_SESSION['rate_limits'][$key],
        fn($ts) => $ts > ($now - $windowSeconds)
    );

    if (count($_SESSION['rate_limits'][$key]) >= $maxRequests) {
        send_error('Rate limit exceeded. Please wait a moment before trying again.', 429);
    }

    $_SESSION['rate_limits'][$key][] = $now;
}

function log_admin_action(int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void {
    $pdo = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$adminId, $action, $targetType, $targetId, $details, $ip]);
}
