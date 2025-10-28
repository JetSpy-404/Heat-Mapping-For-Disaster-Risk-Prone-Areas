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
    // Get barangay info
    $stmt = $pdo->prepare('SELECT name, population FROM barangays WHERE id = ?');
    $stmt->execute([$barangay_id]);
    $barangay = $stmt->fetch();

    if (!$barangay) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Barangay not found']);
        exit;
    }

    // Get actual hazard exposure data from database
    $hazard_types = [1 => 'Landslides', 2 => 'Floods', 3 => 'Storm Surge'];
    $exposure_data = [];

    foreach ($hazard_types as $id => $name) {
        $stmt = $pdo->prepare('SELECT SUM(houses_affected) as total_affected, COUNT(*) as event_count FROM hazards WHERE barangay_id = ? AND hazard_type_id = ?');
        $stmt->execute([$barangay_id, $id]);
        $hazard_data = $stmt->fetch();

        // Use actual houses_affected data, fallback to estimated if no data
        $affected = $hazard_data['total_affected'] ?? 0;
        if ($affected == 0 && $hazard_data['event_count'] > 0) {
            // Estimate based on events if no houses_affected data
            $affected = $hazard_data['event_count'] * 75; // Conservative estimate per event
        }

        $percentage = $barangay['population'] > 0 ? round(($affected / $barangay['population']) * 100, 1) : 0;

        $exposure_data[$name] = [
            'count' => (int)$affected,
            'percentage' => $percentage
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'barangay_name' => $barangay['name'],
            'total_population' => (int)$barangay['population'],
            'flood_exposure' => $exposure_data['Floods'],
            'landslide_exposure' => $exposure_data['Landslides'],
            'storm_surge_exposure' => $exposure_data['Storm Surge']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
