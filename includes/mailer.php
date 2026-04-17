<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Mailjet SMTP sozlamalari
define('SMTP_HOST', 'in-v3.mailjet.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '30097bdb6f8e9eff7a84f4479c174d88');
define('SMTP_PASS', '4b573c924ba07bc983659570a58090c1');

define('MAIL_FROM', 'karimovu960@gmail.com');
define('MAIL_NAME', 'UKON Quiz');

/**
 * OTP kodni emailga yuborish
 */
function sendOTPEmail(string $toEmail, string $toName, string $otp): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'UKON Quiz — Tasdiqlash kodi';

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0d0d0d; color: #ffffff; padding: 24px; border: 1px solid #ff3300; border-radius: 12px;'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h1 style='margin: 0; color: #ff6b00;'>UKON Quiz</h1>
                </div>

                <p>Salom, <strong style='color: #ffd700;'>{$toName}</strong>!</p>

                <p style='color: #cccccc;'>
                    Sizning tasdiqlash kodingiz quyidagicha:
                </p>

                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; letter-spacing: 8px; font-size: 32px; font-weight: bold; color: #ff6b00; background: rgba(255,107,0,0.1); padding: 16px 24px; border: 1px solid rgba(255,107,0,0.35); border-radius: 10px;'>
                        {$otp}
                    </div>
                </div>

                <p style='color: #aaaaaa; font-size: 14px;'>
                    ⏱️ Kod <strong style='color: #ffd700;'>10 daqiqa</strong> amal qiladi.
                </p>

                <p style='color: #aaaaaa; font-size: 14px;'>
                    🔒 Ushbu kodni hech kimga bermang.
                </p>
            </div>
        ";

        $mail->AltBody = "Salom {$toName}! Sizning UKON Quiz tasdiqlash kodingiz: {$otp}. Kod 10 daqiqa amal qiladi.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer xato: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Test natijasini emailga yuborish
 */
function sendResultEmail(string $toEmail, string $toName, string $subjectName, int $score, int $total, int $percent): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'UKON Quiz — Test natijangiz';

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0d0d0d; color: #ffffff; padding: 24px; border: 1px solid #ff3300; border-radius: 12px;'>
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h1 style='margin: 0; color: #ff6b00;'>UKON Quiz</h1>
                </div>

                <p>Salom, <strong style='color: #ffd700;'>{$toName}</strong>!</p>

                <p style='color: #cccccc;'>
                    Siz <strong>{$subjectName}</strong> fanidan test ishladingiz.
                </p>

                <div style='background: rgba(255,255,255,0.04); padding: 16px; border-radius: 10px; margin: 20px 0;'>
                    <p style='margin: 8px 0;'>✅ To‘g‘ri javoblar: <strong>{$score}</strong></p>
                    <p style='margin: 8px 0;'>❌ Noto‘g‘ri javoblar: <strong>" . ($total - $score) . "</strong></p>
                    <p style='margin: 8px 0;'>📘 Jami savollar: <strong>{$total}</strong></p>
                    <p style='margin: 8px 0;'>📊 Natija: <strong style='color: #ffd700;'>{$percent}%</strong></p>
                </div>

                <p style='color: #aaaaaa;'>
                    UKON Quiz bilan bilimingizni oshirishda davom eting!
                </p>
            </div>
        ";

        $mail->AltBody = "Salom {$toName}! {$subjectName} fanidan natijangiz: {$score}/{$total} ({$percent}%).";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Result mailer xato: ' . $mail->ErrorInfo);
        return false;
    }
}
