<?php
include 'db.php';
session_start();

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Tüm seferleri çek (firma adı ile join) - Şemaya göre kolonlar
$stmt = $pdo->prepare("
    SELECT t.id, t.departure_city, t.destination_city, t.departure_time, t.arrival_time, t.price, t.seat_count, t.created_at, c.name AS company_name 
    FROM Trips t 
    JOIN Bus_Company c ON t.company_id = c.id 
    ORDER BY t.created_at DESC
");
$stmt->execute();
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if ($action === 'update' && $id) {
        $departure_city = trim($_POST['departure_city']);
        $destination_city = trim($_POST['destination_city']);
        $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time'];
        $price = trim($_POST['price']);
        $seat_count = trim($_POST['seat_count']);
        
        try {
            $stmt = $pdo->prepare("UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, seat_count = ? WHERE id = ?");
            $stmt->execute([$departure_city, $destination_city, $departure_time, $arrival_time, $price, $seat_count, $id]);
        } catch (PDOException $e) {
            // Hata yönetimi 
        }
    } elseif ($action === 'delete' && $id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Hata yönetimi
        }
    }
    header("Location: trips_view.php");
    exit;
}

$cities = [
    "Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin","Aydın","Balıkesir",
    "Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa","Çanakkale","Çankırı","Çorum","Denizli",
    "Diyarbakır","Edirne","Elazığ","Erzincan","Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane","Hakkari",
    "Hatay","Isparta","Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli","Kırşehir",
    "Kocaeli","Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla","Muş","Nevşehir",
    "Niğde","Ordu","Rize","Sakarya","Samsun","Siirt","Sinop","Sivas","Tekirdağ","Tokat",
    "Trabzon","Tunceli","Şanlıurfa","Uşak","Van","Yozgat","Zonguldak","Aksaray","Bayburt","Karaman",
    "Kırıkkale","Batman","Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis","Osmaniye","Düzce"
];

// Şirketleri çek (create için)
$stmt = $pdo->prepare("SELECT id, name FROM Bus_Company");
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create işlemi
if (isset($_POST['create_trip'])) {
    $company_id = $_POST['company_id'];
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = trim($_POST['price']);
    $seat_count = trim($_POST['seat_count']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, price, seat_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $departure_city, $destination_city, $departure_time, $arrival_time, $price, $seat_count]);
    } catch (PDOException $e) {
        // Hata yönetimi
    }
    header("Location: trips_view.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sefer Yönetimi</title>
<link rel="stylesheet" href="style.css">
<style>
body {
    background-color: #f4f7f6;
    margin: 0;
    padding: 0;
    font-family: "Poppins", Arial, sans-serif;
    padding-top: 220px;
}
</style>
</head>
<body>

<nav class="navbar-admin">
    <div class="nav-left">
        <a href="dashboard.php">Anasayfa</a>
    </div>
    <div class="nav-right">
        <a href="logout.php">Çıkış Yap</a>
    </div>
</nav>

<div class="user-container">
    
    <h1>Sefer Yönetimi</h1>
    <div class="trip-schema">
        <span>Kalkış Şehri</span>
        <span>Varış Şehri</span>
        <span>Kalkış Zamanı</span>
        <span>Varış Zamanı</span>
        <span>Fiyat</span>
        <span>Koltuk</span>
        <span>Firma Adı</span>
        <span>Oluşturma Tarihi</span>
        <span>Eylemler</span>
    </div>
    <ul class="trip-list">
        <?php foreach ($trips as $trip): ?>
            <li class="trip-item">
                <form method="POST" style="display: contents;">
                    <input type="hidden" name="id" value="<?= $trip['id'] ?>">
                    
                    <select name="departure_city" class="trip-input" data-label="Kalkış:">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city ?>" <?= $city === $trip['departure_city'] ? 'selected' : '' ?>><?= $city ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="destination_city" class="trip-input" data-label="Varış:">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city ?>" <?= $city === $trip['destination_city'] ? 'selected' : '' ?>><?= $city ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="datetime-local" name="departure_time" value="<?= str_replace(' ', 'T', $trip['departure_time']) ?>" class="trip-input" data-label="Kalkış Zamanı:">
                    <input type="datetime-local" name="arrival_time" value="<?= str_replace(' ', 'T', $trip['arrival_time']) ?>" class="trip-input" data-label="Varış Zamanı:">
                    <input type="number" name="price" value="<?= htmlspecialchars($trip['price']) ?>" class="trip-input" data-label="Fiyat:">
                    <input type="number" name="seat_count" value="<?= htmlspecialchars($trip['seat_count']) ?>" class="trip-input" data-label="Koltuk:">
                    
                    <span data-label="Firma:"><?= htmlspecialchars($trip['company_name']) ?></span>
                    <span data-label="Oluşturulma:"><?= date('d.m.Y H:i', strtotime($trip['created_at'])) ?></span>
                    
                    <div class="action-buttons">
                        <button type="submit" name="action" value="update" class="action-btn">Güncelle</button>
                        <button type="submit" name="action" value="delete" class="action-btn delete" onclick="return confirm('Bu seferi silmek istediğinize emin misiniz?');">Sil</button>
                    </div>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (empty($trips)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">Henüz sistemde kayıtlı bir sefer bulunmuyor.</p>
    <?php endif; ?>

    <div class="trip-create">
        <h2>Yeni Sefer Oluştur</h2>
        <form method="POST">
            <input type="hidden" name="create_trip" value="1">
            
            <select name="company_id" required class="create-input">
                <option value="" disabled selected>Şirket Seç</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="departure_city" required class="create-input">
                <option value="" disabled selected>Kalkış Şehri</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= $city ?>"><?= $city ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="destination_city" required class="create-input">
                <option value="" disabled selected>Varış Şehri</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= $city ?>"><?= $city ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="datetime-local" name="departure_time" required class="create-input" title="Kalkış Zamanı">
            <input type="datetime-local" name="arrival_time" required class="create-input" title="Varış Zamanı">
            <input type="number" name="price" required class="create-input" placeholder="Fiyat (TL)" min="0">
            <input type="number" name="seat_count" required class="create-input" placeholder="Koltuk Sayısı" min="1">
            
            <button type="submit">Yeni Seferi Ekle</button>
        </form>
    </div>
</div>

</body>
</html>
