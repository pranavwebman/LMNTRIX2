<?php
// auth/login.php
// LMNTrix User Login API & Handler

require_once __DIR__ . '/../includes/auth.php';

check_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', 405);
}

check_rate_limit('login_attempt', 10, 60);

$json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$identity = trim($json['identity'] ?? '');
$password = $json['password'] ?? '';

if (empty($identity) || empty($password)) {
    send_error('Please enter both username/email and password.');
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("
    SELECT id, username, display_name, email, password_hash, role, is_disabled, is_banned
    FROM users
    WHERE username = ? OR email = ?
");
$stmt->execute([$identity, $identity]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    send_error('Invalid username/email or password.');
}

if ($user['is_disabled'] || $user['is_banned']) {
    send_error('Your account has been suspended or disabled.');
}

// Session regeneration to prevent session fixation
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];

// Update online status
$stmtPresence = $pdo->prepare("
    INSERT INTO user_presence (user_id, status, last_seen)
    VALUES (?, 'online', CURRENT_TIMESTAMP)
    ON CONFLICT(user_id) DO UPDATE SET status = 'online', last_seen = CURRENT_TIMESTAMP
");
try {
    $stmtPresence->execute([$user['id']]);
} catch (Exception $e) {
    // MySQL syntax fallback if ON CONFLICT is not supported
    $stmtMysql = $pdo->prepare("
        INSERT INTO user_presence (user_id, status) VALUES (?, 'online')
        ON DUPLICATE KEY UPDATE status = 'online', last_seen = CURRENT_TIMESTAMP
    ");
    $stmtMysql->execute([$user['id']]);
}

send_success([
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'role' => $user['role']
    ],
    'csrf_token' => get_csrf_token()
], 'Login successful');
