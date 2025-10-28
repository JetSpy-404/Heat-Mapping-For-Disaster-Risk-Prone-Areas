<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$barangay_id = isset($_GET['barangay_id']) ? (int)$_GET['barangay_id'] : 0;
$hazard_type_id = isset($_GET['hazard_type_id']) ? (int)$_GET['hazard_type_id'] : 0;

if ($barangay_id <= 0 || $hazard_type_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid barangay_id or hazard_type_id']);
    exit;
}

try {
    // Get barangay population
    $stmt = $pdo->prepare('SELECT population FROM barangays WHERE id = ?');
    $stmt->execute([$barangay_id]);
    $barangay = $stmt->fetch();

    if (!$barangay) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Barangay not found']);
        exit;
    }

    // Get hazard data for this barangay and hazard type
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM hazards WHERE barangay_id = ? AND hazard_type_id = ?');
    $stmt->execute([$barangay_id, $hazard_type_id]);
    $hazard_count = $stmt->fetch()['count'];

    // For now, we'll simulate affected population based on hazard count
    // In a real system, this would be based on actual hazard mapping data
    $affected_population = min($barangay['population'], $hazard_count * 100); // Simple simulation

    echo json_encode([
        'success' => true,
        'data' => [
            'total_population' => (int)$barangay['population'],
            'affected_population' => (int)$affected_population,
            'hazard_events' => (int)$hazard_count
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
