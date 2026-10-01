<?php
// includes/csrf.php
// LMNTrix CSRF Protection Token Helper

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function check_csrf(): void {
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($requestMethod, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$token && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            $token = $data['csrf_token'] ?? null;
        }
        if (!verify_csrf_token($token)) {
            require_once __DIR__ . '/response.php';
            send_error('Invalid security token (CSRF). Please refresh and try again.', 403);
        }
    }
}
