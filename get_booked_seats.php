<?php
// 1. ADIM: Session'ı başlat ve veritabanını dahil et
session_start();
include 'db.php';

header('Content-Type: application/json');

// 2. ADIM: NÖBETÇİ KONTROLÜ
// Kullanıcı giriş yapmış mı? Yapmamışsa, kapıdan geri çevir.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 Unauthorized (Yetkisiz) HTTP durum kodu gönder
    echo json_encode(['error' => 'Bu bilgiyi görmek için giriş yapmalısınız.']);
    exit;
}

// Trip ID gelmemişse boş cevap ver (bu kısım zaten doğruydu)
if (!isset($_GET['trip_id'])) {
    echo json_encode([]);
    exit;
}

$trip_id = (int)$_GET['trip_id'];

try {
    // Veritabanı sorgun zaten güvenli ve doğru, ona dokunmuyoruz.
    $stmt = $pdo->prepare("SELECT seat_number FROM Tickets WHERE trip_id = ? AND status = 'active'");
    // Not: Booked_Seats tablosuna gitmene gerek yok, Tickets tablosu daha direkt.
    $stmt->execute([$trip_id]);
    $booked_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($booked_seats);
} catch (PDOException $e) {
    // Bir hata olursa logla ama kullanıcıya detay verme
    error_log("Koltuk çekme hatası: " . $e->getMessage());
    echo json_encode([]); // Hata durumunda boş dizi döndür
}
?>
