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
$code = trim($input['code'] ?? '');
$newPassword = trim($input['new_password'] ?? '');

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
    // Get the latest valid reset request and increment attempts
    $stmt = $pdo->prepare('
        SELECT id, hashed_code, attempts FROM password_resets
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

    // Check if too many attempts
    if ($resetRequest['attempts'] >= 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new code.']);
        exit;
    }

    // Verify the code
    if (!password_verify($code, $resetRequest['hashed_code'])) {
        // Increment attempts
        $stmt = $pdo->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?');
        $stmt->execute([$resetRequest['id']]);

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
