<?php
// Hata raporlamayı açalım ki sorun olursa görelim
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
session_start();

// Bu fonksiyon, standartlara uygun bir v4 UUID üretir.
function generate_uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Sadece comp_admin erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header('Location: index.php');
    exit;
}

// 1. ADIM: Giriş yapan adminin ŞİRKET ID'sini alıyoruz
$stmt = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_company = $stmt->fetch(PDO::FETCH_ASSOC);
$company_id = $user_company['company_id'] ?? null;

// Eğer adminin bir şirketi yoksa, burada işlem yapamaz
if (!$company_id) {
    die("HATA: Bu kullanıcıya atanmış bir şirket bulunamadı.");
}

// 2. ADIM: Link ile gelen SİLME isteğini burada yakalıyoruz
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    
    // Güvenlik: Sadece kendi şirketinin kuponunu silebilir
    $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$id_to_delete, $company_id]);
    
    header("Location: " . basename(__FILE__)); // Sayfayı yenilemek için
    exit;
}

$success = '';
$error = '';

// 3. ADIM: Formdan gelen EKLEME isteğini burada yakalıyoruz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];

    if (empty($code) || $discount <= 0 || $usage_limit <= 0 || empty($expire_date)) {
        $error = "Tüm alanları doğru şekilde doldurun!";
    } else {
        // Kupon kodunun kendi şirketi içinde benzersiz olup olmadığını kontrol et
        $stmt = $pdo->prepare("SELECT id FROM Coupons WHERE code = ? AND company_id = ?");
        $stmt->execute([$code, $company_id]);
        
        if ($stmt->fetch()) {
            $error = "Bu kupon kodu zaten mevcut!";
        } else {
            // Kuponu veritabanına ekle (Tüm zorunlu alanlarla birlikte)
            $id = generate_uuid_v4(); // Eşsiz UUID oluştur
            $created_at = date('Y-m-d H:i:s'); // Şu anki tarihi al

            $stmt = $pdo->prepare(
                "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            if ($stmt->execute([$id, $code, $discount, $usage_limit, $expire_date, $company_id, $created_at])) {
                $success = "Kupon başarıyla eklendi!";
            } else {
                $error = "Kupon eklenirken bir hata oluştu!";
            }
        }
    }
}

// 4. ADIM: Sadece bu şirkete ait kuponları listeliyoruz
$stmt = $pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Ekle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .coupon-form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .coupon-form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .submit-btn:hover {
            background: #45a049;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #2e7d32;
            border: 1px solid #4CAF50;
        }
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            color: #c62828;
            border: 1px solid #f44336;
        }
        .coupons-list {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .coupons-list h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .coupon-item {
            background: rgba(255,255,255,0.8);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .coupon-info strong {
            color: #4CAF50;
            font-size: 18px;
        }
        .coupon-details {
            font-size: 14px;
            color: #666;
        }
        .coupon-actions a {
            margin-left: 10px;
            text-decoration: none;
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 6px;
            color: white;
        }
        .edit-btn {
            background-color: #2196F3;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        .edit-btn:hover {
            background-color: #1976D2;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="coupon-form-container">
    <h2>🎫 Yeni Kupon Ekle</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="code">Kupon Kodu:</label>
            <input type="text" id="code" name="code" required>
        </div>
        <div class="form-group">
            <label for="discount">İndirim Miktarı (₺):</label>
            <input type="number" id="discount" name="discount" min="1" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="usage_limit">Kullanım Limiti:</label>
            <input type="number" id="usage_limit" name="usage_limit" min="1" required>
        </div>
        <div class="form-group">
            <label for="expire_date">Son Kullanma Tarihi:</label>
            <input type="datetime-local" id="expire_date" name="expire_date" required>
        </div>
        <button type="submit" class="submit-btn">Kupon Ekle</button>
    </form>
</div>

<?php if (!empty($coupons)): ?>
<div class="coupons-list">
    <h3>📋 Mevcut Kuponların</h3>
    <?php foreach ($coupons as $coupon): ?>
        <div class="coupon-item">
            <div class="coupon-info">
                <strong><?= htmlspecialchars($coupon['code']) ?></strong>
                <div class="coupon-details">
                    İndirim: <?= htmlspecialchars($coupon['discount']) ?>₺ | 
                    Limit: <?= htmlspecialchars($coupon['usage_limit']) ?> | 
                    Bitiş: <?= date('d.m.Y H:i', strtotime($coupon['expire_date'])) ?>
                </div>
            </div>
            <div class="coupon-actions">
                <a href="?action=delete&id=<?= $coupon['id'] ?>" class="delete-btn">Sil</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
