<?php
// api/upload.php
// LMNTrix Safe File Upload Handler for Media Attachments

require_once __DIR__ . '/../includes/auth.php';

check_csrf();
$user = require_login();

if ($user['is_muted']) {
    send_error('You are currently muted and cannot upload media.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', 405);
}

check_rate_limit('upload_file', 10, 60);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    send_error('No file was uploaded or upload error occurred.');
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileSize = $file['size'];

$pdo = get_db_connection();
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM group_settings WHERE setting_key IN ('max_upload_size_mb', 'allowed_upload_types')");
$settings = $stmtSettings ? $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) : [];

$maxMb = (int)($settings['max_upload_size_mb'] ?? 5);
$allowedExts = explode(',', $settings['allowed_upload_types'] ?? 'jpg,jpeg,png,gif,webp');

if ($fileSize > ($maxMb * 1024 * 1024)) {
    send_error("File size exceeds maximum limit of {$maxMb}MB.");
}

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    send_error("File extension .{$ext} is not allowed.");
}

// Strict MIME type check using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($fileTmp);
$allowedMimes = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'
];

if (!in_array($mimeType, $allowedMimes, true)) {
    send_error("Invalid file content or disallowed MIME type ({$mimeType}).");
}

// Store inside uploads directory with randomized name
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    // Protect uploads directory against PHP execution
    file_put_contents($uploadDir . '.htaccess', "php_flag engine off\nRemoveHandler .php\nRemoveType .php");
}

$randomName = bin2hex(random_bytes(16)) . '.' . $ext;
$targetPath = $uploadDir . $randomName;

if (!move_uploaded_file($fileTmp, $targetPath)) {
    send_error('Failed to save uploaded file on server.');
}

$relativePath = 'uploads/' . $randomName;

$stmtInsert = $pdo->prepare("
    INSERT INTO attachments (user_id, original_name, file_path, mime_type, file_size)
    VALUES (?, ?, ?, ?, ?)
");
$stmtInsert->execute([$user['id'], $fileName, $relativePath, $mimeType, $fileSize]);
$attachmentId = (int)$pdo->lastInsertId();

send_success([
    'attachment' => [
        'id' => $attachmentId,
        'original_name' => $fileName,
        'file_path' => $relativePath,
        'mime_type' => $mimeType,
        'file_size' => $fileSize
    ]
], 'File uploaded successfully.');
