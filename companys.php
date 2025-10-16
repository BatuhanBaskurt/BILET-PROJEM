<?php
header('Content-Type: application/json');

include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Yetkisiz erişim!']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name, logo_path, created_at FROM Bus_Company");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($companies);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Hata: ' . $e->getMessage()]);
}
exit;
?>
