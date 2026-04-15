<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$body = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if (!$username || !$password) {
    echo json_encode(['success'=>false,'message'=>'Login va parol majburiy']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    echo json_encode(['success'=>false,'message'=>"Login yoki parol noto'g'ri"]);
    exit;
}

$token = bin2hex(random_bytes(32));
echo json_encode([
    'success' => true,
    'token'   => $token,
    'admin'   => ['id'=>$admin['id'], 'username'=>$admin['username']]
]);