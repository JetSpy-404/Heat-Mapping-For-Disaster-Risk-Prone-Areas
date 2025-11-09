<?php
header('Content-Type: application/json');

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
    // Run the Node.js monitor script
    $command = 'node ' . __DIR__ . '/pagasa-monitor.js --check-only 2>&1';
    $output = shell_exec($command);

    if ($output === null) {
        throw new Exception('Failed to execute monitor script');
    }

