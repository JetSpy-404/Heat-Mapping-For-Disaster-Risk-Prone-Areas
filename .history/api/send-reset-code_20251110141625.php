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
    $resetCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Hash the code
    $hashedCode = password_hash($resetCode, PASSWORD_DEFAULT);

    // Check rate limiting: max 3 attempts per email per hour
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as attempt_count FROM password_resets
        WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ');
    $stmt->execute([$email]);
    $attempts = $stmt->fetch()['attempt_count'];

    if ($attempts >= 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many reset attempts. Please try again later.']);
        exit;
    }

    // Store in database (insert or update)
    $stmt = $pdo->prepare('
        INSERT INTO password_resets (email, hashed_code, expires_at, created_at, attempts)
        VALUES (?, ?, ?, NOW(), 0)
        ON DUPLICATE KEY UPDATE
        hashed_code = VALUES(hashed_code),
        expires_at = VALUES(expires_at),
        created_at = NOW(),
        attempts = 0
    ');
    $stmt->execute([$email, $hashedCode, $expiresAt]);

    // For testing: log the reset code
    error_log("Password reset code for {$email}: {$resetCode}");

    // Check if PHPMailer is available
