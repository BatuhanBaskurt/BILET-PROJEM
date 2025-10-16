<?php
header('Content-Type: application/json');
include 'db.php';
session_start();

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Yetkisiz erişim!';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = 'Firma başarıyla silindi.';
        } catch (PDOException $e) {
            $response['message'] = 'Hata: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Lütfen bir firma seçin.';
    }
}

echo json_encode($response);
exit;
?>
