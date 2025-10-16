<?php
include 'db.php';
session_start();
header('Content-Type: application/json');

// 1. GİRİŞ KONTROLÜ (Bu kısım zaten doğruydu)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Bilet almak için giriş yapmalısınız.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$trip_id = $data['trip_id'] ?? null;
$selected_seats = $data['seats'] ?? [];
$user_id = $_SESSION['user_id'];

if (!$trip_id || empty($selected_seats)) {
    echo json_encode(['success' => false, 'message' => 'Sefer veya koltuk bilgisi eksik.']);
    exit;
}

// 2. TRANSACTION BAŞLATMA (Bu kısım zaten mükemmeldi)
$pdo->beginTransaction();
try {
    // 3. SEFER VE FİYAT BİLGİLERİNİ ÇEKME (Bu kısım zaten doğruydu)
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception("Sefer bulunamadı!");
    }

    $original_price_per_seat = (float)$trip['price'];
    $total_original_price = $original_price_per_seat * count($selected_seats);
    $trip_company_id = $trip['company_id'] ?? null;

    // 4. KUPON MANTIĞI (Bu kısım çok iyiydi, aynen korundu)
    $discount_amount = 0.0;
    $coupon_code = null;
    $applies_coupon = false;
    if ($trip_company_id && isset($_SESSION['discount_applied'][$trip_company_id])) {
        $stmt_coupon = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND usage_limit > 0 AND datetime(expire_date) >= datetime('now') AND CAST(company_id AS INTEGER) = ?");
        $stmt_coupon->execute([$_SESSION['discount_applied'][$trip_company_id]['code'], $trip_company_id]);
        $db_coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);
        if ($db_coupon) {
            $applies_coupon = true;
            $discount_amount = (float)$db_coupon['discount'];
            $coupon_code = $db_coupon['code'];
        } else {
            unset($_SESSION['discount_applied'][$trip_company_id]);
        }
    }

    $final_price = $total_original_price - $discount_amount;
    if ($final_price < 0) $final_price = 0;

    // 5. BAKİYE KONTROLÜ (Bu kısım zaten doğruydu)
    $stmt = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_balance = $stmt->fetchColumn();

    if ($user_balance < $final_price) {
        throw new Exception("Yetersiz bakiye!");
    }

    // 6. DOLU KOLTUK KONTROLÜ (Bu kısım daha basit hale getirildi ve düzeltildi)
    $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
    // Not: Artık Booked_Seats yerine direkt Tickets tablosunu kontrol edebiliriz.
    $stmt_check = $pdo->prepare("SELECT seat_number FROM Tickets WHERE trip_id = ? AND seat_number IN ($placeholders) AND status = 'active'");
    $params = array_merge([$trip_id], $selected_seats);
    $stmt_check->execute($params);
    $already_booked = $stmt_check->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($already_booked)) {
        throw new Exception("Hata: " . implode(', ', $already_booked) . " numaralı koltuk(lar) siz işlem yaparken satıldı.");
    }
    
    // ======================================================================
    //                        DEĞİŞEN KISIM BAŞLIYOR
    // ======================================================================

    // 7. HER KOLTUK İÇİN AYRI BİLET OLUŞTURMA (YENİ MANTIK)
    
    // Her bir bilete düşen nihai fiyatı hesaplayalım.
    $final_price_per_seat = $final_price / count($selected_seats);

    $stmt_ticket = $pdo->prepare("INSERT INTO Tickets (trip_id, user_id, seat_number, total_price, status) VALUES (?, ?, ?, ?, 'active')");
    
    foreach ($selected_seats as $seat) {
        // Her bir koltuk için 'Tickets' tablosuna KENDİ NUMARASI ve KENDİ FİYATI ile yeni bir kayıt oluşturuyoruz.
        $stmt_ticket->execute([$trip_id, $user_id, $seat, $final_price_per_seat]);
    }
    
    // Not: Booked_Seats tablosuna artık gerek kalmadı, çünkü her bilgi Tickets tablosunda mevcut.
    // Eğer o tabloyu hala kullanmak istersen, yukarıdaki döngünün içine ekleyebilirsin.

    // 8. BAKİYE DÜŞME VE KUPON GÜNCELLEME (Bu kısımlar doğruydu, döngü dışında kalmalı)
    $new_balance = $user_balance - $final_price;
    $stmt = $pdo->prepare("UPDATE User SET balance = ? WHERE id = ?");
    $stmt->execute([$new_balance, $user_id]);
    $_SESSION['balance'] = $new_balance;

    if ($applies_coupon && $coupon_code) {
        $stmt = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE code = ?");
        $stmt->execute([$coupon_code]);
        unset($_SESSION['discount_applied'][$trip_company_id]);
    }
    
    // ======================================================================
    //                         DEĞİŞEN KISIM BİTTİ
    // ======================================================================

    // 9. İŞLEMİ ONAYLAMA (Bu kısım zaten doğruydu)
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Biletler başarıyla alındı!', 'seat_numbers' => implode(', ', $selected_seats), 'final_price' => number_format($final_price, 2)]);

} catch (Exception $e) {
    // 10. HATA YÖNETİMİ (Bu kısım zaten mükemmeldi)
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
