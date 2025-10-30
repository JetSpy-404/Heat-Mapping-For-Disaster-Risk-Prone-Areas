<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user_id'] ?? 0;

if (!$other_user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.message, m.timestamp, m.read_status,
               u.first_name, u.last_name, u.profile_picture
        FROM messages m
        JOIN users u ON (m.sender_id = u.id)
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $pdo->prepare("UPDATE messages SET read_status = 1 WHERE receiver_id = ? AND sender_id = ? AND read_status = 0")
         ->execute([$user_id, $other_user_id]);

    echo json_encode(['success' => true, 'data' => $messages]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load messages']);
}
