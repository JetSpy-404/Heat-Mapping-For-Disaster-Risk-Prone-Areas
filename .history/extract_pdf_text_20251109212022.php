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

// Use pdftotext with layout preservation for better extraction
$text = '';
$command = "pdftotext -layout \"$pdfPath\" -";
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    $rawText = implode("\n", $output);

    // Clean up the text
    $text = cleanAndFormatText($rawText);

    // Extract structured information
    $structuredData = extractStructuredData($rawText);

    echo json_encode([
        'text' => $text,
        'structured' => $structuredData,
        'success' => true
    ]);
} else {
    // Try alternative extraction methods
    $text = tryAlternativeExtraction($pdfPath);
    if ($text) {
        $structuredData = extractStructuredData($text);
        echo json_encode([
            'text' => $text,
            'structured' => $structuredData,
            'success' => true
        ]);
    } else {
        echo json_encode([
            'error' => 'Unable to extract text from PDF. Please view the PDF directly.',
            'success' => false
        ]);
    }
}

function cleanAndFormatText($text) {
    // Remove excessive whitespace
    $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
    $text = preg_replace('/\s{2,}/', ' ', $text);

    // Preserve line breaks for structure
    $text = nl2br(htmlspecialchars($text));

    return $text;
}

function extractStructuredData($text) {
    $data = [];

    // Extract bulletin number
    if (preg_match('/(?:BULLETIN|TCWS)\s+NO\.?\s*(\d+)/i', $text, $match)) {
        $data['bulletinNumber'] = $match[1];
    }

    // Extract issued date
    if (preg_match('/(\d{1,2}\s+(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4})/i', $text, $match)) {
        $data['issuedDate'] = $match[1];
    }

    // Extract valid until
    if (preg_match('/(?:VALID\s+UNTIL|UNTIL)\s*:\s*([^\n]+)/i', $text, $match)) {
        $data['validUntil'] = trim($match[1]);
    }

    // Extract cyclone name
    if (preg_match('/(?:TROPICAL\s+)?CYCLONE\s+([A-Z][A-Z\s]*?)(?:\s|$)/i', $text, $match)) {
        $data['cycloneName'] = trim($match[1]);
    }

    // Extract intensity (wind speed)
    if (preg_match('/(\d+)\s*(?:km\/h|kph|mph|kt|knots)/i', $text, $match)) {
        $data['intensity'] = $match[1] . ' km/h';
    }

    // Extract location/position
    if (preg_match('/(?:CENTER|LOCATED)\s+(?:AT|NEAR)\s+([^.]+?)(?:\s*\.|\s*$)/i', $text, $match)) {
        $data['location'] = trim($match[1]);
    }

    // Extract forecast information
    if (preg_match('/FORECAST\s*(?:POSITION|TRACK)?\s*:?\s*([^.]+?)(?:\s*\.|\s*$)/i', $text, $match)) {
        $data['forecast'] = trim($match[1]);
    }

    // Extract warnings/signals
    $warnings = [];
    if (preg_match_all('/(?:TCWS|WARNING)\s+(?:NO\.?\s*)?(\d+)[^.]*/i', $text, $matches)) {
        $warnings = $matches[1];
    }
    if (!empty($warnings)) {
        $data['warnings'] = array_unique($warnings);
    }

    // Extract affected areas
    if (preg_match('/AFFECTED\s+AREAS?\s*:?\s*([^.]+?)(?:\s*\.|\s*$)/i', $text, $match)) {
        $data['affectedAreas'] = trim($match[1]);
    }

    // Extract movement direction and speed
    if (preg_match('/MOVING\s+([^.]+?)(?:\s*\.|\s*$)/i', $text, $match)) {
        $data['movement'] = trim($match[1]);
    }

    return $data;
}

function tryAlternativeExtraction($pdfPath) {
    // Try using pdf2txt if available (Python-based)
    $command = "python -c \"import sys; from pdfminer.high_level import extract_text; print(extract_text('$pdfPath'))\" 2>/dev/null";
    exec($command, $output, $returnVar);

    if ($returnVar === 0 && !empty($output)) {
        return implode("\n", $output);
    }

    // Try using pdftotext without layout preservation
    $command = "pdftotext \"$pdfPath\" -";
    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        return implode("\n", $output);
    }

    return false;
}
?>
