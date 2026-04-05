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

if (!in_array($subject, ['math','code'])) {
    echo json_encode(['success'=>false,'message'=>"Noto'g'ri fan"]);
    exit;
}

$db->prepare("INSERT INTO quiz_results (user_id,subject,score,total,time_spent) VALUES (?,?,?,?,?)")
   ->execute([$user['id'], $subject, $score, $total, $time_spent]);

echo json_encode([
    'success' => true,
    'message' => 'Natija saqlandi',
    'percent' => round($score/$total*100)
]);