<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$body = json_decode(file_get_contents('php://input'), true);
$id = isset($body['id']) ? (int)$body['id'] : 0;
$code = isset($body['code']) ? trim($body['code']) : null;
$name = isset($body['name']) ? trim($body['name']) : '';
$municipality_id = isset($body['municipality_id']) ? (int)$body['municipality_id'] : 0;
$population = isset($body['population']) ? (int)$body['population'] : 0;

if ($id <= 0 || $name === '' || $municipality_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid id, name or municipality_id']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE barangays SET code = :code, name = :name, municipality_id = :municipality_id, population = :population WHERE id = :id');
    $stmt->execute(['code' => $code, 'name' => $name, 'municipality_id' => $municipality_id, 'population' => $population, 'id' => $id]);
    echo json_encode(['success' => true, 'data' => ['id' => $id, 'code' => $code, 'name' => $name, 'municipality_id' => $municipality_id, 'population' => $population]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
