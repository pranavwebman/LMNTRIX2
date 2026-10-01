<?php
// auth/logout.php
// LMNTrix User Logout API & Handler

require_once __DIR__ . '/../includes/auth.php';

$userId = get_current_user_id();

if ($userId) {
    $pdo = get_db_connection();
    try {
        $stmt = $pdo->prepare("UPDATE user_presence SET status = 'offline', last_seen = CURRENT_TIMESTAMP WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {}
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

send_success([], 'Logged out successfully');
