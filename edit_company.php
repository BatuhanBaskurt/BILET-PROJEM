<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Yetkisiz erişim!';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $logo_path = isset($_POST['logo_path']) ? trim($_POST['logo_path']) : '';

    if ($id && $name) {
        try {
            $stmt = $pdo->prepare("UPDATE Bus_Company SET name = ?, logo_path = ?, created_at = datetime('now','localtime') WHERE id = ?");
            $stmt->execute([$name, $logo_path, $id]);
            $response['success'] = true;
            $response['message'] = 'Firma başarıyla güncellendi.';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                $response['message'] = 'Bu firma adı zaten kayıtlı.';
            } else {
                $response['message'] = 'Hata: ' . $e->getMessage();
            }
        }
    } else {
        $response['message'] = 'Lütfen gerekli bilgileri girin.';
    }
}

echo json_encode($response);
exit;
?>