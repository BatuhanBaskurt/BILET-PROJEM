<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Yetkisiz erişim!';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    $company_id = isset($_POST['company_id']) ? trim($_POST['company_id']) : '0';

    if ($user_id && $role && $role !== 'admin') {
        try {
            // User rolünde company_id 0 olabilir, Firma Admin'de zorunlu
            $stmt = $pdo->prepare("UPDATE User SET role = ?, company_id = ? WHERE id = ?");
            $stmt->execute([$role, $company_id === '0' && $role === 'user' ? null : $company_id, $user_id]);
            $response['success'] = true;
        } catch (PDOException $e) {
            $response['message'] = 'Hata: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
?>