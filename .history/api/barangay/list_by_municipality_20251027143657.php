<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$municipality_id = isset($_GET['municipality_id']) ? (int)$_GET['municipality_id'] : 0;

if ($municipality_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid municipality_id']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, name FROM barangays WHERE municipality_id = ? ORDER BY name');
    $stmt->execute([$municipality_id]);
    $data = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
