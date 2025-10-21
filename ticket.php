<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT full_name, balance, role FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Session'daki bilgileri taze tutalım
$_SESSION['balance'] = $user['balance'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'] ?? 'user';

// Kullanıcının biletlerini çek 
$stmt = $pdo->prepare("
    SELECT 
        t.id AS ticket_id, 
        t.status,
        tr.departure_city, 
        tr.destination_city, 
        tr.departure_time, 
        tr.arrival_time, 
        t.total_price,
        t.seat_number,
        bc.name AS company_name
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    WHERE t.user_id = ? AND t.status = 'active'
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biletlerim</title>
<link rel="stylesheet" href="style.css">
<style>


body {
    background-color: #f4f7f6;
    display: block !important;
    height: auto !important;
    min-height: 100vh;
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="index.php">Anasayfa</a>
    </div>
    <div class="nav-right">
        <span class="balance-badge">₺<?= htmlspecialchars($_SESSION['balance']) ?></span>
        
        <?php if($_SESSION['role'] === 'user'): ?>
            <a href="ticket.php">Biletlerim</a>
        <?php elseif($_SESSION['role'] === 'comp_admin'): ?>
            <a href="company.php">Şirketim</a>
        <?php endif; ?>
        
        <a href="profile.php">Profilim</a>
        <a href="logout.php">Çıkış Yap</a>
    </div>
</nav>

<div class="ticket-container">
    <h1 class="container-title">Aktif Biletlerim</h1>
    <?php if(count($tickets) === 0): ?>
        <p>Henüz satın alınmış aktif biletiniz yok.</p>
    <?php else: ?>
        <?php foreach($tickets as $ticket): ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <h3 class="ticket-route"><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></h3>
                    <?php if ($ticket['company_name']): ?>
                        <div class="company-name"><?= htmlspecialchars($ticket['company_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="ticket-body">
                    <p><strong>Kalkış:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['departure_time']))) ?></p>
                    <p><strong>Varış (Tahmini):</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['arrival_time']))) ?></p>
                    <p><strong>Koltuk No:</strong> <?= htmlspecialchars($ticket['seat_number']) ?></p>
                    <p><strong>Ödenen Fiyat:</strong> ₺<?= htmlspecialchars(number_format($ticket['total_price'], 2, ',', '.')) ?></p>
                    <p><strong>Bilet ID:</strong> <?= htmlspecialchars($ticket['ticket_id']) ?></p>
                </div>

                <div class="ticket-footer">
                    <a href="download_ticket.php?ticket_id=<?= $ticket['ticket_id'] ?>" class="btn btn-primary">
                        📥 PDF İndir
                    </a>
                    
                    <form method="POST" action="refund_ticket.php" class="refund-form">
                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
                        <button type="submit" class="btn btn-danger">
                            ❌ İade Et
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
