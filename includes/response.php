<?php
// includes/response.php
// LMNTrix Standard API Helper

function send_json($data, int $statusCode = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_error(string $message, int $statusCode = 400, array $extra = []): void {
    send_json(array_merge([
        'success' => false,
        'error' => $message
    ], $extra), $statusCode);
}

function send_success(array $data = [], string $message = ''): void {
    $response = ['success' => true];
    if ($message !== '') {
        $response['message'] = $message;
    }
    send_json(array_merge($response, $data), 200);
}

function sanitize_output(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
