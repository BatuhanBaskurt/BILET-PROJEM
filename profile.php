<?php
include 'db.php';
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT full_name, email, company_id, balance FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Rolü belirle
if (!empty($user['company_id']) && $user['company_id'] != 0) {
    $role = 'COMP_ADMIN';
} else {
    $role = 'USER';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profilim</title>
<link rel="stylesheet" href="style.css">
<style>

</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="profile-container">
    <h2>Profilim</h2>

    <div class="profile-info">
        <p><strong>İsim:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
        <p><strong>E-posta:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Rol:</strong> <?= $role ?></p>
        <p><strong>Bakiye:</strong> ₺<?= htmlspecialchars($user['balance']) ?></p>
    </div>

    <?php if($role === 'USER'): ?>
        <h3>Geçmiş Biletler</h3>
        <?php
        $stmt2 = $pdo->prepare("
            SELECT 
                t.id, 
                t.status,
                t.total_price,
                tr.departure_city, 
                tr.destination_city, 
                tr.departure_time, 
                tr.arrival_time
            FROM Tickets t
            JOIN Trips tr ON t.trip_id = tr.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt2->execute([$_SESSION['user_id']]);
        $tickets = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Otomatik status güncellemesi 
        $now = date('Y-m-d H:i:s');
        foreach($tickets as &$ticket) {
            if ($ticket['status'] === 'active' && $ticket['departure_time'] < $now) {
                
                $updateStmt = $pdo->prepare("UPDATE Tickets SET status = 'expired' WHERE id = ?");
                $updateStmt->execute([$ticket['id']]);
                $ticket['status'] = 'expired';
            }
        }
        ?>

        <?php if(count($tickets) === 0): ?>
            <p>Henüz geçmiş biletiniz yok</p>
        <?php else: ?>
            <table class="tickets-table">
                <tr>
                    <th>No</th>
                    <th>Kalkış</th>
                    <th>Varış</th>
                    <th>Kalkış Saati</th>
                    <th>Varış Saati</th>
                    <th>Fiyat</th>
                    <th>Durum</th>
                </tr>
                <?php foreach($tickets as $ticket): ?>
                    <tr>
                        <td><?= $ticket['id'] ?></td>
                        <td><?= htmlspecialchars($ticket['departure_city']) ?></td>
                        <td><?= htmlspecialchars($ticket['destination_city']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($ticket['departure_time'])) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($ticket['arrival_time'])) ?></td>
                        <td>₺<?= htmlspecialchars($ticket['total_price']) ?></td>
                        <td>
                            <?php if($ticket['status'] === 'active'): ?>
                                <span class="status-active">Aktif</span>
                            <?php elseif($ticket['status'] === 'cancelled'): ?>
                                <span class="status-cancelled">İptal</span>
                            <?php elseif($ticket['status'] === 'expired'): ?>
                                <span class="status-expired">Geçmiş</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
