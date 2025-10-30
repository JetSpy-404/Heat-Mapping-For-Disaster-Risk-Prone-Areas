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

    // Store in database (insert or update)
    $stmt = $pdo->prepare('
        INSERT INTO password_resets (email, hashed_code, expires_at, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
        hashed_code = VALUES(hashed_code),
        expires_at = VALUES(expires_at),
        created_at = NOW()
    ');
    $stmt->execute([$email, $hashedCode, $expiresAt]);

    // Send email
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Replace with your email
        $mail->Password = 'your-app-password'; // Replace with app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('noreply@yourapp.com', 'Your App');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code';
        $mail->Body = "
            <p>Your password reset code is: <strong>{$resetCode}</strong></p>
            <p>This code will expire in 15 minutes.</p>
            <p>If you didn't request this reset, please ignore this email.</p>
        ";
        $mail->AltBody = "Your password reset code is: {$resetCode}. This code will expire in 15 minutes.";

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log('[send-reset-code] Mail error: ' . $mail->ErrorInfo);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }

} catch (Exception $e) {
    error_log('[send-reset-code] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
