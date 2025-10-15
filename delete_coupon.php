<?php
session_start();
include 'db.php';

// Sadece comp_admin erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: coupon_add.php');
exit;
?>
