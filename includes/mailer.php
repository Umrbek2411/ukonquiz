<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('SMTP_HOST', 'in-v3.mailjet.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '30097bdb6f8e9eff7a84f4479c174d88');
define('SMTP_PASS', '4b573c924ba07bc983659570a58090c1');
define('MAIL_FROM', 'karimovu960@gmail.com');
define('MAIL_NAME', 'UKON Quiz');

function sendOTPEmail(string $toEmail, string $toName, string $otp): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = 'UKON Quiz — Tasdiqlash kodingiz';
        $mail->isHTML(true);
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;
                    background:#0d0d0d;color:#fff;padding:40px;border-radius:16px;
                    border:1px solid #ff3300;'>
            <div style='text-align:center;margin-bottom:24px;'>
                <span style='font-size:32px;font-weight:900;color:#ff6b00;'>
                    UKON Quiz
                </span>
            </div>
            <p>Salom, <strong style='color:#ffd700;'>{$toName}</strong>!</p>
            <p style='color:#aaa;margin-bottom:24px;'>Tasdiqlash kodingiz:</p>
            <div style='text-align:center;letter-spacing:16px;font-size:42px;
                        font-weight:900;color:#ff6b00;padding:20px 0;
                        background:rgba(255,107,0,0.1);border-radius:12px;
                        border:1px solid rgba(255,107,0,0.3);margin-bottom:24px;'>
                {$otp}
            </div>
            <p style='color:#666;font-size:13px;'>
                ⏱️ Kod <strong style='color:#ffd700;'>10 daqiqa</strong> ichida eskiradi.<br>
                🔒 Kodni hech kimga bermang.
            </p>
        </div>";
        $mail->AltBody = "Salom {$toName}! UKON Quiz tasdiqlash kodi: {$otp}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer xato: ' . $e->getMessage());
        return false;
    }
}
function sendResultEmail(string $toEmail, string $toName, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = 'UKON Quiz — Test natijangiz';
        $mail->isHTML(true);
        $mail->Body    = $body;
        $mail->AltBody = "Salom {$toName}! Test natijangiz tayyor. Emailingizni oching.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Result mailer xato: ' . $e->getMessage());
        return false;
    }
}