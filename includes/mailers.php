<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

function send_email($to, $name, $subject, $body) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'YOUR_EMAIL@gmail.com';
        $mail->Password = 'YOUR_APP_PASSWORD';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('YOUR_EMAIL@gmail.com', 'LazyDrip');
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer error: " . $mail->ErrorInfo);
        return false;
    }
}