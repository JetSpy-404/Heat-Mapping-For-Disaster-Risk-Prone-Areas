<?php
// test-email.php
require_once 'Mail/phpmailer/PHPMailerAutoload.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Username = 'jethabac161@gmail.com';
    $mail->Password = 'Habacvaporoso1';
    $mail->SMTPDebug = 2;
    
    $mail->setFrom('jethabac161@gmail.com', 'Test');
    $mail->addAddress('jetvaporosohabac@gmail.com');
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email';
    
    if ($mail->send()) {
        echo "Email sent successfully";
    } else {
        echo "Email failed to send";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
