<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$db = getDB();
$action = $_GET['action'] ?? '';

// GET — fanlar ro'yxati
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $stmt = $db->query("
            SELECT s.*, COUNT(q.id) as question_count 
            FROM subjects s 
            LEFT JOIN questions q ON q.subject_id = s.id 
            GROUP BY s.id 
            ORDER BY s.id
        ");
        echo json_encode(['success'=>true, 'subjects'=>$stmt->fetchAll()]);
        exit;
    }
}

// POST
$body = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

// Fan qo'shish
if ($action === 'add') {
    $name = trim($body['name'] ?? '');
    $icon = trim($body['icon'] ?? '📚');
    $desc = trim($body['description'] ?? '');
    
    if (!$name) {
        echo json_encode(['success'=>false,'message'=>'Fan nomi majburiy']);
        exit;
    }
    
    $db->prepare("INSERT INTO subjects (name, icon, description) VALUES (?,?,?)")
       ->execute([$name, $icon, $desc]);
    
    echo json_encode(['success'=>true,'message'=>'Fan qo\'shildi!','id'=>$db->lastInsertId()]);
    exit;
}

// Fan o'chirish
if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID majburiy']); exit; }
    $db->prepare("DELETE FROM subjects WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true,'message'=>'Fan o\'chirildi!']);
    exit;
}

// Fan tahrirlash
if ($action === 'edit') {
    $id   = (int)($body['id'] ?? 0);
    $name = trim($body['name'] ?? '');
    $icon = trim($body['icon'] ?? '📚');
    $desc = trim($body['description'] ?? '');
    
    if (!$id || !$name) {
        echo json_encode(['success'=>false,'message'=>'ID va nom majburiy']);
        exit;
    }
    
    $db->prepare("UPDATE subjects SET name=?, icon=?, description=? WHERE id=?")
       ->execute([$name, $icon, $desc, $id]);
    
    echo json_encode(['success'=>true,'message'=>'Fan yangilandi!']);
    exit;
}

echo json_encode(['success'=>false,'message'=>"Noto'g'ri so'rov"]);