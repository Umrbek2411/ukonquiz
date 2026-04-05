<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); exit; 
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Faqat POST']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$email   = strtolower(trim($body['email']   ?? ''));
$code    = trim($body['code']    ?? '');
$purpose = trim($body['purpose'] ?? 'register');

if (!$email || !$code) {
    echo json_encode(['success'=>false,'message'=>'Email va kod majburiy']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT id FROM otp_codes
    WHERE email=? AND code=? AND purpose=?
    AND used=0 AND expires_at > DATE_SUB(NOW(), INTERVAL 5 HOUR)
    LIMIT 1
");
$stmt->execute([$email, $code, $purpose]);
$otp = $stmt->fetch();

if (!$otp) {
    echo json_encode(['success'=>false,'message'=>"Kod noto'g'ri yoki muddati o'tgan"]);
    exit;
}

$db->prepare("UPDATE otp_codes SET used=1 WHERE id=?")->execute([$otp['id']]);
$db->prepare("UPDATE users SET is_verified=1, last_login=NOW() WHERE email=?")->execute([$email]);

$stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

$token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 days'));
$db->prepare("INSERT INTO sessions (user_id,token,expires_at) VALUES (?,?,?)")
   ->execute([$user['id'], $token, $expires]);

echo json_encode([
    'success' => true,
    'message' => 'Muvaffaqiyatli tasdiqlandi',
    'token'   => $token,
    'user'    => [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'phone'     => $user['phone'],
    ]
]);