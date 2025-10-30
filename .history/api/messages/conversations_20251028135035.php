<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.profile_picture,
               (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY timestamp DESC LIMIT 1) AS last_message,
               (SELECT timestamp FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY timestamp DESC LIMIT 1) AS last_timestamp,
               (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND read_status = 0) AS unread_count
        FROM users u
        WHERE u.id != ?
        ORDER BY COALESCE(last_timestamp, '1970-01-01 00:00:00') DESC, u.first_name ASC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $conversations]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load conversations']);
}
