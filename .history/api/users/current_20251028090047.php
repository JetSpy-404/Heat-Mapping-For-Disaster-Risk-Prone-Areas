<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

try {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.profile_picture,
            COALESCE(m.name, '') AS municipality, u.address, u.contact_number
            FROM users u
            LEFT JOIN municipalities m ON u.municipality_id = m.id
            WHERE u.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $data = [
        'id' => (int)$user['id'],
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? '',
        'profile_picture' => !empty($user['profile_picture']) ? $user['profile_picture'] : 'assets/images/logo10.png',
        'municipality' => $user['municipality'] ?? '',
        'address' => $user['address'] ?? '',
        'contact_number' => $user['contact_number'] ?? '',
    ];

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log('[api/users/current.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
