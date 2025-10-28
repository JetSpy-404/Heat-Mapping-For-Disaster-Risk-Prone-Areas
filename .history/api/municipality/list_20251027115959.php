<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = include __DIR__ . '/../db.php';

    $stmt = $pdo->query('SELECT id, name FROM municipalities ORDER BY name');
    $municipalities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($municipalities);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
