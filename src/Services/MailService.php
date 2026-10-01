<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Services;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use Somen\InventoryManagementSystem\Core\Env;
use Somen\InventoryManagementSystem\Core\Logger;

/**
 * Sends e-mail through PHPMailer (SMTP). MAIL_DRIVER=log writes mails to
 * storage/logs/mail.log instead (handy for local testing).
 *
 * send*() returns ['ok'=>bool, 'code'=>'OK'|'NO_INTERNET'|'MAIL_FAILED', 'message'=>string]
 */
final class MailService
{
    public static function sendOtp(string $to, string $name, string $otp): array
    {
        $minutes = (int)(OtpService::TTL / 60);
        $body = self::template(
            'Verify your email',
            "Hi " . e($name) . ", use this code to finish creating your StockSense account.",
            '<div style="font-size:34px;letter-spacing:10px;font-weight:700;color:#0B1F3A;background:#F8F5EE;'
            . 'border:1px solid #C9A24B;border-radius:8px;padding:16px 10px;text-align:center;margin:22px 0;">' . e($otp) . '</div>',
            "This code is valid for {$minutes} minutes. If you did not request it, you can ignore this email."
        );
        return self::send($to, $name, 'Your StockSense verification code', $body, "Your StockSense OTP is {$otp}. It is valid for {$minutes} minutes.");
    }

    public static function sendPasswordReset(string $to, string $name, string $link): array
    {
        $hours = PasswordResetService::TTL_HOURS;
        $button = '<p style="text-align:center;margin:26px 0;"><a href="' . e($link) . '" style="background:#C9A24B;color:#0B1F3A;'
            . 'text-decoration:none;font-weight:700;padding:13px 28px;border-radius:8px;display:inline-block;">Reset password</a></p>'
            . '<p style="font-size:12px;color:#0B1F3A;word-break:break-all;">Or paste this link into your browser:<br>' . e($link) . '</p>';
        $body = self::template(
            'Reset your password',
            "Hi " . e($name) . ", we received a request to reset your StockSense password.",
            $button,
            "This link works once and expires in {$hours} hours. If you did not request it, your password is unchanged."
        );
        return self::send($to, $name, 'Reset your StockSense password', $body, "Reset your password (valid {$hours} hours, one use): {$link}");
    }

    private static function template(string $title, string $intro, string $middle, string $footer): string
    {
        $app = e((string)Env::get('APP_NAME', 'StockSense'));
        return '<!doctype html><html><body style="margin:0;padding:24px;background:#F8F5EE;font-family:Arial,Helvetica,sans-serif;color:#0B1F3A;">'
            . '<table role="presentation" width="100%" style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #C9A24B;">'
            . '<tr><td style="background:#0B1F3A;padding:20px 28px;color:#C9A24B;font-size:22px;font-weight:700;">' . $app . '</td></tr>'
            . '<tr><td style="padding:28px;">'
            . '<h2 style="margin:0 0 10px;font-size:20px;color:#0B1F3A;">' . e($title) . '</h2>'
            . '<p style="margin:0;line-height:1.55;">' . $intro . '</p>' . $middle
            . '<p style="margin:0;font-size:13px;line-height:1.5;">' . e($footer) . '</p>'
            . '</td></tr></table></body></html>';
    }

    private static function send(string $to, string $toName, string $subject, string $html, string $text): array
    {
        $driver = strtolower((string)Env::get('MAIL_DRIVER', 'smtp'));
        if ($driver === 'log') {
            $dir = dirname(__DIR__, 2) . '/storage/logs';
            @file_put_contents(
                $dir . '/mail.log',
                "=== " . gmdate('c') . " | To: {$to} | Subject: {$subject}\n{$text}\n\n",
                FILE_APPEND | LOCK_EX
            );
            return ['ok' => true, 'code' => 'OK', 'message' => 'Logged'];
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = (string)Env::get('MAIL_HOST', 'smtp.gmail.com');
            $mail->Port       = Env::int('MAIL_PORT', 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = (string)Env::get('MAIL_USERNAME', '');
            $mail->Password   = (string)Env::get('MAIL_PASSWORD', '');
            $enc = strtolower((string)Env::get('MAIL_ENCRYPTION', 'tls'));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
            $mail->Timeout = 15;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(
                (string)Env::get('MAIL_FROM_ADDRESS', (string)Env::get('MAIL_USERNAME', 'no-reply@localhost')),
                (string)Env::get('MAIL_FROM_NAME', 'StockSense IMS')
            );
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text;
            $mail->send();
            return ['ok' => true, 'code' => 'OK', 'message' => 'Sent'];
        } catch (MailException $e) {
            $info = $mail->ErrorInfo ?: $e->getMessage();
            Logger::error('Mail send failed', ['to' => $to, 'error' => $info]);
            if (self::looksLikeNoConnection($info)) {
                return ['ok' => false, 'code' => 'NO_INTERNET', 'message' => "You haven't internet connection."];
            }
            return ['ok' => false, 'code' => 'MAIL_FAILED', 'message' => 'Email sending failed.'];
        }
    }

    private static function looksLikeNoConnection(string $info): bool
    {
        $needles = ['could not connect', 'getaddrinfo', 'php_network_getaddresses', 'name or service not known',
                    'network is unreachable', 'connection timed out', 'connection refused', 'no such host'];
        $low = strtolower($info);
        foreach ($needles as $n) {
            if (str_contains($low, $n)) {
                return true;
            }
        }
        return false;
    }
}
