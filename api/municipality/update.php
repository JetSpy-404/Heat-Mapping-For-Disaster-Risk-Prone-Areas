<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$body = json_decode(file_get_contents('php://input'), true);
$id = isset($body['id']) ? (int)$body['id'] : 0;
$code = isset($body['code']) && $body['code'] !== '' ? trim($body['code']) : null;
$name = isset($body['name']) ? trim($body['name']) : '';
$province = isset($body['province']) && $body['province'] !== '' ? trim($body['province']) : null;
$populations = isset($body['populations']) ? (int)$body['populations'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

if ($populations < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Populations must be 0 or greater']);
    exit;
}

if ($code !== null && strlen($code) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Code too long (max 50 chars)']);
    exit;
}

try {
    // If code provided, ensure it's not used by another row
    if ($code !== null && $code !== '') {
        $chk = $pdo->prepare('SELECT id FROM municipalities WHERE code = :code LIMIT 1');
        $chk->execute(['code' => $code]);
        $existing = $chk->fetch();
        if ($existing && (int)$existing['id'] !== $id) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Code already in use by another municipality']);
            exit;
        }
    }

    $stmt = $pdo->prepare('UPDATE municipalities SET code = :code, name = :name, province = :province, populations = :populations WHERE id = :id');
    $stmt->execute([
        'code' => $code,
        'name' => $name,
        'province' => $province,
        'populations' => $populations,
        'id' => $id,
    ]);

    // Return authoritative row
    $sel = $pdo->prepare('SELECT id, code, name, province, populations, created_at FROM municipalities WHERE id = :id LIMIT 1');
    $sel->execute(['id' => $id]);
    $row = $sel->fetch();

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
