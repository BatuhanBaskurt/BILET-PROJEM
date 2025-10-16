<?php
// delete_trips.php - API gibi, GET ile sil, JSON dönsün ama redirect veya confirm var
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz']);
    exit;
}

$id = $_GET['id'] ?? '';

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ?");
        $stmt->execute([$id]);
        // Sil sonra redirect ana sayfaya veya JSON
        header("Location: trips_view.php"); // Veya echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID yok']);
}
?>
