<?php
header('Content-Type: application/json');

// Directory containing the PDFs
$directory = __DIR__ . '/pagasa_advisories/';

if (!is_dir($directory)) {
    echo json_encode(['error' => 'Directory not found']);
    exit;
}

// Get all PDF files
$files = glob($directory . '*.pdf');

// Sort files by modification time (newest first)
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$pdfs = [];
foreach ($files as $file) {
    $filename = basename($file);
    $pdfs[] = [
        'filename' => $filename,
        'path' => 'pagasa_advisories/' . $filename,
        'modified' => date('Y-m-d H:i:s', filemtime($file)),
        'size' => filesize($file)
    ];
}

echo json_encode($pdfs);
?>
