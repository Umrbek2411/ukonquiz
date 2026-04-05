<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ukonquiz_db');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'admin'); // phpmyadmin paroli

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'DB xato: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}