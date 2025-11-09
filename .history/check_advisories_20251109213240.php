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
    $command = 'node ' . __DIR__ . '/pagasa-monitor.js --check-only --force-latest 2>&1';
    $output = shell_exec($command);

    if ($output === null) {
        throw new Exception('Failed to execute monitor script');
    }

    // Parse the output - look for JSON in the output
    $jsonStart = strpos($output, '{');
    $jsonEnd = strrpos($output, '}');

    if ($jsonStart === false || $jsonEnd === false) {
        // If no JSON found, assume no new advisories
        $result = ['new_advisories' => []];
    } else {
        $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        $result = json_decode($jsonString, true);

        if ($result === null) {
            // If JSON parsing failed, assume no new advisories
            $result = ['new_advisories' => []];
        }
    }

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
        'new_advisories' => $result['new_advisories'] ?? [],
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
