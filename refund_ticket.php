<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['ticket_id'])) {
    header("Location: ticket.php");
    exit;
}

$ticket_id = intval($_POST['ticket_id']);
$user_id = $_SESSION['user_id'];

// Bilet bilgilerini çek
$stmt = $pdo->prepare("SELECT total_price, status FROM Tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı!");
}

// Zaten iptal edildiyse hata ver
if ($ticket['status'] === 'cancelled') {
    die("Bu bilet zaten iptal edilmiş!");
}

// İade işlemi - Status'u cancelled yap, silme!
$stmt = $pdo->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$ticket_id]);

// Booked_Seats tablosundan koltukları temizle
$stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
$stmt->execute([$ticket_id]);

// Kullanıcıya parasını iade et
$stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
$stmt->execute([$ticket['total_price'], $user_id]);

// Başarılı, yönlendir
header("Location: ticket.php?refund=success");
exit;
?>