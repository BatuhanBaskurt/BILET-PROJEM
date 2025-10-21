<?php
include 'db.php'; 
session_start();

// Eğer kullanıcı zaten admin olarak giriş yapmışsa, onu direkt panele yolla
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}


// Eğer session'da token yoksa, yeni bir tane oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Formdan gelen token ile session'daki token eşleşiyor mu?
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "Geçersiz istek! Lütfen tekrar deneyin.";
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if ($email && $password) {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] !== 'admin') {
                    // Rol uymasa bile genel bir hata mesajı ver.
                    $message = "Hatalı email veya şifre.";
                } else {
                    // Başarılı giriş
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['balance'] = $user['balance'];
                    
                
                    header("Location: dashboard.php"); // admin paneline yönlendir
                    exit();
                }
            } else {
                $message = "Hatalı email veya şifre.";
            }
        } else {
            $message = "Lütfen tüm alanları doldurun.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <form method="post" class="login-form">
        <h2>Admin Paneli Girişi</h2>
        
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

        <label for="email">Email:</label>
        <input type="text" id="email" name="email" placeholder="Email" required>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" placeholder="Şifre" required>

        <button type="submit">Giriş Yap</button>

        <?php if ($message): ?>
            <p style="color:red;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
    </form>

</body>
</html>
