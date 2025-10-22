<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php'; 
session_start();

// Sadece comp_admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header("Location: index.php");
    exit;
}

// Şirket ID'sini al
$stmt = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$company_id = $user['company_id'] ?? null;

if (!$company_id) {
    die("HATA: Bu kullanıcıya atanmış bir şirket bulunamadı.");
}

// Şehir listesi
$cities = ["Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin","Aydın","Balıkesir","Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa","Çanakkale","Çankırı","Çorum","Denizli","Diyarbakır","Edirne","Elazığ","Erzincan","Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane","Hakkari","Hatay","Isparta","Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli","Kırşehir","Kocaeli","Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla","Muş","Nevşehir","Niğde","Ordu","Rize","Sakarya","Samsun","Siirt","Sinop","Sivas","Tekirdağ","Tokat","Trabzon","Tunceli","Şanlıurfa","Uşak","Van","Yozgat","Zonguldak","Aksaray","Bayburt","Karaman","Kırıkkale","Batman","Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis","Osmaniye","Düzce"];

// Silme işlemi
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$delete_id, $company_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
            $stmt->execute([$delete_id, $company_id]);
            $form_error = "Sefer silindi!";
        } else {
            $form_error = "Geçersiz veya yetkisiz sefer ID.";
        }
    } catch (PDOException $e) {
        $form_error = "Silme hatası: " . $e->getMessage();
    }
}

// Form işlemleri
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Yeni sefer ekleme
    if (isset($_POST['add_trip'])) {
        $departure = trim($_POST['departure_city'] ?? '');
        $destination = trim($_POST['destination_city'] ?? '');
        $departure_time = !empty($_POST['departure_time']) ? str_replace('T', ' ', $_POST['departure_time']) . ':00' : '';
        $arrival_time = !empty($_POST['arrival_time']) ? str_replace('T', ' ', $_POST['arrival_time']) . ':00' : '';
        $seat_count = (int)($_POST['seat_count'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        if (empty($departure) || empty($destination) || empty($departure_time) || empty($arrival_time)) {
            $form_error = "Tüm alanlar zorunlu.";
        } elseif ($seat_count <= 0) {
            $form_error = "Koltuk sayısı sıfırdan büyük olmalı.";
        } elseif ($price < 0) {
            $form_error = "Fiyat negatif olamaz.";
        } elseif (!in_array($departure, $cities) || !in_array($destination, $cities)) {
            $form_error = "Geçersiz şehir seçimi.";
        } else {
            $departure_timestamp = strtotime($departure_time);
            $arrival_timestamp = strtotime($arrival_time);
            if ($departure_timestamp === false || $arrival_timestamp === false) {
                $form_error = "Geçersiz tarih formatı.";
            } elseif ($departure_timestamp >= $arrival_timestamp) {
                $form_error = "Kalkış saati varış saatinden önce olmalı.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, seat_count, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$company_id, $departure, $destination, $departure_time, $arrival_time, $seat_count, $price]);
                    $form_error = "Sefer eklendi!";
                } catch (PDOException $e) {
                    $form_error = "Ekleme hatası: " . $e->getMessage();
                }
            }
        }
    }
    // Sefer güncelleme
    elseif (isset($_POST['update_trip'])) {
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        $departure = trim($_POST['departure_city'] ?? '');
        $destination = trim($_POST['destination_city'] ?? '');
        $departure_time = !empty($_POST['departure_time']) ? str_replace('T', ' ', $_POST['departure_time']) . ':00' : '';
        $arrival_time = !empty($_POST['arrival_time']) ? str_replace('T', ' ', $_POST['arrival_time']) . ':00' : '';
        $seat_count = (int)($_POST['seat_count'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        if ($trip_id <= 0) {
            $form_error = "Geçersiz sefer ID.";
        } elseif (empty($departure) || empty($destination) || empty($departure_time) || empty($arrival_time)) {
            $form_error = "Tüm alanlar zorunlu.";
        } elseif ($seat_count <= 0) {
            $form_error = "Koltuk sayısı sıfırdan büyük olmalı.";
        } elseif ($price < 0) {
            $form_error = "Fiyat negatif olamaz.";
        } elseif (!in_array($departure, $cities) || !in_array($destination, $cities)) {
            $form_error = "Geçersiz şehir seçimi.";
        } else {
            $departure_timestamp = strtotime($departure_time);
            $arrival_timestamp = strtotime($arrival_time);
            if ($departure_timestamp === false || $arrival_timestamp === false) {
                $form_error = "Geçersiz tarih formatı.";
            } elseif ($departure_timestamp >= $arrival_timestamp) {
                $form_error = "Kalkış saati varış saatinden önce olmalı.";
            } else {
                try {
                    // Seferin şirkete ait olduğunu doğrula
                    $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
                    $stmt->execute([$trip_id, $company_id]);
                    if ($stmt->fetch()) {
                        $stmt = $pdo->prepare("UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, seat_count = ?, price = ? WHERE id = ? AND company_id = ?");
                        $stmt->execute([$departure, $destination, $departure_time, $arrival_time, $seat_count, $price, $trip_id, $company_id]);
                        $form_error = "Sefer güncellendi!";
                    } else {
                        $form_error = "Geçersiz veya yetkisiz sefer ID.";
                    }
                } catch (PDOException $e) {
                    $form_error = "Güncelleme hatası: " . $e->getMessage();
                }
            }
        }
    }
}

// Şirket ve seferleri çek
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
    main {
        max-width: 1200px;
        margin: 100px auto 30px;
        padding: 25px;
        border-radius: 15px; 
    }
    body{
    padding-top: 200px; 
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
                <input type="number" id="add_seat" name="seat_count" min="1" required>
            </div>
            <div class="form-group">
                <label for="add_price">Fiyat (TL)</label>
                <input type="number" id="add_price" name="price" min="0" step="0.01" required>
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
