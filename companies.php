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
   
    if(!empty($upload_error)) {

        die($upload_error);
    }
    
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';

    try {
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && $id) {
            // Silme işlemi 
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
 
            $logo_for_db = $new_logo_filename;
            
            $stmt = $pdo->prepare("INSERT INTO Bus_Company (name, logo_path) VALUES (?, ?)");
            $stmt->execute([$name, $logo_for_db]);
        }
    } catch (PDOException $e) {
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
body {
    margin: 0;
    padding-top: 200px; 
    background-color: #f4f7f6;
    font-family: "Poppins", Arial, sans-serif;
}
</style>
</head>
<body>

<nav class="navbar-admin">
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
