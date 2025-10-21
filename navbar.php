<nav class="navbar">
    <div class="nav-left">
        <?php

            $home_link = 'index.php'; 
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                $home_link = 'dashboard.php';
            }
        ?>
        <a href="<?= $home_link ?>">Anasayfa</a>
    </div>

    <div class="nav-right">
        <?php if (!isset($_SESSION['user_id'])): ?>

            <a href="login.php">Giriş Yap</a>
            <a href="register.php">Kayıt Ol</a>

        <?php else: ?>
            <?php if (!isset($_SESSION['role'])) {
                $stmt = $pdo->prepare("SELECT role FROM User WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['role'] = $roleData['role'] ?? 'user';
            } ?>

            <?php if ($_SESSION['role'] === 'user'): ?>
                <span class="balance-badge">₺<?= htmlspecialchars($_SESSION['balance']) ?></span>
                <a href="ticket.php">Biletlerim</a>
                <a href="profile.php">Profilim</a>
                <a href="logout.php">Çıkış Yap</a>

            <?php elseif ($_SESSION['role'] === 'comp_admin'): ?>
                <a href="company.php">Şirketim</a>
                <a href="trips.php">Seferler</a>
                <a href="add_coupon.php">Kupon Ekle</a>
                <a href="profile.php">Profilim</a>
                <a href="logout.php">Çıkış Yap</a>

            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <span class="balance-badge">₺<?= htmlspecialchars($_SESSION['balance']) ?></span>
                <a href="users.php">Kullanıcılar</a>
                <a href="companies.php">Şirketler</a>
                <a href="logout.php">Çıkış Yap</a>

            <?php else: ?>
                <a href="logout.php">Çıkış Yap</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</nav>