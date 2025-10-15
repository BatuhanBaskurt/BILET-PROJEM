<?php
session_start();
include 'db.php'; // PDO ile SQLite bağlantısı

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if ($full_name && $password && $email) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO User (full_name, email, password, balance, created_at) VALUES (?, ?, ?, 800, datetime('now','localtime'))");
            $stmt->execute([$full_name, $email, $hashedPassword]);

            // Otomatik giriş
            $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $email;

                setcookie('user_id', $user['id'], [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => isset($_SERVER['HTTPS']),
                    'samesite' => 'Lax'
                ]);

                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                $message = "Hata: Bu e-posta zaten kayıtlı.";
            } else {
                $message = "Hata: " . $e->getMessage();
            }
        }
    } else {
        $message = "Lütfen tüm alanları doldurun.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form method="post">
        <label for="username">Ad Soyad:</label>
        <input type="text" id="username" name="username" placeholder="Tam İsim">

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" placeholder="Şifre">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Email">

        <button type="submit">Kayıt Ol</button>

        <p style="text-align:center; margin-top:20px; font-size:14px;">
            Zaten bir hesabın var mı? <a href="login.php">Giriş yap</a>
        </p>

        <?php if(isset($message) && $message) echo "<p>$message</p>"; ?>
    </form>
</body>
</html>