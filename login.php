<?php
// 1. ÖNCE KURALLAR VE VERİTABANI BAĞLANTISI YÜKLENİR
// Bu satır, session güvenlik ayarlarını (ini_set) içeren db.php'yi yükler.
include 'db.php'; 

// 2. SONRA GÜVENLİ KURALLARLA SESSION BAŞLATILIR
session_start();

// Hataları gizle, logla
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');

// CSRF token oluştur (Senin kodun, zaten doğru)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF kontrolü (Senin kodun, zaten doğru)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Geçersiz istek.";
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        // Giriş doğrulama (Senin kodun, zaten doğru)
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $password) {
            try {
                $stmt = $pdo->prepare("SELECT id, email, password, role FROM User WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Kullanıcı ve şifre kontrolü (Senin kodun, zaten doğru)
                if ($user && password_verify($password, $user['password'])) {
                    
                    // Başarılı giriş
                    session_regenerate_id(true); // Bu çok önemli bir güvenlik adımı, harika!
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // !!! KRİTİK GÜVENLİK AÇIĞI YARATAN setcookie KODU BURADAN SİLİNDİ !!!
                    // PHP'nin kendi session cookie'si zaten yeterli ve güvenlidir.

                    header("Location: index.php");
                    exit();
                    
                } else {
                    $message = "Hatalı email veya şifre.";
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $message = "Bir hata oluştu.";
            }
        } else {
            $message = "Lütfen geçerli bir email ve şifre girin.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Email" required>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" placeholder="Şifre" required>

        <button type="submit">Giriş Yap</button>

        <?php if ($message) echo "<p style='color: red; text-align: center;'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>"; ?>

        <p style="text-align:center; margin-top:20px; font-size:14px;">
            Hesabın yok mu? <a href="register.php">Kayıt ol</a>
        </p>
    </form>
</body>
</html>
