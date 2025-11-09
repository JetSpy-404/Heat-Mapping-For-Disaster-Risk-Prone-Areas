<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['path'])) {
    echo json_encode(['error' => 'No PDF path provided']);
    exit;
}

$pdfPath = $_GET['path'];

// Check if file exists
if (!file_exists($pdfPath)) {
    echo json_encode(['error' => 'PDF file not found']);
    exit;
}

// Use pdftotext if available (common on Linux/Mac, may need installation on Windows)
$text = '';
$command = "pdftotext \"$pdfPath\" -";
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    $text = implode("\n", $output);
} else {
    // Fallback: try to read as text if it's a text-based PDF
    $text = 'Unable to extract text from PDF. Please view the PDF directly.';
}

// Basic formatting: convert line breaks to HTML
$text = nl2br(htmlspecialchars($text));

// Return the text
echo json_encode(['text' => $text]);
?>
