<?php
include 'db.php';
session_start();

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

// Link ile gelen silme isteği
if (isset($_GET['delete_user'])) {
    $user_id_to_delete = (int)$_GET['delete_user'];
    if ($user_id_to_delete != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM User WHERE id = ?");
            $stmt->execute([$user_id_to_delete]);
        } catch (PDOException $e) {
            die("Kullanıcı silinirken hata oluştu: " . $e->getMessage());
        }
    }
    header("Location: users.php"); // Dosya adını kendine göre düzenle
    exit;
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT full_name, email, balance, role, id, company_id FROM User WHERE role != 'admin' AND id != ?");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Şirketleri çek
$stmt = $pdo->prepare("SELECT id, name FROM Bus_Company");
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kullanıcı Yönetimi</title>
<link rel="stylesheet" href="style.css">
<style>

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
    <h1>Kullanıcı Yönetimi</h1>
    <div class="user-schema">
        <span>Ad Soyad</span>
        <span>E-posta</span>
        <span>Bakiye</span>
        <span>Rol</span>
        <span>Atanacak Şirket</span>
        <span>İşlemler</span>
    </div>
    <ul class="user-list">
        <?php foreach ($users as $user): ?>
            <li class="user-item" id="user_<?= $user['id'] ?>">
                <span><?= htmlspecialchars($user['full_name']) ?></span>
                <span><?= htmlspecialchars($user['email']) ?></span>
                <span><?= htmlspecialchars($user['balance']) ?> TL</span>
                <select class="role-select" id="role_<?= $user['id'] ?>" onchange="toggleCompanySelect(<?= $user['id'] ?>, this.value)">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="comp_admin" <?= $user['role'] === 'comp_admin' ? 'selected' : '' ?>>Firma Admin</option>
                </select>
                <div class="company-cell <?= $user['role'] !== 'comp_admin' ? 'hidden' : '' ?>" id="company_cell_<?= $user['id'] ?>">
                    <select class="company-select" id="company_<?= $user['id'] ?>">
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['id'] ?>" <?= $user['company_id'] == $company['id'] ? 'selected' : '' ?>><?= htmlspecialchars($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="actions-cell">
                    <a href="users.php?delete_user=<?= $user['id'] ?>" class="delete-btn">Sil</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <button class="save-btn" onclick="saveAllUsers()">Onayla</button>
</div>

<script>
function toggleCompanySelect(userId, role) {
    const companyCell = document.getElementById('company_cell_' + userId);
    if (role === 'comp_admin') {
        companyCell.classList.remove('hidden');
    } else {
        companyCell.classList.add('hidden');
    }
}
function saveAllUsers() {
    const userItems = document.querySelectorAll('.user-item');
    let allRequests = [];

    userItems.forEach(item => {
        const userId = item.id.replace('user_', '');
        const role = document.getElementById('role_' + userId).value;
        const companySelect = document.getElementById('company_' + userId);
        let companyId = (role === 'comp_admin') ? companySelect.value : null;

        let request = fetch('update_role.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&role=${role}&company_id=${companyId || ''}`
        }).then(response => response.json());
        allRequests.push(request);
    });

    Promise.all(allRequests).then(() => {
        location.reload();
    }).catch(error => {
        console.error('Hata:', error);
        alert('Değişiklikler kaydedilirken bir hata oluştu.');
    });
}
</script>

</body>
</html>
