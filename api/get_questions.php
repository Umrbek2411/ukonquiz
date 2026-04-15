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

// Fan nomini topish
$stmt = $db->prepare("SELECT * FROM subjects WHERE LOWER(name) LIKE ? OR id=? LIMIT 1");
$slug = $subject_slug === 'math' ? '%matematik%' : '%dasturlash%';
$id   = (int)$subject_slug;
$stmt->execute([$slug, $id]);
$subject = $stmt->fetch();

if (!$subject) {
    echo json_encode(['success'=>false,'message'=>'Fan topilmadi']);
    exit;
}

// Savollarni olish
$stmt = $db->prepare("
    SELECT id, question, opt_a, opt_b, opt_c, opt_d, correct_ans 
    FROM questions 
    WHERE subject_id = ? 
    ORDER BY RAND() 
    LIMIT 10
");
$stmt->execute([$subject['id']]);
$questions = $stmt->fetchAll();

echo json_encode([
    'success'  => true,
    'subject'  => $subject,
    'questions'=> $questions
]);