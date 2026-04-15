<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $db->query("
        SELECT 
            qr.id,
            u.full_name,
            u.email,
            s.name as subject_name,
            s.icon as subject_icon,
            qr.score,
            qr.total,
            ROUND(qr.score/qr.total*100) as percent,
            qr.time_spent,
            qr.taken_at
        FROM quiz_results qr
        JOIN users u ON u.id = qr.user_id
        JOIN subjects s ON s.name = qr.subject OR s.id = qr.user_id
        ORDER BY qr.taken_at DESC
        LIMIT 100
    ");
    echo json_encode(['success'=>true,'results'=>$stmt->fetchAll()]);
    exit;
}

if ($action === 'stats') {
    $total_users   = $db->query("SELECT COUNT(*) FROM users WHERE is_verified=1")->fetchColumn();
    $total_results = $db->query("SELECT COUNT(*) FROM quiz_results")->fetchColumn();
    $avg_score     = $db->query("SELECT ROUND(AVG(score/total*100)) FROM quiz_results")->fetchColumn();
    $today         = $db->query("SELECT COUNT(*) FROM quiz_results WHERE DATE(taken_at)=CURDATE()")->fetchColumn();

    echo json_encode([
        'success'       => true,
        'total_users'   => $total_users,
        'total_results' => $total_results,
        'avg_score'     => $avg_score ?: 0,
        'today'         => $today
    ]);
    exit;
}

echo json_encode(['success'=>false,'message'=>"Noto'g'ri so'rov"]);