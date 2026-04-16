<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); exit; 
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Faqat POST']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$token = trim($body['token'] ?? '');

$db   = getDB();
$stmt = $db->prepare("
    SELECT u.* FROM users u
    JOIN sessions s ON s.user_id=u.id
    WHERE s.token=? AND s.expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success'=>false,'message'=>'Sessiya topilmadi']);
    exit;
}

$subject    = trim($body['subject']     ?? '');
$score      = (int)($body['score']      ?? 0);
$total      = (int)($body['total']      ?? 10);
$time_spent = (int)($body['time_spent'] ?? 0);

$db->prepare("INSERT INTO quiz_results (user_id, subject, score, total, time_spent) VALUES (?,?,?,?,?)")
   ->execute([$user['id'], $subject, $score, $total, $time_spent]);

$percent = round($score / $total * 100);
$min     = floor($time_spent / 60);
$sec     = $time_spent % 60;
$time_str = $min > 0 ? "{$min} daqiqa {$sec} soniya" : "{$sec} soniya";

if ($percent >= 90)      { $medal = '🏆'; $baho = "Mukammal!"; }
elseif ($percent >= 70)  { $medal = '🥇'; $baho = "Ajoyib!"; }
elseif ($percent >= 50)  { $medal = '🥈'; $baho = "Yaxshi!"; }
else                     { $medal = '🥉'; $baho = "Davom eting!"; }

// Email yuborish
$mail_body = "
<div style='font-family:Arial,sans-serif;max-width:560px;margin:auto;
            background:#0d0d0d;color:#fff;padding:40px;border-radius:16px;
            border:1px solid #ff3300;'>
    <div style='text-align:center;margin-bottom:24px;'>
        <span style='font-size:32px;font-weight:900;color:#ff6b00;'>UKON Quiz</span>
    </div>
    <p>Salom, <strong style='color:#ffd700;'>{$user['full_name']}</strong>!</p>
    <p style='color:#aaa;margin:12px 0'>Siz <strong style='color:#fff'>{$subject}</strong> fanidan test topshirdingiz.</p>

    <div style='text-align:center;margin:24px 0;'>
        <div style='font-size:60px;'>{$medal}</div>
        <div style='font-size:18px;font-weight:700;color:#ffd700;margin-top:8px;'>{$baho}</div>
    </div>

    <div style='display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin:24px 0;'>
        <div style='background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);
                    border-radius:12px;padding:16px;text-align:center;'>
            <div style='font-size:28px;font-weight:900;color:#22c55e;'>{$score}</div>
            <div style='font-size:12px;color:#aaa;margin-top:4px;'>TO'G'RI</div>
        </div>
        <div style='background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);
                    border-radius:12px;padding:16px;text-align:center;'>
            <div style='font-size:28px;font-weight:900;color:#ef4444;'>".($total-$score)."</div>
            <div style='font-size:12px;color:#aaa;margin-top:4px;'>NOTO'G'RI</div>
        </div>
        <div style='background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.3);
                    border-radius:12px;padding:16px;text-align:center;'>
            <div style='font-size:28px;font-weight:900;color:#ffd700;'>{$percent}%</div>
            <div style='font-size:12px;color:#aaa;margin-top:4px;'>NATIJA</div>
        </div>
    </div>

    <div style='background:rgba(255,107,0,0.08);border:1px solid rgba(255,107,0,0.2);
                border-radius:12px;padding:16px;text-align:center;margin-bottom:24px;'>
        <div style='font-size:14px;color:#aaa;'>Sarflangan vaqt</div>
        <div style='font-size:20px;font-weight:700;color:#ff6b00;margin-top:4px;'>{$time_str}</div>
    </div>

    <p style='color:#666;font-size:13px;text-align:center;'>
        UKON Quiz platformasida bilimingizni sinab ko'ring!
    </p>
</div>";

// sendResultEmail($user['email'], $user['full_name'], $mail_body);

echo json_encode([
    'success' => true,
    'message' => 'Natija saqlandi',
    'percent' => $percent
]);