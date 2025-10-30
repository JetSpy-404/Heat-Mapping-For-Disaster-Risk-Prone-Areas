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
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? '',
            'profile_picture' => !empty($user['profile_picture']) ? $user['profile_picture'] : 'assets/images/logo10.png',
            'status' => $user['status'] ?? 'pending'
        ];
    }, $users);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load users']);
}
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

