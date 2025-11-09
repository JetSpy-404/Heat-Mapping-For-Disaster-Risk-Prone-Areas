<?php
// Lightweight wrapper for barangay page — enforce session and serve the HTML template.
include_once __DIR__ . '/session_check.php';

// Output the existing HTML template. This keeps the original HTML file untouched
// and enforces the session guard in one place.
readfile(__DIR__ . '/barangay.html');
exit;
?>

