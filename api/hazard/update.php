<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$body = json_decode(file_get_contents('php://input'), true);
$id = isset($body['id']) ? (int)$body['id'] : 0;
$barangay_id = isset($body['barangay_id']) ? (int)$body['barangay_id'] : 0;
$municipality_id = isset($body['municipality_id']) ? (int)$body['municipality_id'] : 0;
$hazard_type_id = isset($body['hazard_type_id']) ? (int)$body['hazard_type_id'] : 0;
$event_date = isset($body['event_date']) ? $body['event_date'] : null;
$severity = isset($body['severity']) ? trim($body['severity']) : null;
$houses_affected = isset($body['houses_affected']) ? trim($body['houses_affected']) : null;

if ($id <= 0 || $barangay_id <= 0 || $municipality_id <= 0 || $hazard_type_id <= 0 || !$event_date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE hazards SET barangay_id = :barangay_id, municipality_id = :municipality_id, hazard_type_id = :hazard_type_id, event_date = :event_date, severity = :severity, houses_affected = :houses_affected WHERE id = :id');
    $stmt->execute([
        'barangay_id' => $barangay_id,
        'municipality_id' => $municipality_id,
        'hazard_type_id' => $hazard_type_id,
        'event_date' => $event_date,
        'severity' => $severity,
        'houses_affected' => $houses_affected,
        'id' => $id,
    ]);
    $sel = $pdo->prepare('SELECT id, barangay_id, municipality_id, hazard_type_id, DATE_FORMAT(event_date, "%Y-%m-%d") as event_date, severity, houses_affected, created_at FROM hazards WHERE id = :id LIMIT 1');
    $sel->execute(['id' => $id]);
    $row = $sel->fetch();
    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
