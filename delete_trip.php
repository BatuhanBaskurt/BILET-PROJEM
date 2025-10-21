<?php

include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'comp_admin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Önce şirketi kontrol et
    $stmt = $pdo->prepare("SELECT company_id FROM Trips WHERE id = ?");
    $stmt->execute([$id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trip && $trip['company_id'] == $_SESSION['company_id']) {
        $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header("Location: company.php");
exit;
