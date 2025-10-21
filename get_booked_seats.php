<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// 2. ADIM: NÖBETÇİ KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 Unauthorized (Yetkisiz) HTTP durum kodu gönder
    echo json_encode(['error' => 'Bu bilgiyi görmek için giriş yapmalısınız.']);
    exit;
}

// Trip ID gelmemişse boş cevap ver 
if (!isset($_GET['trip_id'])) {
    echo json_encode([]);
    exit;
}

$trip_id = (int)$_GET['trip_id'];

try {

    $stmt = $pdo->prepare("SELECT seat_number FROM Tickets WHERE trip_id = ? AND status = 'active'");
 
    $stmt->execute([$trip_id]);
    $booked_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($booked_seats);
} catch (PDOException $e) {
    
    error_log("Koltuk çekme hatası: " . $e->getMessage());
    echo json_encode([]); 
}
?>
