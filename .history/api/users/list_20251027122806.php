<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

try {
    // Select users and join municipality name; provide placeholders for optional columns
    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role AS usertype, u.status,
        COALESCE(m.name, '') AS municipality, u.address, u.contact_number, u.profile_picture, u.created_at
        FROM users u
        LEFT JOIN municipalities m ON u.municipality_id = m.id
        ORDER BY u.id DESC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

