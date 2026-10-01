<?php
// setup.php
// LMNTrix Initialization & Seeding Script

require_once __DIR__ . '/config/db.php';

$pdo = get_db_connection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'sqlite') {
    // Create SQLite tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            avatar TEXT DEFAULT NULL,
            status TEXT DEFAULT 'Building things and breaking things.',
            bio TEXT DEFAULT NULL,
            role TEXT NOT NULL DEFAULT 'member',
            accent_hue INTEGER NOT NULL DEFAULT 205,
            theme_pref TEXT NOT NULL DEFAULT 'dark',
            is_disabled INTEGER NOT NULL DEFAULT 0,
            is_banned INTEGER NOT NULL DEFAULT 0,
            is_muted INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS user_presence (
            user_id INTEGER PRIMARY KEY,
            status TEXT NOT NULL DEFAULT 'offline',
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            original_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            file_size INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reply_to_id INTEGER DEFAULT NULL,
            message TEXT NOT NULL,
            attachment_id INTEGER DEFAULT NULL,
            is_edited INTEGER NOT NULL DEFAULT 0,
            is_deleted INTEGER NOT NULL DEFAULT 0,
            is_announcement INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL,
            FOREIGN KEY (attachment_id) REFERENCES attachments(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS message_reactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            emoji TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(message_id, user_id, emoji),
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            actor_id INTEGER DEFAULT NULL,
            message_id INTEGER DEFAULT NULL,
            content TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS typing_status (
            user_id INTEGER PRIMARY KEY,
            last_typed DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS group_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS pinned_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message_id INTEGER NOT NULL UNIQUE,
            pinned_by INTEGER NOT NULL,
            pinned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
            FOREIGN KEY (pinned_by) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS admin_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            target_type TEXT DEFAULT NULL,
            target_id INTEGER DEFAULT NULL,
            details TEXT DEFAULT NULL,
            ip_address TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
}

// Seed default settings if empty
$stmt = $pdo->query("SELECT COUNT(*) FROM group_settings");
if ($stmt->fetchColumn() == 0) {
    $defaultSettings = [
        'group_name' => 'LMNTRIX',
        'group_description' => 'Private digital clubhouse for the inner circle.',
        'group_icon' => 'L',
        'registration_enabled' => '1',
        'chat_enabled' => '1',
        'maintenance_mode' => '0',
        'max_upload_size_mb' => '5',
        'allowed_upload_types' => 'jpg,jpeg,png,gif,webp',
        'announcement_message' => 'Welcome to LMNTRIX Private Space!'
    ];
    $insertSetting = $pdo->prepare("INSERT INTO group_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaultSettings as $k => $v) {
        $insertSetting->execute([$k, $v]);
    }
}

// Seed default users if empty
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $seedUsers = [
        [
            'username' => 'pranav',
            'display_name' => 'PRANAV',
            'email' => 'pranav@lmntrix.local',
            'password' => 'password123',
            'role' => 'owner',
            'hue' => 205,
            'status' => 'Building things and breaking things.',
            'bio' => 'Founder of the LMNTrix inner circle.'
        ],
        [
            'username' => 'alex',
            'display_name' => 'Alex',
            'email' => 'alex@lmntrix.local',
            'password' => 'password123',
            'role' => 'admin',
            'hue' => 205,
            'status' => 'Always up for a late night.',
            'bio' => 'Night owl and code enthusiast.'
        ],
        [
            'username' => 'riya',
            'display_name' => 'Riya',
            'email' => 'riya@lmntrix.local',
            'password' => 'password123',
            'role' => 'member',
            'hue' => 158,
            'status' => 'Design, coffee, repeat.',
            'bio' => 'UI/UX artist & coffee lover.'
        ],
        [
            'username' => 'adithya',
            'display_name' => 'Adithya',
            'email' => 'adithya@lmntrix.local',
            'password' => 'password123',
            'role' => 'member',
            'hue' => 222,
            'status' => 'Probably asleep.',
            'bio' => 'Early bird or late sleeper, nobody knows.'
        ],
        [
            'username' => 'nihal',
            'display_name' => 'Nihal',
            'email' => 'nihal@lmntrix.local',
            'password' => 'password123',
            'role' => 'member',
            'hue' => 140,
            'status' => 'Shipping small things.',
            'bio' => 'Systems engineer & problem solver.'
        ],
        [
            'username' => 'arjun',
            'display_name' => 'Arjun',
            'email' => 'arjun@lmntrix.local',
            'password' => 'password123',
            'role' => 'member',
            'hue' => 192,
            'status' => 'Somewhere with a book.',
            'bio' => 'Avid reader and tech adventurer.'
        ]
    ];

    $insertUser = $pdo->prepare("
        INSERT INTO users (username, display_name, email, password_hash, role, accent_hue, status, bio)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertPresence = $pdo->prepare("INSERT INTO user_presence (user_id, status) VALUES (?, ?)");

    foreach ($seedUsers as $u) {
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $insertUser->execute([
            $u['username'],
            $u['display_name'],
            $u['email'],
            $hash,
            $u['role'],
            $u['hue'],
            $u['status'],
            $u['bio']
        ]);
        $uid = $pdo->lastInsertId();
        $insertPresence->execute([$uid, $u['username'] === 'pranav' || $u['username'] === 'alex' || $u['username'] === 'riya' || $u['username'] === 'nihal' ? 'online' : 'offline']);
    }

    // Seed default chat messages
    $alexId = $pdo->query("SELECT id FROM users WHERE username = 'alex'")->fetchColumn();
    $pranavId = $pdo->query("SELECT id FROM users WHERE username = 'pranav'")->fetchColumn();
    $riyaId = $pdo->query("SELECT id FROM users WHERE username = 'riya'")->fetchColumn();
    $nihalId = $pdo->query("SELECT id FROM users WHERE username = 'nihal'")->fetchColumn();

    $insertMsg = $pdo->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, ?)");
    $now = time();

    $insertMsg->execute([$alexId, 'bro are you joining tonight?', date('Y-m-d H:i:s', $now - 3200)]);
    $insertMsg->execute([$pranavId, 'yeah, give me 10 mins.', date('Y-m-d H:i:s', $now - 3000)]);
    $insertMsg->execute([$riyaId, 'guys check this out', date('Y-m-d H:i:s', $now - 2400)]);
    $insertMsg->execute([$pranavId, 'wait what 😂', date('Y-m-d H:i:s', $now - 2200)]);
    $insertMsg->execute([$nihalId, 'sending this to everyone i know', date('Y-m-d H:i:s', $now - 1200)]);
    $insertMsg->execute([$pranavId, 'LMNTrix database connection is officially live! 🔥', date('Y-m-d H:i:s', $now - 300)]);
}

if (php_sapi_name() === 'cli' || isset($_GET['silent'])) {
    echo "LMNTrix setup complete.\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'LMNTrix setup complete']);
}
