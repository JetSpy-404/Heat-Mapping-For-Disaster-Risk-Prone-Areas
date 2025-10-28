<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

try {
    $sql = "SELECT h.id,
        h.barangay_id,
        b.name AS barangay,
        h.municipality_id,
        m.name AS municipality,
        h.hazard_type_id,
        ht.name AS disaster_type,
        DATE_FORMAT(h.event_date, '%Y-%m-%d') AS date,
        h.severity,
        h.houses_affected,
        h.created_at
        FROM hazards h
        LEFT JOIN barangays b ON h.barangay_id = b.id
        LEFT JOIN municipalities m ON h.municipality_id = m.id
        LEFT JOIN hazard_types ht ON h.hazard_type_id = ht.id
        ORDER BY h.id DESC";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

