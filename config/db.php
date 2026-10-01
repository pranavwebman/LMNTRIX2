<?php
// config/db.php
// LMNTrix Database Connection

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'lmntrix_db');
define('DB_USER', getenv('DB_USER') ?: 'lmntrix_user');
define('DB_PASS', getenv('DB_PASS') ?: 'secret_password');
define('DB_CHARSET', 'utf8mb4');

function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $sqlitePath = __DIR__ . '/../data/lmntrix.sqlite';

    // Check if SQLite fallback mode is explicitly enabled or file exists
    if (getenv('DB_DRIVER') === 'sqlite' || (!getenv('DB_HOST') && file_exists($sqlitePath))) {
        if (!is_dir(dirname($sqlitePath))) {
            mkdir(dirname($sqlitePath), 0755, true);
        }
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL is unavailable in current runtime environment
        if (!is_dir(dirname($sqlitePath))) {
            mkdir(dirname($sqlitePath), 0755, true);
        }
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        return $pdo;
    }
}
