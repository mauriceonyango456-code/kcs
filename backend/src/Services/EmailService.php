<?php
declare(strict_types=1);

namespace KCS\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email service powered by PHPMailer
 */
class EmailService
{
  public static function sendMail(string $toEmail, string $toName, string $subject, string $body): void
  {
    $config = require __DIR__ . '/../../config/config.php';
    $smtp = $config['smtp'] ?? [];

    if (empty($smtp['username']) || empty($smtp['password'])) {
      throw new \RuntimeException('SMTP credentials not configured.');
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp['port'] ?? 587;

        // Recipients
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        throw new \RuntimeException("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
  }
}
