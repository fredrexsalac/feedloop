<?php
session_start();
require_once '../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Defaults
$defaults = [
    'theme' => 'light',
    'accent_color' => null,
    'compact_mode' => 0,
    'language' => 'en',
    'timezone' => 'UTC',
    'notif_email' => 1,
    'notif_push' => 1,
    'notif_categories' => null,
    'landing_section' => 'announcements',
    'show_tutorials' => 1,
];

try {
    // Ensure table exists (best-effort)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
        user_id INT PRIMARY KEY,
        theme ENUM('light','dark','animated') DEFAULT 'light',
        accent_color VARCHAR(20) NULL,
        compact_mode TINYINT(1) DEFAULT 0,
        language VARCHAR(8) DEFAULT 'en',
        timezone VARCHAR(64) DEFAULT 'UTC',
        notif_email TINYINT(1) DEFAULT 1,
        notif_push TINYINT(1) DEFAULT 1,
        notif_categories JSON NULL,
        landing_section VARCHAR(40) DEFAULT 'announcements',
        show_tutorials TINYINT(1) DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prefs) {
        echo json_encode(['success' => true, 'preferences' => $defaults]);
        exit;
    }

    // Merge with defaults to be safe
    $preferences = array_merge($defaults, $prefs);
    echo json_encode(['success' => true, 'preferences' => $preferences]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Failed to load preferences']);
}
