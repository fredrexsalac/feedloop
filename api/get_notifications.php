<?php
session_start();
require '../db.php';

// Determine login status using unified session first, fallback to legacy
$is_logged_in = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !empty($_SESSION['user_id']));

if ($is_logged_in) {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
} elseif (!empty($_SESSION['frontend_logged_in'])) {
    $is_logged_in = true;
    $user_id = (int)($_SESSION['frontend_user_id'] ?? 0);
} else {
    $user_id = 0;
}

if (!$is_logged_in || $user_id <= 0) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$notifications = [];
$unread_count = 0;

try {
    // Prefer unified user_notifications table
    $stmt = $pdo->prepare(
        "SELECT n.*
         FROM user_notifications n
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC
         LIMIT 5"
    );
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Count unread in user_notifications (is_read assumed tinyint 0/1)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = (int)($stmt->fetchColumn() ?: 0);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error loading notifications: ' . $e->getMessage()
    ]);
}
?>