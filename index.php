<?php
session_start();
include 'db.php'; // PDO ile SQLite bağlantısı

// Kullanıcı giriş yaptıysa, balance ve isim çek
if(isset($_SESSION['user_id'])){
    $stmt = $pdo->prepare("SELECT full_name, balance, role FROM User WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['balance'] = $user['balance'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
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

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['from']) && isset($_GET['to']) && isset($_GET['date'])) {
    $from = $_GET['from'];
    $to = $_GET['to'];
    $date = $_GET['date'];

    // Örnek: seferleri çekiyoruz
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE departure_city = ? AND destination_city = ? AND DATE(departure_time) = ?");
    $stmt->execute([$from, $to, $date]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anasayfa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<!-- Arama Formu -->
<div class="search-form">
    <form method="get" action="tickets.php">
        <label for="from">Kalkış Şehri:</label>
        <select id="from" name="from" required>
            <option value="" disabled selected>Şehir seçin</option>
            <?php foreach($cities as $city): ?>
                <option value="<?= $city ?>"><?= $city ?></option>
            <?php endforeach; ?>
        </select>

        <label for="to">Varış Şehri:</label>
        <select id="to" name="to" required>
            <option value="" disabled selected>Şehir seçin</option>
            <?php foreach($cities as $city): ?>
                <option value="<?= $city ?>"><?= $city ?></option>
            <?php endforeach; ?>
        </select>

        <label for="date">Tarih:</label>
        <input type="date" id="date" name="date" required>

        <button type="submit">Ara</button>
    </form>
</div>

<!-- Trip Listesi (arama formunun altında) -->
<?php if (!empty($trips)): ?>
    <div class="trip-container">
        <?php foreach($trips as $trip): ?>
            <div class="trip-card">
                <h3><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></h3>
                <p>Kalkış: <?= htmlspecialchars($trip['departure_time']) ?></p>
                <p>Varış: <?= htmlspecialchars($trip['arrival_time']) ?></p>
                <p>Koltuk: <?= htmlspecialchars($trip['seat_count']) ?></p>
                <p>Fiyat: ₺<?= htmlspecialchars($trip['price']) ?></p>

                <!-- Satın Al Butonu -->
                <p style="margin-top: 12px; text-align: center;">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a class="buy-ticket-link" href="login.php" style="background: rgba(173,216,230,0.35); padding: 10px 20px; border-radius: 8px; text-decoration:none; color:#000;">Giriş Yap ve Satın Al</a>
                    <?php elseif($_SESSION['role'] === 'user'): ?>
                        <a class="buy-ticket-link" href="buy_ticket.php?trip_id=<?= $trip['id'] ?>" style="background: rgba(173,216,230,0.35); padding: 10px 20px; border-radius: 8px; text-decoration:none; color:#000;">Satın Al</a>
                    <?php else: ?>
                        <span style="color: gray;">Bilet satın alamazsınız</span>
                    <?php endif; ?>
                </p>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
