<?php
declare(strict_types=1);

namespace KCS\Services;

/**
 * Minimal SMTP client using STARTTLS.
 * This avoids external dependencies for the academic project.
 *
 * Gmail requires an App Password and STARTTLS on port 587.
 */
class EmailService
{
  private static function readSmtpResponse($fp): string
  {
    $response = '';
    // SMTP multiline replies:
    // e.g. 250-localhost
    //      250-SIZE 35882577
    //      250 HELP
    while (!feof($fp)) {
      $line = fgets($fp, 2048);
      if ($line === false) {
        break;
      }
      $response .= $line;
      $trim = rtrim($line, "\r\n");
      if (strlen($trim) >= 4 && $trim[3] === ' ') {
        break;
      }
    }
    return $response;
  }

  private static function smtpCommand($fp, string $cmd): void
  {
    fwrite($fp, $cmd . "\r\n");
  }

  private static function expectCodes($response, array $expected): void
  {
    $code = (int)substr(trim($response), 0, 3);
    if (!in_array($code, $expected, true)) {
      throw new \RuntimeException('SMTP error: ' . $code);
    }
  }

  public static function sendMail(string $toEmail, string $toName, string $subject, string $body): void
  {
    $config = require __DIR__ . '/../../config/config.php';
    $smtp = $config['smtp'];

    if (empty($smtp['username']) || empty($smtp['password'])) {
      throw new \RuntimeException('SMTP credentials not configured.');
    }

    $fp = stream_socket_client(
      sprintf('tcp://%s:%d', $smtp['host'], $smtp['port']),
      $errno,
      $errstr,
      30,
      STREAM_CLIENT_CONNECT
    );

    if (!$fp) {
      throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
    }

    // Server greeting
    $greet = self::readSmtpResponse($fp);
    self::expectCodes($greet, [220]);

    // EHLO
    self::smtpCommand($fp, 'EHLO kcs-clearance');
    $ehloResp = self::readSmtpResponse($fp);
    self::expectCodes($ehloResp, [250]);

    // STARTTLS
    self::smtpCommand($fp, 'STARTTLS');
    $startTlsResp = self::readSmtpResponse($fp);
    self::expectCodes($startTlsResp, [220]);

    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      fclose($fp);
      throw new \RuntimeException('Failed to enable TLS for SMTP.');
    }

    // EHLO again after STARTTLS
    self::smtpCommand($fp, 'EHLO kcs-clearance');
    $ehloResp2 = self::readSmtpResponse($fp);
    self::expectCodes($ehloResp2, [250]);

    // AUTH LOGIN
    self::smtpCommand($fp, 'AUTH LOGIN');
    $auth1 = self::readSmtpResponse($fp);
    self::expectCodes($auth1, [334]);

    self::smtpCommand($fp, base64_encode($smtp['username']));
    $auth2 = self::readSmtpResponse($fp);
    self::expectCodes($auth2, [334]);

    self::smtpCommand($fp, base64_encode($smtp['password']));
    $auth3 = self::readSmtpResponse($fp);
    self::expectCodes($auth3, [235]);

    // MAIL FROM
    self::smtpCommand($fp, 'MAIL FROM:<' . $smtp['from_email'] . '>');
    $mailFrom = self::readSmtpResponse($fp);
    self::expectCodes($mailFrom, [250]);

    // RCPT TO
    self::smtpCommand($fp, 'RCPT TO:<' . $toEmail . '>');
    $rcpt = self::readSmtpResponse($fp);
    self::expectCodes($rcpt, [250, 251]);

    // DATA
    self::smtpCommand($fp, 'DATA');
    $data = self::readSmtpResponse($fp);
    self::expectCodes($data, [354]);

    $subjectEncoded = $subject; // For academic use; keep simple (no QP/base64 RFC encoding).
    $headers = [
      'From: "' . $smtp['from_name'] . '" <' . $smtp['from_email'] . '>',
      'To: "' . $toName . '" <' . $toEmail . '>',
      'Subject: ' . $subjectEncoded,
      'MIME-Version: 1.0',
      'Content-Type: text/plain; charset=UTF-8',
    ];

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
    fwrite($fp, $payload);

    $dataEnd = self::readSmtpResponse($fp);
    self::expectCodes($dataEnd, [250]);

    // QUIT
    self::smtpCommand($fp, 'QUIT');
    fclose($fp);
  }
}

