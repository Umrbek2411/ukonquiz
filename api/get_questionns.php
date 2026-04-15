<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

$db = getDB();

$subject_id = (int)($_GET['subject'] ?? 0);

if (!$subject_id) {
    echo json_encode(['success'=>false,'message'=>'Fan ID majburiy']);
    exit;
}

// Sozlamalarni olish
$stmt = $db->query("SELECT key_name, value FROM settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['key_name']] = $row['value'];
}
$questions_count = (int)($settings['questions_count'] ?? 10);
$quiz_time       = (int)($settings['quiz_time'] ?? 300);

// Fanni topish
$stmt = $db->prepare("SELECT * FROM subjects WHERE id=? LIMIT 1");
$stmt->execute([$subject_id]);
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
    LIMIT ?
");
$stmt->execute([$subject_id, $questions_count]);
$questions = $stmt->fetchAll();

if (count($questions) < $questions_count) {
    echo json_encode([
        'success'  => false,
        'message'  => "Bu fanda yetarli savol yo'q. Kerakli: {$questions_count}, mavjud: ".count($questions)
    ]);
    exit;
}

echo json_encode([
    'success'   => true,
    'subject'   => $subject,
    'questions' => $questions,
    'quiz_time' => $quiz_time
]);