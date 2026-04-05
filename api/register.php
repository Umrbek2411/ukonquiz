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
$name  = trim($body['full_name'] ?? '');
$phone = trim($body['phone']     ?? '');
$email = strtolower(trim($body['email'] ?? ''));

if (mb_strlen($name) < 3 || strpos($name, ' ') === false) {
    echo json_encode(['success'=>false,'message'=>"To'liq ism familiyangizni kiriting"]);
    exit;
}
if (strlen($phone) < 7) {
    echo json_encode(['success'=>false,'message'=>"Telefon raqamini kiriting"]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>"Email manzilini to'g'ri kiriting"]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, is_verified FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $user['is_verified'] == 1) {
    echo json_encode(['success'=>false,'message'=>"Bu email allaqachon ro'yxatdan o'tgan"]);
    exit;
}

if (!$user) {
    $db->prepare("INSERT INTO users (full_name, phone, email) VALUES (?,?,?)")
       ->execute([$name, $phone, $email]);
} else {
    $db->prepare("UPDATE users SET full_name=?, phone=? WHERE email=?")
       ->execute([$name, $phone, $email]);
}

$otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = gmdate('Y-m-d H:i:s', strtotime('+10 minutes') + 18000);
$db->prepare("DELETE FROM otp_codes WHERE email=? AND purpose='register'")->execute([$email]);
$db->prepare("INSERT INTO otp_codes (email,code,purpose,expires_at) VALUES (?,?,'register',?)")
   ->execute([$email, $otp, $expires]);

if (!sendOTPEmail($email, $name, $otp)) {
    echo json_encode(['success'=>false,'message'=>"Email yuborishda xato. SMTP sozlamalarini tekshiring."]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => "Tasdiqlash kodi emailingizga yuborildi",
    'email'   => $email
]);