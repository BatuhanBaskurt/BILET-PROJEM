<?php
include 'db.php';
session_start();

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Şirketleri çek
$stmt = $pdo->prepare("SELECT id, name, logo_path, created_at FROM Bus_Company");
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form ve silme işleme
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ======================================================================
    // 🔥 GÜVENLİ DOSYA YÜKLEME MANTIĞI BAŞLIYOR 🔥
    // ======================================================================
    $new_logo_filename = null;
    $upload_error = '';

    // Eğer bir dosya yüklendiyse, önce onu güvenle işleyelim
    if (isset($_FILES['logo_path']) && $_FILES['logo_path']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo_path'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        // 1. Güvenlik: Dosya Boyutu
        if ($file['size'] > $max_file_size) {
            $upload_error = "Dosya boyutu 5 MB'den büyük olamaz.";
        }

        // 2. Güvenlik: Dosya Tipi ve Uzantısı
        if (empty($upload_error)) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_mime_type = mime_content_type($file['tmp_name']);

            if (!in_array($file_extension, $allowed_extensions) || !in_array($file_mime_type, $allowed_mime_types)) {
                $upload_error = "Geçersiz dosya türü. Sadece JPG, PNG, GIF veya WEBP yükleyebilirsiniz.";
            }
        }

        // 3. Güvenlik: Dosya İçeriği
        if (empty($upload_error)) {
            if (getimagesize($file['tmp_name']) === false) {
                $upload_error = "Yüklenen dosya geçerli bir resim dosyası değil.";
            }
        }
        
        // 4. Güvenlik: Dosyayı Güvenli İsimle Kaydetme
        if (empty($upload_error)) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $new_logo_filename = uniqid('company_', true) . '.' . $file_extension;
            $target_file = $target_dir . $new_logo_filename;
            
            if (!move_uploaded_file($file["tmp_name"], $target_file)) {
                $upload_error = "Dosya yüklenirken sunucuda bir hata oluştu.";
            }
        }
    }
    // Eğer bir hata varsa, işlemi burada durdurabiliriz. (Şimdilik hata mesajı için bir değişkenimiz var)
    if(!empty($upload_error)) {
        // Bu hatayı bir session'a atıp sayfada göstermek daha iyi olur, ama şimdilik ölelim.
        die($upload_error);
    }
    // ======================================================================
    // 🔥 GÜVENLİ DOSYA YÜKLEME MANTIĞI BİTTİ 🔥
    // ======================================================================

    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';

    try {
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && $id) {
            // Silme işlemi (Bu kısım zaten güvenliydi)
            $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($id && $name) {
            // Güncelleme
            $current_logo = $pdo->prepare("SELECT logo_path FROM Bus_Company WHERE id = ?");
            $current_logo->execute([$id]);
            $current_logo_path = $current_logo->fetchColumn();
            
            // Eğer yeni bir logo yüklendiyse onu kullan, yüklenmediyse eskisini koru.
            $logo_for_db = $new_logo_filename ?? $current_logo_path;
            
            $stmt = $pdo->prepare("UPDATE Bus_Company SET name = ?, logo_path = ? WHERE id = ?");
            $stmt->execute([$name, $logo_for_db, $id]);
        } elseif ($name) { // ID yoksa bu yeni oluşturmadır.
            // Oluşturma
            // Yeni logo yüklendiyse onun adını, yüklenmediyse null kullan.
            $logo_for_db = $new_logo_filename;
            
            $stmt = $pdo->prepare("INSERT INTO Bus_Company (name, logo_path) VALUES (?, ?)");
            $stmt->execute([$name, $logo_for_db]);
        }
    } catch (PDOException $e) {
        // Hata mesajını logla, kullanıcıya gösterme.
        error_log("Şirket yönetimi hatası: " . $e->getMessage());
    }
    // İşlem sonrası sayfayı yenileyerek temiz bir sayfa göster.
    header("Location: companies.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Şirket Yönetimi</title>
<link rel="stylesheet" href="style.css">
<style>
/* KAYMA SORUNU ÇÖZÜMÜ */
body {
    margin: 0;
    padding-top: 200px; /* 80'den 100'e çıkarıldı, daha rahat boşluk */
    background-color: #f4f7f6;
    font-family: "Poppins", Arial, sans-serif;
}
/* Navbar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    background-color: #dc3545;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: 60px;
    box-sizing: border-box;
    border-radius: 0;
}
.nav-left a, .nav-right a {
    color: white;
    text-decoration: none;
    margin: 0 15px;
    font-family: "Poppins", Arial, sans-serif;
    font-weight: 600;
    font-size: 16px;
    transition: color 0.3s ease;
}
.nav-right a:hover, .nav-left a:hover {
    color: #f0f0f0;
}
/* Container */
.user-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 25px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}
.user-container h1 {
    text-align: center;
    color: #dc3545;
    margin-bottom: 30px;
    font-size: 2.5em;
    font-weight: 700;
    margin-top: 0;
}
.company-schema {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr;
    gap: 10px;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #333;
}
.company-list {
    margin-bottom: 20px;
}
.company-item {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr;
    gap: 10px;
    align-items: flex-start;
    padding: 15px;
    background: #fff;
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-bottom: 2px solid #ddd;
}
.company-item:last-child {
    border-bottom: none;
}
.company-item span,
.company-item input[type="text"],
.company-item input[type="file"] {
    color: #333;
    font-size: 16px;
    padding: 5px;
    width: 100%;
    box-sizing: border-box;
    text-align: left;
}
.company-item input[type="file"] {
    margin-top: 5px;
}
.company-item .button-group {
    display: flex;
    gap: 10px;
    margin-top: 5px;
}
.company-item .update-btn,
.company-item .delete-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    width: 100%;
}
.company-item .update-btn {
    background-color: #000000;
    color: white;
}
.company-item .update-btn:hover {
    background-color: #333333;
    transform: scale(1.05);
}
.company-item .delete-btn {
    background-color: #dc3545;
    color: white;
}
.company-item .delete-btn:hover {
    background-color: #c82333;
    transform: scale(1.05);
}
.company-form {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.company-form h2 {
    text-align: center;
    color: #dc3545;
    margin-bottom: 20px;
    font-size: 2.5em;
    font-weight: 700;
    font-family: "Poppins", Arial, sans-serif;
    margin-top: 0;
}
.company-form form {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.company-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    width: 100%;
    text-align: left;
}
.company-form input[type="text"],
.company-form input[type="file"] {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}
.company-form button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background-color: #000000;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    align-self: center;
}
.company-form button:hover {
    background-color: #333333;
    transform: scale(1.05);
}
/* Responsive */
@media (max-width: 768px) {
    .user-container {
        margin: 20px 10px;
        padding: 15px;
    }
    
    .company-schema, .company-item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .company-item .button-group {
        flex-direction: column;
        gap: 5px;
    }
    
    .company-form {
        max-width: 100%;
    }
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
    <h1>Şirket Yönetimi</h1>
    <div class="company-schema">
        <span>Şirket Adı</span>
        <span>Logo Yolu</span>
        <span>Oluşturma Tarihi</span>
    </div>
    <div class="company-list">
        <?php foreach ($companies as $company): ?>
            <div class="company-item">
                <div>
                    <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>" form="form_<?= $company['id'] ?>">
                    <div class="button-group">
                        <button type="submit" form="form_<?= $company['id'] ?>" class="update-btn">Güncelle</button>
                        <button type="submit" form="form_<?= $company['id'] ?>" name="action" value="delete" class="delete-btn">Sil</button>
                    </div>
                </div>
                <div>
                    <span><?= htmlspecialchars($company['logo_path'] ?: 'Yok') ?></span>
                    <input type="file" name="logo_path" accept="image/*" form="form_<?= $company['id'] ?>" style="margin-top: 5px;">
                </div>
                <span><?= htmlspecialchars($company['created_at']) ?></span>
                <form method="POST" enctype="multipart/form-data" id="form_<?= $company['id'] ?>">
                    <input type="hidden" name="id" value="<?= $company['id'] ?>">
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="company-form">
        <h2>Şirket Oluştur</h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="name">Şirket Adı:</label>
            <input type="text" name="name" id="name" required>
            <label for="logo_path">Logo (Dosya):</label>
            <input type="file" name="logo_path" id="logo_path" accept="image/*">
            <button type="submit">Kaydet</button>
        </form>
    </div>
</div>

</body>
</html>
