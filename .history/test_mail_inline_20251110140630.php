<?php
require_once 'vendor/autoload.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'live.smtp.mailtrap.io';
    $mail->SMTPAuth = true;
    $mail->Username = 'apismtp@mailtrap.io';
    $mail->Password = 'c9aa57ac112b9aed9311a53759cf4d54';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('noreply@yourapp.com', 'Your App');
    $mail->addAddress('test@example.com');
    $mail->isHTML(true);
    $mail->Subject = 'Test';
    $mail->Body = 'Test email';
    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo 'Error: ' . $mail->ErrorInfo;
}
