<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$current_password = trim($input['current_password'] ?? '');
$new_password = trim($input['new_password'] ?? '');

if (empty($current_password) || empty($new_password)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (strlen($new_password) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
    exit;
}

try {
    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$new_hash, $userId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('[api/users/change_password.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
