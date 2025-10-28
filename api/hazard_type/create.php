<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$body = json_decode(file_get_contents('php://input'), true);
$name = isset($body['name']) ? trim($body['name']) : '';
$category = isset($body['category']) ? trim($body['category']) : '';
$description = isset($body['description']) ? trim($body['description']) : '';

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO hazard_types (name, category, description) VALUES (:name, :category, :description)');
    $stmt->execute([
        'name' => $name,
        'category' => $category,
        'description' => $description,
    ]);
    $id = (int)$pdo->lastInsertId();
    $sel = $pdo->prepare('SELECT id, name, category, description, created_at FROM hazard_types WHERE id = :id LIMIT 1');
    $sel->execute(['id' => $id]);
    $row = $sel->fetch();
    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

