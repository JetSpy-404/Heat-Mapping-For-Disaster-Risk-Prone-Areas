<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, profile_picture, status FROM users WHERE id != ? ORDER BY first_name ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function($user) {
        return [
            'id' => (int)$user['id'],
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',

    $data = array_map(function($r) {
        return [
            'id' => isset($r['id']) ? (int)$r['id'] : 0,
            'profile_picture' => !empty($r['profile_picture']) ? $r['profile_picture'] : 'assets/images/default-avatar.jpg',
            'first_name' => $r['first_name'] ?? '',
            'last_name' => $r['last_name'] ?? '',
            'municipality' => $r['municipality'] ?? '',
            'address' => $r['address'] ?? '',
            'contact_number' => $r['contact_number'] ?? '',
            'usertype' => $r['usertype'] ?? '',
            'status' => $r['status'] ?? '',
            'email' => $r['email'] ?? '',
            'created_at' => $r['created_at'] ?? '',
        ];
    }, $rows ?: []);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log('[api/users/list.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

