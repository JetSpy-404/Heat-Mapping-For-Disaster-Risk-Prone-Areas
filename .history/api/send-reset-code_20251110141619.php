<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    // Check if user exists (optional for privacy)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // For privacy, don't reveal if user exists
        echo json_encode(['success' => true]);
        exit;
    }

    // Generate 6-digit code
