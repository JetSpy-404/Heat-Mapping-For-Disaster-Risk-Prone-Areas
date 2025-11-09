<?php
header('Content-Type: application/json');

// Include the PAGASA monitor logic
require_once 'pagasa-monitor.php';

// Directory containing the PDFs
$directory = __DIR__ . '/pagasa_advisories/';

if (!is_dir($directory)) {
    echo json_encode(['error' => 'Directory not found']);
    exit;
}

// Load previously seen advisories
$monitorStateFile = $directory . 'monitor_state.json';
$knownAdvisories = [];

if (file_exists($monitorStateFile)) {
    $stateData = json_decode(file_get_contents($monitorStateFile), true);
    $knownAdvisories = $stateData['knownAdvisories'] ?? [];
}

try {
    // Create monitor instance
    $monitor = new RobustPagasaMonitor();

    // Check for new advisories
    $newAdvisories = $monitor->checkForNewAdvisories();

    // Get current list of PDFs
    $files = glob($directory . '*.pdf');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $currentPdfs = [];
    foreach ($files as $file) {
        $filename = basename($file);
        $currentPdfs[] = [
            'filename' => $filename,
            'path' => 'pagasa_advisories/' . $filename,
            'modified' => date('Y-m-d H:i:s', filemtime($file)),
            'size' => filesize($file)
        ];
    }

    // Return result
    echo json_encode([
        'success' => true,
        'new_advisories' => $newAdvisories,
        'total_advisories' => count($currentPdfs),
        'last_check' => date('Y-m-d H:i:s'),
        'current_pdfs' => $currentPdfs
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
}
?>
