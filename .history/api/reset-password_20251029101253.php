<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$code = trim($input['code'] ?? '');
$newPassword = trim($input['newPassword'] ?? '');

if (empty($email) || empty($code) || empty($newPassword)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strlen($newPassword) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    // Get the latest valid reset request
    $stmt = $pdo->prepare('
        SELECT hashed_code FROM password_resets
        WHERE email = ? AND expires_at > NOW()
        ORDER BY created_at DESC LIMIT 1
    ');
    $stmt->execute([$email]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset code']);
        exit;
    }

    // Verify the code
    if (!password_verify($code, $resetRequest['hashed_code'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid code']);
        exit;
    }

    // Hash the new password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update user password
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$newHash, $email]);

    // Delete the used reset code
    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
    $stmt->execute([$email]);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);

} catch (Exception $e) {
    error_log('[reset-password] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
