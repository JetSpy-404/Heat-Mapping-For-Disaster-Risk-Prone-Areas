<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user_id'] ?? 0;

if (!$other_user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    // Mark messages from other user as read
    $updateStmt = $pdo->prepare("UPDATE messages SET read_status = 1 WHERE sender_id = ? AND receiver_id = ? AND read_status = 0");
    $updateStmt->execute([$other_user_id, $user_id]);

    // Fetch messages between the two users
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, timestamp, read_status
        FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY timestamp ASC
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $messages]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load messages']);
}
