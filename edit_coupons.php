<?php
// Hata raporlamayı açalım ki sorun olursa görelim
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

// Bu fonksiyon, standartlara uygun bir v4 UUID üretir.
function generate_uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Link ile gelen silme isteği
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
        $stmt->execute([$id_to_delete]);
    } catch (PDOException $e) {
        die("Silme hatası: " . $e->getMessage());
    }
    header("Location: edit_coupons.php");
    exit;
}

// POST İşlemleri (Create ve Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_coupon'])) {
        $code = trim($_POST['code']);
        $discount = trim($_POST['discount']);
        $usage_limit = trim($_POST['usage_limit']);
        $expire_date = $_POST['expire_date'];
        $company_id = $_POST['company_id'];
        
        $id = generate_uuid_v4();
        $created_at = date('Y-m-d H:i:s');

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$id, $code, $discount, $usage_limit, $expire_date, $company_id, $created_at]);
        } catch (PDOException $e) {
            die("Veritabanına kupon eklenemedi: " . $e->getMessage());
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $code = trim($_POST['code']);
            $discount = trim($_POST['discount']);
            $usage_limit = trim($_POST['usage_limit']);
            $expire_date = $_POST['expire_date'];
            $company_id = $_POST['company_id'];
            
            $stmt = $pdo->prepare("UPDATE Coupons SET code = ?, discount = ?, usage_limit = ?, expire_date = ?, company_id = ? WHERE id = ?");
            $stmt->execute([$code, $discount, $usage_limit, $expire_date, $company_id, $id]);
        }
    }
    header("Location: edit_coupons.php");
    exit;
}

// Verileri Çekme
$coupons = $pdo->query("
    SELECT c.id, c.code, c.discount, c.usage_limit, c.expire_date, c.created_at, b.name AS company_name, c.company_id 
    FROM Coupons c 
    LEFT JOIN Bus_Company b ON c.company_id = b.id 
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$companies = $pdo->query("SELECT id, name FROM Bus_Company")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kupon Yönetimi</title>
<link rel="stylesheet" href="style.css">
<style>
    /* 1. KAYMA SORUNUNU ÇÖZEN KURAL */
    body {
        padding-top: 80px; /* Navbar için güvenli bir boşluk */
        background-color: #f4f7f6; /* Sayfa arka planı */
        font-family: "Poppins", Arial, sans-serif;
        margin: 0; /* Tarayıcı varsayılan boşluğunu sıfırla */
    }
    .navbar {
        position: fixed;
        top: 0;
        left: 0; /* Önemli */
        right: 0; /* Önemli */
        width: 100%;
        background-color: #dc3545;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 1000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        height: 60px; /* Sabit yükseklik */
        box-sizing: border-box;
        border-radius: 0;
    }
    .nav-left a, .nav-right a {
        color: white;
        text-decoration: none;
        margin: 0 15px;
        font-weight: 600;
        font-size: 16px;
    }
    /* Ana Konteyner */
    .user-container {
        max-width: 1200px;
        margin: 30px auto; /* Üstteki gereksiz boşluk kaldırıldı */
        padding: 25px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    .user-container h1, .user-container h2 {
        text-align: center;
        color: #dc3545;
        margin-bottom: 30px;
    }
    /* Kupon Listesi */
    .coupon-schema, .coupon-item {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr 1.5fr;
        gap: 15px;
        align-items: center;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .coupon-schema {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .coupon-item {
        background: #fff;
        border: 1px solid #eee;
    }
    .coupon-list { list-style: none; padding: 0; }
    .coupon-item input, .coupon-item select {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ddd;
        width: 100%;
        box-sizing: border-box;
    }
    .action-buttons { 
        display: flex;
        gap: 10px;
    }
    .action-btn {
        padding: 8px 15px;
        border: none;
        border-radius: 6px;
        background-color: #000;
        color: white;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        flex: 1; /* Butonların eşit genişlemesi için */
    }
    .action-btn.delete {
        background-color: #dc3545;
    }
    /* Yeni Kupon Oluşturma Formu */
    .coupon-create {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid #eee;
    }
    .coupon-create form {
        max-width: 800px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    .coupon-create input, .coupon-create select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-sizing: border-box;
    }
    .coupon-create button {
        grid-column: 1 / -1; /* Buton tam genişlik kaplasın */
        padding: 12px;
        border: none;
        border-radius: 6px;
        background-color: #000;
        color: white;
        font-weight: bold;
        cursor: pointer;
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

<div class="user-container">
    <h1>Kupon Yönetimi</h1>
    <div class="coupon-schema">
        <span>Kupon Kodu</span>
        <span>İndirim (TL)</span>
        <span>Limit</span>
        <span>Son Tarih</span>
        <span>Şirket Adı</span>
        <span>Eylemler</span>
    </div>
    <ul class="coupon-list">
        <?php foreach ($coupons as $coupon): ?>
            <li class="coupon-item">
                <form method="POST" style="display: contents;">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($coupon['id']) ?>">
                    <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>">
                    <input type="number" step="0.01" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>">
                    <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>">
                    <input type="date" name="expire_date" value="<?= date('Y-m-d', strtotime($coupon['expire_date'])) ?>">
                    <select name="company_id" required>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>" <?= $comp['id'] == $coupon['company_id'] ? 'selected' : '' ?>><?= htmlspecialchars($comp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="action-buttons">
                        <button type="submit" name="action" value="update" class="action-btn">Kaydet</button>
                        <a href="edit_coupons.php?action=delete&id=<?= htmlspecialchars($coupon['id']) ?>" class="action-btn delete">Sil</a>
                    </div>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <div class="coupon-create">
        <h2>Yeni Kupon Oluştur</h2>
        <form method="POST">
            <input type="hidden" name="create_coupon" value="1">
            <input type="text" name="code" required placeholder="Kupon Kodu">
            <input type="number" step="0.01" name="discount" required placeholder="İndirim (TL)">
            <input type="number" name="usage_limit" required placeholder="Kullanım Limiti">
            <input type="date" name="expire_date" required>
            <select name="company_id" required>
                <option value="" disabled selected>Şirket Seç</option>
                <?php foreach ($companies as $comp): ?>
                    <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Oluştur</button>
        </form>
    </div>
</div>

</body>
</html>
