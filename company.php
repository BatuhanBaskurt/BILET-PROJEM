<?php
// Hata raporlamayı açalım ki sorun olursa görelim
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';
session_start();

// Sadece comp_admin erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header('Location: index.php');
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

$success = '';
$error = '';

// 2. ADIM: Formdan GÜNCELLEME isteği geldiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $existing_logo = trim($_POST['existing_logo']);
    $logo_to_save = $existing_logo; // Varsayılan olarak eski logoyu koru

    if (empty($name)) {
        $error = "Şirket adı boş bırakılamaz.";
    } else {
        
        // ======================================================================
        // 🔥 GÜVENLİ LOGO YÜKLEME KISMI BURADA BAŞLIYOR 🔥
        // ======================================================================
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            
            $file = $_FILES['logo'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB limit

            // 1. GÜVENLİK KONTROLÜ: Dosya Boyutu
            if ($file['size'] > $max_file_size) {
                $error = "Dosya boyutu 5 MB'den büyük olamaz.";
            }

            // 2. GÜVENLİK KONTROLÜ: Dosya Tipi ve Uzantısı (En önemlisi)
            if (!$error) {
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $file_mime_type = mime_content_type($file['tmp_name']);

                if (!in_array($file_extension, $allowed_extensions) || !in_array($file_mime_type, $allowed_mime_types)) {
                    $error = "Geçersiz dosya türü. Sadece JPG, PNG, GIF veya WEBP yükleyebilirsiniz.";
                }
            }

            // 3. GÜVENLİK KONTROLÜ: Dosya İçeriği (Gerçekten resim mi?)
            if (!$error) {
                if (getimagesize($file['tmp_name']) === false) {
                    $error = "Yüklenen dosya geçerli bir resim dosyası değil.";
                }
            }
            
            // 4. GÜVENLİK KONTROLÜ: Dosyayı Güvenli Bir İsimle Kaydetme
            if (!$error) {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true); // 0755 daha güvenli bir izindir.
                }

                // Kullanıcının yolladığı dosya adını kullanmıyoruz, kendimiz üretiyoruz.
                $new_file_name = uniqid('logo_', true) . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;
                
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $logo_to_save = $new_file_name;
                } else {
                    $error = "Logo yüklenirken sunucuda bir hata oluştu.";
                }
            }
        }


        if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE Bus_Company SET name = ?, logo_path = ? WHERE id = ?");
                if ($stmt->execute([$name, $logo_to_save, $company_id])) {
                    $success = "Şirket bilgileri başarıyla güncellendi!";
                    // Sayfayı yeniden yükleyerek güncel bilgileri gösterelim
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $error = "Güncelleme sırasında bir veritabanı hatası oluştu.";
                }
            } catch (PDOException $e) {
                $error = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
}

// 3. ADIM: Sayfa yüklendiğinde mevcut şirket bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Şirket Ayarları</title>
<link rel="stylesheet" href="style.css">
<style>
 
    body {
        padding-top: 80px;
    }
    main {
        max-width: 600px; 
        margin: 40px auto;
        padding: 30px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    h1 {
        text-align: center;
        color: #333; 
        margin-bottom: 30px;
    }
</style>
</head>
<body class="company-page">

<?php include 'navbar.php'; ?>

<main>
    <h1>Şirket Ayarları</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Şirket Adı</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="logo">Şirket Logosu (Değiştirmek için seçin)</label>
            <input type="file" id="logo" name="logo" accept="image/*">
            <input type="hidden" name="existing_logo" value="<?= htmlspecialchars($company['logo_path'] ?? '') ?>">
            
            <?php if (!empty($company['logo_path'])): ?>
                <div class="current-logo">
                    Mevcut Logo: 
                    <img src="uploads/<?= htmlspecialchars($company['logo_path']) ?>" alt="Mevcut Logo">
                    
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="submit-btn">Değişiklikleri Kaydet</button>
    </form>
</main>

</body>
</html>
