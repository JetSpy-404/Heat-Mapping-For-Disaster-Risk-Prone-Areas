<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';

try {
    $stmt = $pdo->query('SELECT id, name FROM municipalities ORDER BY name');
    $municipalities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $municipalities]);
} catch (Exception $e) {
    error_log('[api/municipality/list.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
