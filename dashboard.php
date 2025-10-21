<?php

include 'db.php';
session_start();

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php"); 
    exit;
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT full_name FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['full_name'] = $user['full_name'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="style.css">
<style>

</style>
</head>
<body>

<nav class="navbar-admin">
    <div class="nav-left">
        <a href="dashboard.php">Ana Sayfa</a>
    </div>
    <div class="nav-right">
        <a href="logout.php">Çıkış Yap</a>
    </div>
</nav>

<div class="dashboard">
    <h1>Hoş Geldiniz, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
    <h2>Admin Kontrol Paneli</h2>
    
    <div class="dashboard-content">
        <div class="dashboard-card">
            <h3>Kullanıcı Yönetimi</h3>
            <p>Kullanıcıları görüntüleyin, yetkilerini düzenleyin veya sistemden kaldırın.</p>
            <a href="users.php" class="action-btn">Kullanıcılara Git</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Şirket Yönetimi</h3>
            <p>Otobüs firmalarını ekleyin, düzenleyin veya silin.</p>
            <a href="companies.php" class="action-btn">Şirketlere Git</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Sefer İzleme</h3>
            <p>Sistemdeki tüm seferleri görüntüleyin ve detaylarını kontrol edin.</p>
            <a href="trips_view.php" class="action-btn">Seferleri İzle</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Kupon Yönetimi</h3>
            <p>İndirim kuponları oluşturun, düzenleyin ve kampanyaları yönetin.</p>
            <a href="edit_coupons.php" class="action-btn">Kuponlara Git</a>
        </div>
    </div>
</div>

</body>
</html>
