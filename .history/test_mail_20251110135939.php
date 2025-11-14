<?php
require_once 'vendor/autoload.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'live.smtp.mailtrap.io'; // Mailtrap SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'apismtp@mailtrap.io'; // Mailtrap username
    $mail->Password = 'c9aa57ac112b9aed9311a53759cf4d54'; // Mailtrap password
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS for port 587
    $mail->Port = 587; // Recommended port

    // Recipients
    $mail->setFrom('noreply@yourapp.com', 'Your App');
    $mail->addAddress('test@example.com');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email to verify Mailtrap configuration.';

    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo 'Error: ' . $mail->ErrorInfo;
}
