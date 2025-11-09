<?php
header('Content-Type: application/json');

// Include the PAGASA monitor logic
require_once 'pagasa-monitor.php';

// Directory containing the PDFs
$directory = __DIR__ . '/pagasa_advisories/';

