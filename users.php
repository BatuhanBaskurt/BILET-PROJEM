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
/* CSS koduna dokunmadım, aynen bıraktım */
.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    background-color: #dc3545;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-radius: 0;
}
.nav-left a, .nav-right a {
    color: white;
    text-decoration: none;
    margin: 0 15px;
    font-family: "Poppins", Arial, sans-serif;
    font-weight: 600;
    font-size: 16px;
}
.user-container {
    max-width: 1200px;
    margin: 100px auto 20px;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    font-family: "Poppins", Arial, sans-serif;
}
.user-container h1 {
    text-align: center;
    color: #dc3545;
    margin-bottom: 40px;
    font-size: 2.5em;
    font-weight: 700;
}
.user-schema, .user-item {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1.5fr 1.5fr 1fr; 
    gap: 15px;
    align-items: center;
}
.user-schema {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #333;
}
.user-list {
    list-style: none;
    padding: 0;
}
.user-item {
    padding: 15px;
    background: #fff;
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
}
.user-item span {
    color: #333;
    font-size: 16px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-item .role-select, .user-item .company-select {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ddd;
    background-color: #fff;
    width: 100%;
    box-sizing: border-box;
}
.user-item .company-cell.hidden {
    visibility: hidden;
}
.user-item .actions-cell {
    grid-column: 6;
}
.save-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background-color: #000;
    color: white;
    font-weight: 600;
    margin-top: 20px;
    display: block;
    width: fit-content;
    margin-left: auto;
    margin-right: auto;
}
.delete-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    background-color: #dc3545;
    color: white;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
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
