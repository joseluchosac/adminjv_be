<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require "../vendor/autoload.php";
function sendFromHostingerMail(){
  $mail = new PHPMailer(true);
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@josvelsac.com';
    $mail->Password = '@0Pewg4514';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = '587';
    
    $mail->setFrom('info@josvelsac.com', 'JOSVELSAC CDP');
    $mail->addAddress('josvelsac@gmail.com', 'JOSVELSAC_GMAIL CDP');
    // $mail->addCC('otro@mail.com', 'JOSVELSAC_GMAIL CDP');
    
    // $mail->addAttachment("docs/documento.pdf", "documento.pdf");
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Prueba de envío de correo';
    $mail->Body = 'Esto es una prueba de <strong>Envio de email del ñandú</strong>';
    $mail->send();
    echo "Correo enviado";

}
