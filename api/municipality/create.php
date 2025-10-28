<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

// Expect JSON body
$body = json_decode(file_get_contents('php://input'), true);
$code = isset($body['code']) && $body['code'] !== '' ? trim($body['code']) : null;
$name = isset($body['name']) ? trim($body['name']) : '';
$province = isset($body['province']) && $body['province'] !== '' ? trim($body['province']) : null;
$populations = isset($body['populations']) ? (int)$body['populations'] : 0;

// Basic validation
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

if ($province !== null && strlen($province) > 191) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Province too long (max 191 chars)']);
    exit;
}

try {
    // If code provided, ensure uniqueness
    if ($code !== null && $code !== '') {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM municipalities WHERE code = :code');
        $chk->execute(['code' => $code]);
        if ($chk->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Code already in use']);
            exit;
        }
    }

    $stmt = $pdo->prepare('INSERT INTO municipalities (code, name, province, populations) VALUES (:code, :name, :province, :populations)');
    $stmt->execute([
        'code' => $code,
        'name' => $name,
        'province' => $province,
        'populations' => $populations,
    ]);
    $id = (int)$pdo->lastInsertId();

    // Return authoritative row from DB
    $sel = $pdo->prepare('SELECT id, code, name, province, populations, created_at FROM municipalities WHERE id = :id LIMIT 1');
    $sel->execute(['id' => $id]);
    $row = $sel->fetch();

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
