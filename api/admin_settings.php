<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$db = getDB();

// GET — sozlamalarni olish
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("SELECT * FROM settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['key_name']] = $row['value'];
    }
    echo json_encode(['success'=>true, 'settings'=>$settings]);
    exit;
}

// POST — saqlash
$body = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

if ($action === 'save') {
    $quiz_time       = (int)($body['quiz_time'] ?? 300);
    $questions_count = (int)($body['questions_count'] ?? 10);

    if ($quiz_time < 30 || $quiz_time > 3600) {
        echo json_encode(['success'=>false,'message'=>"Vaqt 30-3600 sekund oralig'ida bo'lishi kerak"]);
        exit;
    }
    if ($questions_count < 1 || $questions_count > 50) {
        echo json_encode(['success'=>false,'message'=>"Savollar soni 1-50 oralig'ida bo'lishi kerak"]);
        exit;
    }

    $db->prepare("UPDATE settings SET value=? WHERE key_name='quiz_time'")->execute([$quiz_time]);
    $db->prepare("UPDATE settings SET value=? WHERE key_name='questions_count'")->execute([$questions_count]);

    echo json_encode(['success'=>true,'message'=>'Sozlamalar saqlandi!']);
    exit;
}

echo json_encode(['success'=>false,'message'=>"Noto'g'ri so'rov"]);