<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

$db = getDB();

$subject_slug = $_GET['subject'] ?? '';

if (!$subject_slug) {
    echo json_encode(['success'=>false,'message'=>'Fan nomi majburiy']);
    exit;
}

// Fan nomini topish (faqat ID bo'yicha aniq qidirish)
$stmt = $db->prepare("SELECT * FROM subjects WHERE id = ? LIMIT 1");
$id = (int)$subject_slug;
$stmt->execute([$id]);
$subject = $stmt->fetch();

if (!$subject) {
    echo json_encode(['success'=>false,'message'=>'Fan topilmadi']);
    exit;
}

// Savollar sonini sozlamalardan olish
$stmt = $db->prepare("SELECT value FROM settings WHERE key_name='questions_count' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch();
$limit = $row ? (int)$row['value'] : 10;
if ($limit <= 0) { $limit = 10; }

// Savollarni olish
$stmt = $db->prepare("
    SELECT id, question, opt_a, opt_b, opt_c, opt_d, correct_ans 
    FROM questions 
    WHERE subject_id = ? 
    ORDER BY RAND() 
    LIMIT $limit
");
$stmt->execute([$subject['id']]);
$questions = $stmt->fetchAll();

echo json_encode([
    'success'  => true,
    'subject'  => $subject,
    'questions'=> $questions
]);