<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

try {
    $stmt = $pdo->query(
        'SELECT u.id, u.first_name, u.last_name, u.email, u.role AS usertype, '
        . 'COALESCE(m.name, "") AS municipality, u.address, u.contact_number, u.profile_picture, u.created_at '
        . 'FROM users u LEFT JOIN municipalities m ON u.municipality_id = m.id '
        . 'ORDER BY u.id DESC'
    );
    $rows = $stmt->fetchAll();

    $data = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'profile_picture' => $r['profile_picture'] ? $r['profile_picture'] : 'assets/images/default-avatar.jpg',
            'first_name' => $r['first_name'],
            'last_name' => $r['last_name'],
            'municipality' => $r['municipality'],
            'address' => $r['address'] ?? '',
            'contact_number' => $r['contact_number'] ?? '',
            'usertype' => $r['usertype'],
            'status' => '',
            'email' => $r['email'],
            'created_at' => $r['created_at'],
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
