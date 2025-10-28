<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

try {
    // Return description as well so the client can display it
    $stmt = $pdo->query('SELECT id, name, category, description, created_at FROM hazard_types ORDER BY name ASC');
    $data = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
