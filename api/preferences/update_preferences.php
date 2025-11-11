<?php
session_start();
require_once '../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$input = $_POST;
if (empty($input)) {
    // Support JSON body
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) { $input = $decoded; }
}

$theme = $input['theme'] ?? null; // light | dark | animated
$accent_color = $input['accent_color'] ?? null;
$compact_mode = isset($input['compact_mode']) ? (int)!!$input['compact_mode'] : null;
$language = $input['language'] ?? null;
$timezone = $input['timezone'] ?? null;
$notif_email = isset($input['notif_email']) ? (int)!!$input['notif_email'] : null;
$notif_push = isset($input['notif_push']) ? (int)!!$input['notif_push'] : null;
$notif_categories = $input['notif_categories'] ?? null; // array or JSON
$landing_section = $input['landing_section'] ?? null;
$show_tutorials = isset($input['show_tutorials']) ? (int)!!$input['show_tutorials'] : null;

$allowed_themes = ['light','dark','animated'];
if ($theme !== null && !in_array($theme, $allowed_themes, true)) {
    echo json_encode(['error' => 'Invalid theme']);
    exit;
}

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

    // Build upsert with COALESCE semantics
    $stmt = $pdo->prepare("INSERT INTO user_preferences (
            user_id, theme, accent_color, compact_mode, language, timezone, notif_email, notif_push, notif_categories, landing_section, show_tutorials
        ) VALUES (
            :user_id, COALESCE(:theme, 'light'), :accent_color, COALESCE(:compact_mode, 0), COALESCE(:language, 'en'), COALESCE(:timezone, 'UTC'), COALESCE(:notif_email, 1), COALESCE(:notif_push, 1), :notif_categories, COALESCE(:landing_section, 'announcements'), COALESCE(:show_tutorials, 1)
        )
        ON DUPLICATE KEY UPDATE
            theme = COALESCE(VALUES(theme), theme),
            accent_color = COALESCE(VALUES(accent_color), accent_color),
            compact_mode = COALESCE(VALUES(compact_mode), compact_mode),
            language = COALESCE(VALUES(language), language),
            timezone = COALESCE(VALUES(timezone), timezone),
            notif_email = COALESCE(VALUES(notif_email), notif_email),
            notif_push = COALESCE(VALUES(notif_push), notif_push),
            notif_categories = COALESCE(VALUES(notif_categories), notif_categories),
            landing_section = COALESCE(VALUES(landing_section), landing_section),
            show_tutorials = COALESCE(VALUES(show_tutorials), show_tutorials)
    ");

    // Normalize notif_categories to JSON
    if (is_array($notif_categories)) {
        $notif_categories = json_encode($notif_categories);
    } elseif ($notif_categories !== null && !is_string($notif_categories)) {
        $notif_categories = json_encode([]);
    }

    $stmt->execute([
        ':user_id' => $user_id,
        ':theme' => $theme,
        ':accent_color' => $accent_color,
        ':compact_mode' => $compact_mode,
        ':language' => $language,
        ':timezone' => $timezone,
        ':notif_email' => $notif_email,
        ':notif_push' => $notif_push,
        ':notif_categories' => $notif_categories,
        ':landing_section' => $landing_section,
        ':show_tutorials' => $show_tutorials,
    ]);

    // Optionally refresh key session values
    if ($theme !== null) { $_SESSION['theme'] = $theme; }
    if ($timezone !== null) { $_SESSION['timezone'] = $timezone; }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Failed to update preferences']);
}
