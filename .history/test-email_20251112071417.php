<?php
// test-email.php
require_once 'Mail/phpmailer/PHPMailerAutoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Username = 'jethabac161@gmail.com';
    $mail->Password = 'Ha'; // Use App Password here
    
    // Enable debugging (optional)
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "[$level] $str\n";
    };
    
    // Recipients
    $mail->setFrom('jethabac161@gmail.com', 'Test System');
    $mail->addAddress('jethabac161@gmail.com'); // Send to yourself for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from PHP';
    $mail->Body = '<h1>This is a test email</h1><p>If you can read this, email is working!</p>';
    $mail->AltBody = 'This is a test email from PHP';
    
    if ($mail->send()) {
        echo "\n✅ Email sent successfully!\n";
    } else {
        echo "\n❌ Email failed to send\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}