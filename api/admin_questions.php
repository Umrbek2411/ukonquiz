<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$db = getDB();
$action = $_GET['action'] ?? '';

// GET — savollar ro'yxati
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $subject_id = (int)($_GET['subject_id'] ?? 0);
        if ($subject_id) {
            $stmt = $db->prepare("
                SELECT q.*, s.name as subject_name 
                FROM questions q 
                JOIN subjects s ON s.id = q.subject_id 
                WHERE q.subject_id = ? 
                ORDER BY q.id
            ");
            $stmt->execute([$subject_id]);
        } else {
            $stmt = $db->query("
                SELECT q.*, s.name as subject_name 
                FROM questions q 
                JOIN subjects s ON s.id = q.subject_id 
                ORDER BY s.id, q.id
            ");
        }
        echo json_encode(['success'=>true,'questions'=>$stmt->fetchAll()]);
        exit;
    }
}

// POST
$body = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

// Savol qo'shish
if ($action === 'add') {
    $subject_id = (int)($body['subject_id'] ?? 0);
    $question   = trim($body['question'] ?? '');
    $opt_a      = trim($body['opt_a'] ?? '');
    $opt_b      = trim($body['opt_b'] ?? '');
    $opt_c      = trim($body['opt_c'] ?? '');
    $opt_d      = trim($body['opt_d'] ?? '');
    $correct    = (int)($body['correct_ans'] ?? 0);

    if (!$subject_id || !$question || !$opt_a || !$opt_b || !$opt_c || !$opt_d) {
        echo json_encode(['success'=>false,'message'=>'Barcha maydonlar majburiy']);
        exit;
    }
    if ($correct < 0 || $correct > 3) {
        echo json_encode(['success'=>false,'message'=>"To'g'ri javob 0-3 oralig'ida bo'lishi kerak"]);
        exit;
    }

    $db->prepare("
        INSERT INTO questions (subject_id, question, opt_a, opt_b, opt_c, opt_d, correct_ans) 
        VALUES (?,?,?,?,?,?,?)
    ")->execute([$subject_id, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct]);

    echo json_encode(['success'=>true,'message'=>'Savol qo\'shildi!','id'=>$db->lastInsertId()]);
    exit;
}

// Savol o'chirish
if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID majburiy']); exit; }
    $db->prepare("DELETE FROM questions WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true,'message'=>'Savol o\'chirildi!']);
    exit;
}

// Savol tahrirlash
if ($action === 'edit') {
    $id         = (int)($body['id'] ?? 0);
    $question   = trim($body['question'] ?? '');
    $opt_a      = trim($body['opt_a'] ?? '');
    $opt_b      = trim($body['opt_b'] ?? '');
    $opt_c      = trim($body['opt_c'] ?? '');
    $opt_d      = trim($body['opt_d'] ?? '');
    $correct    = (int)($body['correct_ans'] ?? 0);

    if (!$id || !$question || !$opt_a || !$opt_b || !$opt_c || !$opt_d) {
        echo json_encode(['success'=>false,'message'=>'Barcha maydonlar majburiy']);
        exit;
    }

    $db->prepare("
        UPDATE questions SET question=?, opt_a=?, opt_b=?, opt_c=?, opt_d=?, correct_ans=? WHERE id=?
    ")->execute([$question, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $id]);

    echo json_encode(['success'=>true,'message'=>'Savol yangilandi!']);
    exit;
}

echo json_encode(['success'=>false,'message'=>"Noto'g'ri so'rov"]);