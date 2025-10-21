<?php
include 'db.php';
session_start();

// Sadece comp_admin erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

if (!isset($_GET['id'])) {
    header('Location: add_coupon.php');
    exit;
}

$id = intval($_GET['id']);

// Kuponu getir
$stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ?");
$stmt->execute([$id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    $error = "Kupon bulunamadı!";
}

// Form gönderildiyse güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];

    if (empty($code) || $discount <= 0 || $usage_limit <= 0 || empty($expire_date)) {
        $error = "Tüm alanları doğru şekilde doldurun!";
    } else {
        // Kupon kodu başka kuponla çakışmasın
        $stmt = $pdo->prepare("SELECT id FROM Coupons WHERE code = ? AND id != ?");
        $stmt->execute([$code, $id]);

        if ($stmt->fetch()) {
            $error = "Bu kupon kodu başka bir kuponda mevcut!";
        } else {
            $stmt = $pdo->prepare("UPDATE Coupons SET code = ?, discount = ?, usage_limit = ?, expire_date = ? WHERE id = ?");
            if ($stmt->execute([$code, $discount, $usage_limit, $expire_date, $id])) {
                $success = "Kupon başarıyla güncellendi!";
                // Güncel veriyi yeniden çek
                $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ?");
                $stmt->execute([$id]);
                $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Güncelleme sırasında bir hata oluştu!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Düzenle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="coupon-form-container">
    <h2>✏️ Kupon Düzenle</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($coupon): ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="code">Kupon Kodu:</label>
            <input type="text" id="code" name="code" required value="<?= htmlspecialchars($coupon['code']) ?>">
        </div>

        <div class="form-group">
            <label for="discount">İndirim Miktarı (₺):</label>
            <input type="number" id="discount" name="discount" min="1" step="0.01" required value="<?= htmlspecialchars($coupon['discount']) ?>">
        </div>

        <div class="form-group">
            <label for="usage_limit">Kullanım Limiti:</label>
            <input type="number" id="usage_limit" name="usage_limit" min="1" required value="<?= htmlspecialchars($coupon['usage_limit']) ?>">
        </div>

        <div class="form-group">
            <label for="expire_date">Son Kullanma Tarihi:</label>
            <input type="datetime-local" id="expire_date" name="expire_date" required value="<?= date('Y-m-d\TH:i', strtotime($coupon['expire_date'])) ?>">
        </div>

        <button type="submit" class="submit-btn">Kuponu Güncelle</button>
        <a href="add_coupon.php" class="back-btn">⬅️ Kupon Listesine Dön</a>
    </form>
    <?php endif; ?>
</div>

</body>
</html>
