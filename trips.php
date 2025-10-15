<?php
// Hata raporlamayı açalım ki sorun olursa görelim
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php'; // PDO ile SQLite bağlantısı

// Sadece comp_admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header("Location: index.php");
    exit;
}

// 1. ADIM: Giriş yapan adminin ŞİRKET ID'sini alıyoruz
$stmt = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$company_id = $user['company_id'] ?? null;

if (!$company_id) {
    die("HATA: Bu kullanıcıya atanmış bir şirket bulunamadı.");
}

// Şehir listesi
$cities = ["Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin","Aydın","Balıkesir","Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa","Çanakkale","Çankırı","Çorum","Denizli","Diyarbakır","Edirne","Elazığ","Erzincan","Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane","Hakkari","Hatay","Isparta","Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli","Kırşehir","Kocaeli","Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla","Muş","Nevşehir","Niğde","Ordu","Rize","Sakarya","Samsun","Siirt","Sinop","Sivas","Tekirdağ","Tokat","Trabzon","Tunceli","Şanlıurfa","Uşak","Van","Yozgat","Zonguldak","Aksaray","Bayburt","Karaman","Kırıkkale","Batman","Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis","Osmaniye","Düzce"];

// 2. ADIM: Link ile gelen SİLME isteğini burada yakalıyoruz
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$delete_id, $company_id]);
    header("Location: trips.php");
    exit;
}

// 3. ADIM: Formdan gelen EKLEME ve GÜNCELLEME isteklerini burada yakalıyoruz
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A) YENİ SEFER EKLEME
    if (isset($_POST['add_trip'])) {
        $departure = trim($_POST['departure_city']);
        $destination = trim($_POST['destination_city']);
        $departure_time = str_replace('T', ' ', $_POST['departure_time']) . ':00';
        $arrival_time = str_replace('T', ' ', $_POST['arrival_time']) . ':00';
        $seat_count = (int)($_POST['seat_count'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        // Kalkış saati varış saatinden önce olmalı kontrolü
        $departure_timestamp = strtotime($departure_time);
        $arrival_timestamp = strtotime($arrival_time);
        if ($departure_timestamp >= $arrival_timestamp) {
            $form_error = "Kalkış saati varış saatinden önce olmalıdır.";
        } elseif ($departure && $destination && $seat_count > 0 && $price >= 0) {
            $stmt = $pdo->prepare("INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, seat_count, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company_id, $departure, $destination, $departure_time, $arrival_time, $seat_count, $price]);
        } else {
            $form_error = "Lütfen tüm alanları doğru doldurun.";
        }
    }
    // B) MEVCUT SEFERİ GÜNCELLEME
    elseif (isset($_POST['update_trip'])) {
        $trip_id = (int)$_POST['trip_id'];
        $departure = trim($_POST['departure_city']);
        $destination = trim($_POST['destination_city']);
        $departure_time = str_replace('T', ' ', $_POST['departure_time']) . ':00';
        $arrival_time = str_replace('T', ' ', $_POST['arrival_time']) . ':00';
        $seat_count = (int)$_POST['seat_count'];
        $price = (float)$_POST['price'];

        // Kalkış saati varış saatinden önce olmalı kontrolü
        $departure_timestamp = strtotime($departure_time);
        $arrival_timestamp = strtotime($arrival_time);
        if ($departure_timestamp >= $arrival_timestamp) {
            $form_error = "Kalkış saati varış saatinden önce olmalıdır.";
        } else {
            $stmt = $pdo->prepare("UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, seat_count = ?, price = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$departure, $destination, $departure_time, $arrival_time, $seat_count, $price, $trip_id, $company_id]);
        }
    }
    header("Location: trips.php");
    exit;
}

// Şirket bilgileri ve seferleri çek
$company = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$company->execute([$company_id]);
$company = $company->fetch(PDO::FETCH_ASSOC);

$trips_stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time ASC");
$trips_stmt->execute([$company_id]);
$trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($company['name'] ?? 'Şirket') ?> - Sefer Yönetimi</title>
<link rel="stylesheet" href="style.css">
<style>
    :root {
        --primary-color: #dc3545;
        --update-color: #007bff;
    }
    /* Body ve arka plan */
    body.company-page {
        padding-top: 70px;
        background-image: url('foto/wallpaper.jpg'); /* ← burası eklendi */
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        padding-top: 200px;
    }
    main {
        max-width: 1200px;
        margin: 100px auto 30px;
        padding: 25px;
        
        border-radius: 15px; 
        
    }
    .company-title {
        color: #000000ff;
        text-align: center;
        margin-bottom: 30px;
    }
    /* Konteynerlerin arkasındaki kutuları kaldırıyoruz */
    .trip-form-container { margin-bottom: 40px; }
    .trip-list-container { overflow-x: auto; }

    /* 🔥 FOTOĞRAFTAKİ GİBİ DÜZENLİ FORM BURADA 🔥 */
    .trip-form {
        display: grid;
        grid-template-columns: 1fr 1fr; /* 2 sütunlu yapı */
        gap: 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .trip-form label {
        color: #ccc;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .trip-form input, .trip-form select {
        padding: 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        background: rgba(0, 0, 0, 1);
        color: #000000ff;
        border-radius: 8px;
        font-size: 16px;
    }
    .trip-form option { background: #ffffffff; }
    /* Butonun tam genişlikte olmasını sağlıyoruz */
    .trip-form .form-group-full {
        grid-column: 1 / -1;
    }
    .trip-form button {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 8px;
        background: var(--update-color);
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
    }

    /* Tablo stilleri aynı kalıyor */
    .trip-table { width: 100%; border-collapse: collapse; color: #fff; }
    .trip-table th, .trip-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
    .trip-table thead th { background: rgba(255, 255, 255, 0.15); }
    .trip-table input, .trip-table select { width: 100%; padding: 8px; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.3); color: #fff; border-radius: 4px; box-sizing: border-box; }
    .actions-cell { display: flex; gap: 8px; }
    .btn { padding: 6px 12px; border-radius: 4px; border: none; color: white; text-decoration: none; font-weight: bold; cursor: pointer; text-align: center; }
    .btn-update { background-color: var(--update-color); }
    .btn-delete { background-color: var(--primary-color); }
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox için */
    input[type=number] {
        -moz-appearance: textfield;
        
        
    }
</style>

</head>
<body class="company-page">

<?php include 'navbar.php'; ?>

<main>
    <h2 class="company-title"><?= htmlspecialchars($company['name'] ?? 'Şirket') ?> - Sefer Yönetimi</h2>

    <?php if (!empty($form_error)): ?>
        <div style="color:red; text-align:center; margin-bottom:15px;"><?= htmlspecialchars($form_error) ?></div>
    <?php endif; ?>

    <div class="trip-form-container">
        <form method="post" class="trip-form">
            <div class="form-group">
                <label for="add_dep_city">Kalkış</label>
                <select name="departure_city" id="add_dep_city" required>
                    <option value="" disabled selected>Şehir Seç</option>
                    <?php foreach($cities as $city): ?><option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="add_dest_city">Varış</label>
                <select name="destination_city" id="add_dest_city" required>
                    <option value="" disabled selected>Şehir Seç</option>
                    <?php foreach($cities as $city): ?><option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="add_dep_time">Kalkış Saati</label>
                <input type="datetime-local" id="add_dep_time" name="departure_time" required>
            </div>
            <div class="form-group">
                <label for="add_arr_time">Varış Saati</label>
                <input type="datetime-local" id="add_arr_time" name="arrival_time" required>
            </div>
            <div class="form-group">
                <label for="add_seat">Koltuk</label>
                <input type="number" id="add_seat" name="seat_count"  min="1" required>
            </div>
            <div class="form-group">
                <label for="add_price">Fiyat (TL)</label>
                <input type="number" id="add_price" name="price"  min="0" step="0.01" required>
            </div>
            <div class="form-group form-group-full">
                <button type="submit" name="add_trip">Yeni Sefer Ekle</button>
            </div>
        </form>
    </div>

    <div class="trip-list-container">
        <table class="trip-table">
            <thead>
                <tr>
                    <th>Kalkış</th><th>Varış</th><th>Kalkış Saati</th><th>Varış Saati</th><th>Koltuk</th><th>Fiyat</th><th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr><td colspan="7" style="text-align: center;">Henüz sefer yok.</td></tr>
                <?php else: ?>
                    <?php foreach($trips as $trip): ?>
                        <form method="post">
                            <tr>
                                <input type="hidden" name="trip_id" value="<?= (int)$trip['id'] ?>">
                                <td>
                                    <select name="departure_city" required>
                                        <?php foreach($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city) ?>" <?= ($city == $trip['departure_city']) ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="destination_city" required>
                                        <?php foreach($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city) ?>" <?= ($city == $trip['destination_city']) ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="datetime-local" name="departure_time" value="<?= str_replace(' ', 'T', substr($trip['departure_time'], 0, 16)) ?>" required></td>
                                <td><input type="datetime-local" name="arrival_time" value="<?= str_replace(' ', 'T', substr($trip['arrival_time'], 0, 16)) ?>" required></td>
                                <td><input type="number" name="seat_count" value="<?= (int)$trip['seat_count'] ?>" min="1" required></td>
                                <td><input type="number" name="price" value="<?= (float)$trip['price'] ?>" min="0" step="0.01" required></td>
                                <td class="actions-cell">
                                    <button type="submit" name="update_trip" class="btn btn-update">Kaydet</button>
                                    <a href="?delete=<?= (int)$trip['id'] ?>" class="btn btn-delete">Sil</a>
                                </td>
                            </tr>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
