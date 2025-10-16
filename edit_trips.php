<?php
// edit_trips.php - API gibi çalışsın ama form görsel, GET ile id al, POST ile update


include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update API
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = trim($_POST['price']);
    $seat_count = trim($_POST['seat_count']);

    try {
        $stmt = $pdo->prepare("UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, seat_count = ? WHERE id = ?");
        $stmt->execute([$departure_city, $destination_city, $departure_time, $arrival_time, $price, $seat_count, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// GET: Form göster (görsel edit)
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    echo "Sefer bulunamadı";
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sefer Düzenle</title>
<style>
    /* Basit form stil, navbar yok çünkü API ama admin için */
    body { font-family: Arial; background: rgba(255,255,255,0.9); padding: 20px; }
    form { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; }
    button { padding: 10px 20px; background: #000; color: white; border: none; border-radius: 6px; cursor: pointer; }
</style>
</head>
<body>
<form method="POST" action="edit_trips.php">
    <input type="hidden" name="id" value="<?= $trip['id'] ?>">
    <label>Kalkış Şehri: <input type="text" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required></label>
    <label>Varış Şehri: <input type="text" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required></label>
    <label>Kalkış Zamanı: <input type="datetime-local" name="departure_time" value="<?= str_replace(' ', 'T', $trip['departure_time']) ?>" required></label>
    <label>Varış Zamanı: <input type="datetime-local" name="arrival_time" value="<?= str_replace(' ', 'T', $trip['arrival_time']) ?>" required></label>
    <label>Fiyat: <input type="number" name="price" value="<?= $trip['price'] ?>" required></label>
    <label>Koltuk Sayısı: <input type="number" name="seat_count" value="<?= $trip['seat_count'] ?>" required></label>
    <button type="submit">Güncelle</button>
</form>
</body>
</html>
