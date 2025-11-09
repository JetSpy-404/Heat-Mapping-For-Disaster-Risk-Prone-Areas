<?php
include_once __DIR__ . '/../session_check.php';
include_once __DIR__ . '/../access_control.php';
include_once __DIR__ . '/../db.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get latest advisories from database
    // Assuming there's an advisories table with columns: id, title, description, level, created_at
    $stmt = $conn->prepare("
        SELECT id, title, description, level, created_at
        FROM advisories
        ORDER BY created_at DESC
        LIMIT 10
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $advisories = [];
    while ($row = $result->fetch_assoc()) {
        $advisories[] = [
            'id' => $row['id'],
            'title' => htmlspecialchars($row['title']),
