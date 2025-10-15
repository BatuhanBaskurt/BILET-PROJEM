<?php
session_start();
include 'db.php';

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php"); // login.php olmalı, dosya adını kontrol et
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
/* Navbar */
.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    background-color: #dc3545;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    box-sizing: border-box; /* Önemli */
    height: 60px; /* Sabit bir yükseklik verelim */
    align-items: center; /* Dikeyde ortalamak için */
    border-radius: 0;
}

.nav-left a, .nav-right a {
    color: white;
    text-decoration: none;
    margin: 0 15px;
    font-family: "Poppins", Arial, sans-serif;
    font-weight: 600;
    font-size: 16px;
}

/* 🔥 BÜTÜN SORUNU ÇÖZEN KOD BURADA (1/2) 🔥 */
/* Body'e, navbar'ın yüksekliği kadar üstten boşluk veriyoruz */
body {
    padding-top: 60px; /* Navbar'ın yüksekliği kadar */
    font-family: "Poppins", Arial, sans-serif;
    background-color: #f4f7f6; /* Saydamlık yerine düz renk daha iyi olabilir */
}


/* Dashboard Ana Alanı */
/* 🔥 GEREKSİZ BOŞLUĞU AZALTTIK (2/2) 🔥 */
.dashboard {
    max-width: 1200px;
    margin: 30px auto; /* Üstteki 100px'i azalttık */
    padding: 20px;
    background-color: #ffffff; /* Saydamlık yerine beyaz */
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.dashboard h1 {
    text-align: center;
    color: #dc3545;
    margin-bottom: 10px; /* Boşluğu azalttım */
    font-size: 2.2em;
    font-weight: 700;
}

.dashboard h2 {
    text-align: center;
    color: #555; /* Rengi biraz yumuşattım */
    margin-bottom: 40px;
    font-size: 1.5em;
    font-weight: 400;
}

/* Dashboard Kartları */
.dashboard-content {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
}

.dashboard-card {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    width: 300px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #eee;
}

.dashboard-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.dashboard-card h3 {
    color: #dc3545;
    margin-bottom: 15px;
    font-size: 1.5em;
}

.dashboard-card p {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 25px;
}

.action-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background-color: #000;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.action-btn:hover {
    background-color: #333;
    transform: translateY(-2px);
}
</style>
</head>
<body>

<nav class="navbar">
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