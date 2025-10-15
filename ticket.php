<?php
session_start();
include 'db.php';

// Kullanıcı giriş kontrolü
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

// Kullanıcının biletlerini çek - SADECE AKTİF BİLETLER (status = 'active')
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
:root {
    --primary-color: #007bff;
    --danger-color: #dc3545;
    --light-blue: rgba(173, 216, 230, 0.35);
    --light-blue-border: rgba(173, 216, 230, 0.45);
}

body {
    background-color: #f4f7f6;
    display: block !important;
    height: auto !important;
    min-height: 100vh;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background: var(--light-blue);
    backdrop-filter: blur(14px) saturate(130%);
    border-bottom: 1px solid var(--light-blue-border);
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}

.nav-left a, .nav-right a {
    margin-right: 15px;
    text-decoration: none;
    font-weight: 600;
    color: #000;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background-color 0.25s ease;
}

.nav-left a:hover, .nav-right a:hover {
    background-color: var(--light-blue-border);
}

.nav-right .balance-badge {
    background: var(--light-blue);
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 700;
    margin-right: 6px;
}

.ticket-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 100px 20px 40px 20px;
}

.container-title {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 30px;
    text-align: center;
}

.ticket-card {
    background: rgba(173, 216, 230, 0.35);
    backdrop-filter: blur(14px) saturate(130%);
    border: 1px solid rgba(173, 216, 230, 0.45);
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.ticket-route {
    margin: 0;
    font-size: 1.5rem;
    color: #000;
}

.company-name {
    font-size: 1rem;
    font-weight: 700;
    color: #000;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 5px 10px;
    border-radius: 6px;
}

.ticket-body {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.ticket-body p {
    margin: 0;
    font-size: 0.95rem;
    color: #000;
}

.ticket-body p strong {
    color: #000;
    font-weight: 700;
}

.ticket-footer {
    display: flex;
    gap: 10px;
    padding: 15px 20px;
    background-color: transparent;
    border-top: 1px solid rgba(0,0,0,0.1);
    margin-top: auto;
    align-items: center;
    justify-content: center; /* Butonları yatayda tam ortalar */
}

/* 🔥 SORUNU KÖKÜNDEN ÇÖZEN TEK SATIR BURADA 🔥 */
.refund-form {
    display: contents; /* Form etiketini layout'tan kaldırır, içindeki butonu serbest bırakır */
}

.btn {
    padding: 8px 16px;
    font-size: 0.9rem;
    font-weight: 700;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.25s ease;
    color: white;
    text-align: center;
    width: 160px; /* Butonlara sabit bir genişlik veriyoruz */
    box-sizing: border-box; /* Padding ve border'ın genişliği etkilememesini sağlar */
}
.btn-primary { background-color: var(--primary-color); }
.btn-primary:hover { background-color: #0056b3; }
.btn-danger { background-color: var(--danger-color); }
.btn-danger:hover { background-color: #c82333; }
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
