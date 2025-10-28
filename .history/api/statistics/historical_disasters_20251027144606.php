<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$barangay_id = isset($_GET['barangay_id']) ? (int)$_GET['barangay_id'] : 0;

if ($barangay_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid barangay_id']);
    exit;
}

try {
    // Get disaster counts by hazard type for the last 5 years using actual hazard data
    $stmt = $pdo->prepare("
        SELECT
            ht.name as hazard_type,
            COUNT(h.id) as count
        FROM hazard_types ht
        LEFT JOIN hazards h ON ht.id = h.hazard_type_id
            AND h.barangay_id = ?
            AND h.event_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
        GROUP BY ht.id, ht.name
        ORDER BY ht.name
    ");
    $stmt->execute([$barangay_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
