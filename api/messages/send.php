<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$sender_id = $_SESSION['user_id'];
$receiver_id = $data['receiver_id'] ?? 0;
$message = trim($data['message'] ?? '');

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $message]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
