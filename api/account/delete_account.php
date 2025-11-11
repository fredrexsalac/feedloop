<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();

require_once '../../db.php';

try {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['role'] ?? '') !== 'user') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $confirm = trim($input['confirm'] ?? '');
    if ($confirm !== 'DELETE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Confirmation text mismatch']);
        exit;
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid user']);
        exit;
    }

    $pdo->beginTransaction();

    try { $pdo->prepare('DELETE FROM user_notifications WHERE user_id = ?')->execute([$userId]); } catch (Throwable $e) {}
    try { $pdo->prepare('DELETE FROM user_dismissed_announcements WHERE user_id = ?')->execute([$userId]); } catch (Throwable $e) {}
    try { $pdo->prepare('DELETE FROM activity_logs WHERE user_id = ?')->execute([$userId]); } catch (Throwable $e) {}
    try { $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$userId]); } catch (Throwable $e) {}

    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ? AND role = "user"');
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Account not found']);
        exit;
    }

    $pdo->commit();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    echo json_encode(['success' => true, 'redirect' => '../../index.php?account_deleted=1']);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
