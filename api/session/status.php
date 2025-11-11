<?php
session_start();
header('Content-Type: application/json');

$response = [
  'logged_in' => false,
  'role' => 'guest',
  'user_id' => null,
  'username' => null,
];

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
  $response['logged_in'] = true;
  $response['role'] = $_SESSION['role'] ?? 'user';
  $response['user_id'] = (int)$_SESSION['user_id'];
  $response['username'] = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? null);
} elseif (!empty($_SESSION['frontend_logged_in']) && !empty($_SESSION['frontend_user_id'])) {
  $response['logged_in'] = true;
  $response['role'] = 'user';
  $response['user_id'] = (int)$_SESSION['frontend_user_id'];
  $response['username'] = $_SESSION['frontend_full_name'] ?? ($_SESSION['frontend_username'] ?? null);
}

echo json_encode(['success' => true] + $response);
