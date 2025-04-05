<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require "../vendor/autoload.php";

$mail = new PHPMailer(true);

try {
  // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'josvelsac@gmail.com';
  $mail->Password = 'qkwywlkeweblvfax';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = '587';
  // $mail->Port = '465';
  
  $mail->setFrom('josvelsac@gmail.com', 'josvelsac gmail');
  $mail->addAddress('info@josvelsac.com', 'JOSVELSAC_GMAIL CDP');
  // $mail->addCC('otro@mail.com', 'JOSVELSAC_GMAIL CDP');
  
  // $mail->addAttachment("docs/documento.pdf", "documento.pdf");
  $mail->isHTML(true);
  $mail->CharSet = 'UTF-8';
  $mail->Subject = 'Prueba desde gmail';
  $mail->Body = 'Esto es una prueba de <strong>Envio de email del ñandú</strong>';
  $mail->send();
  echo "Correo enviado";
} catch (Exception $e) {
  echo $mail->ErrorInfo;
}