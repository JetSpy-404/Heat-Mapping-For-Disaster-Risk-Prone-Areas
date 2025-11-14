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
    $phpmailerPath = __DIR__ . '/../Mail/phpmailer/PHPMailerAutoload.php';
    if (file_exists($phpmailerPath)) {
        // Use PHPMailer if available
        require $phpmailerPath;
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host='smtp.gmail.com';
        $mail->Port=587;
        $mail->SMTPAuth=true;
        $mail->SMTPSecure='tls';

        // h-hotel account
        $mail->Username='jethabac161@gmail.com';
        $mail->Password='Habacvaporoso';

        // send by h-hotel email
        $mail->setFrom('email', 'Password Reset');
        // get email from input
        $mail->addAddress($email);

        try {
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset Code';
            $mail->Body = "
                <h3>Password Reset Request</h3>
                <p>Your password reset code is: <strong style='font-size: 18px;'>{$resetCode}</strong></p>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <br>
                <p>Regards,<br>Your App Team</p>
            ";
            $mail->AltBody = "Your password reset code is: {$resetCode}. This code will expire in 15 minutes.";

            $mail->send();
            $mailSent = true;
            
        } catch (Exception $e) {
            error_log('[send-reset-code] PHPMailer error: ' . $e->getMessage());
            // Fall back to basic mail() function
            $mailSent = sendBasicEmail($email, $resetCode);
        }
    } else {
        // Use basic mail() function if PHPMailer not available
        $mailSent = sendBasicEmail($email, $resetCode);
    }

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'Reset code sent successfully! Check your email.']);
    } else {
        error_log('[send-reset-code] All email methods failed');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    }

} catch (Exception $e) {
    error_log('[send-reset-code] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// Helper function for basic email sending
function sendBasicEmail($email, $resetCode) {
    $subject = 'Your Password Reset Code';
    $message = "
        <html>
        <head>
            <title>Password Reset Code</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .code { font-size: 18px; font-weight: bold; color: #4361ee; }
            </style>
        </head>
        <body>
            <h2>Password Reset Request</h2>
            <p>Your password reset code is: <span class='code'>{$resetCode}</span></p>
            <p>This code will expire in 15 minutes.</p>
            <p>If you didn't request this reset, please ignore this email.</p>
            <br>
            <p>Regards,<br>Your App Team</p>
        </body>
        </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: noreply@yourapp.com',
        'Reply-To: noreply@yourapp.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    return mail($email, $subject, $message, implode("\r\n", $headers));
}
