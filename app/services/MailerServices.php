<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require 'vendor/autoload.php';
// require '../../vendor/autoload.php';

class MailerHostinger
{
  private static $host = 'smtp.hostinger.com';
  private static $username = 'info@josvelsac.com';
  private static $password = '@0Pewg4514';
  private static $port = 587;

  public static function sendMail($to, $subject, $body, $from = 'info@josvelsac.com', $fromName = 'josvelsac')
  {
    // Ejemplo de uso:
    // Mailer::sendMail('destinatario@example.com', 'Asunto del correo', 'Cuerpo del correo');
    $mail = new PHPMailer(true);

    try {
      // Configuración del servidor
      $mail->isSMTP();
      $mail->Host = self::$host;
      $mail->SMTPAuth = true;
      $mail->Username = self::$username;
      $mail->Password = self::$password;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = self::$port;

      // Remitente
      $mail->setFrom($from, $fromName);

      // Destinatario
      $mail->addAddress($to);

      // Contenido
      $mail->isHTML(true);
      $mail->CharSet = 'UTF-8';
      $mail->Subject = $subject;
      $mail->Body    = $body;

      $mail->send();
      $response["error"] = false;
      $response["msg"] = 'El correo ha sido enviado correctamente';
      return $response;
    } catch (Exception $e) {
      $response["error"] = false;
      $response["msg"] = "No se pudo enviar el correo. Error: {$mail->ErrorInfo}";
      return $response;
    }
  }
}


