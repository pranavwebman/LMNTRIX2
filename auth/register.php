<?php
// auth/register.php
// LMNTrix User Registration API & Handler

require_once __DIR__ . '/../includes/auth.php';

check_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', 405);
}

check_rate_limit('register_attempt', 5, 60);

$pdo = get_db_connection();

// Check if registration is enabled in group settings
$stmtSettings = $pdo->query("SELECT setting_value FROM group_settings WHERE setting_key = 'registration_enabled'");
$regEnabled = $stmtSettings ? $stmtSettings->fetchColumn() : '1';
if ($regEnabled === '0') {
    send_error('Registration is currently disabled by administrator.');
}

$json = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = strtolower(trim($json['username'] ?? ''));
$displayName = trim($json['display_name'] ?? '');
$email = strtolower(trim($json['email'] ?? ''));
$password = $json['password'] ?? '';
$confirmPassword = $json['confirm_password'] ?? '';

if (empty($username) || empty($displayName) || empty($email) || empty($password)) {
    send_error('All fields are required.');
}

if ($password !== $confirmPassword) {
    send_error('Passwords do not match.');
}

if (strlen($password) < 6) {
    send_error('Password must be at least 6 characters long.');
}

if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    send_error('Username must be 3-20 alphanumeric characters or underscores.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_error('Please enter a valid email address.');
}

// Check existing user
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmtCheck->execute([$username, $email]);
if ($stmtCheck->fetch()) {
    send_error('Username or Email is already registered.');
}

// Random hue generation for avatar theme
$randomHue = rand(120, 260);

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmtInsert = $pdo->prepare("
    INSERT INTO users (username, display_name, email, password_hash, role, accent_hue)
    VALUES (?, ?, ?, ?, 'member', ?)
");
$stmtInsert->execute([$username, $displayName, $email, $passwordHash, $randomHue]);
$newUserId = (int)$pdo->lastInsertId();

// Set up default user presence
try {
    $stmtPresence = $pdo->prepare("INSERT INTO user_presence (user_id, status) VALUES (?, 'online')");
    $stmtPresence->execute([$newUserId]);
} catch (Exception $e) {}

// Session login
session_regenerate_id(true);
$_SESSION['user_id'] = $newUserId;

send_success([
    'user' => [
        'id' => $newUserId,
        'username' => $username,
        'display_name' => $displayName,
        'role' => 'member'
    ],
    'csrf_token' => get_csrf_token()
], 'Registration successful. Welcome to LMNTrix!');
