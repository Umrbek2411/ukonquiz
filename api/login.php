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
$email = strtolower(trim($body['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>"Email manzilini to'g'ri kiriting"]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email=? AND is_verified=1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success'=>false,'message'=>"Bu email bilan hisob topilmadi. Ro'yxatdan o'ting."]);
    exit;
}

$otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = gmdate('Y-m-d H:i:s', strtotime('+10 minutes') + 18000);
$db->prepare("DELETE FROM otp_codes WHERE email=? AND purpose='login'")->execute([$email]);
$db->prepare("INSERT INTO otp_codes (email,code,purpose,expires_at) VALUES (?,?,'login',?)")
   ->execute([$email, $otp, $expires]);

if (!sendOTPEmail($email, $user['full_name'], $otp)) {
    echo json_encode(['success'=>false,'message'=>"Email yuborishda xato."]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => "Kirish kodi emailingizga yuborildi"
]);