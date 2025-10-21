<?php
include 'db.php';
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcının bilgileri
$stmt = $pdo->prepare("SELECT full_name, balance, role FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['balance'] = $user['balance'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'] ?? 'user';

// GET parametreleri
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

if (!$from || !$to || !$date) {
    header("Location: index.php");
    exit;
}

// Sayfa ilk yüklendiğinde (POST değilse) kuponları temizle
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['discount_applied'] = [];
}

// Trips çek
$stmt = $pdo->prepare("
    SELECT tr.*, bc.logo_path AS company_logo, bc.name AS company_name
    FROM Trips tr
    LEFT JOIN Bus_Company bc ON tr.company_id = bc.id
    WHERE tr.departure_city = ? AND tr.destination_city = ? AND DATE(tr.departure_time) = ?
    ORDER BY tr.departure_time ASC
");
$stmt->execute([$from, $to, $date]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Boş koltuk hesaplama
foreach ($trips as &$trip) {
    $stmt2 = $pdo->prepare("SELECT COUNT(bs.id) FROM Booked_Seats bs JOIN Tickets t ON bs.ticket_id = t.id WHERE t.trip_id = ?");
    $stmt2->execute([$trip['id']]);
    $booked_count = $stmt2->fetchColumn();
    $trip['available_seats'] = $trip['seat_count'] - $booked_count;
}
unset($trip);

// Kupon uygulama işlemi
$discount_messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discount_code']) && isset($_POST['company_id'])) {
    $code = trim($_POST['discount_code']);
    $target_company_id = (int)$_POST['company_id'];

    $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND usage_limit > 0 AND datetime(expire_date) >= datetime('now')");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        $discount_messages[$target_company_id] = "Geçersiz kod!";
    } else {
        if ((int)$coupon['company_id'] !== $target_company_id) {
            $discount_messages[$target_company_id] = "Bu kupon bu şirket için geçerli değil!";
        } else {
            $_SESSION['discount_applied'][$target_company_id] = ['code' => $coupon['code'], 'discount' => $coupon['discount']];
            $discount_messages[$target_company_id] = "✅ ₺" . number_format($coupon['discount'], 2) . " indirim uygulandı!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sefer Sonuçları</title>
<link rel="stylesheet" href="style.css">
<style>
    body{
        padding-top: 220px;
    }
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="trip-container">
    <h1>Arama Sonuçları: <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?> (<?= htmlspecialchars($date) ?>)</h1>
    
    <?php if (count($trips) === 0): ?>
        <p>Bu tarihte uygun sefer bulunamadı.</p>
    <?php else: ?>
        <?php foreach ($trips as $trip): ?>
            <?php
            $price = (float)$trip['price'];
            $company_id_for_coupon = $trip['company_id'] ?? null;
            $applied = false;
            $discount_amount = 0;
            if ($company_id_for_coupon && isset($_SESSION['discount_applied'][$company_id_for_coupon])) {
                $discount_amount = (float)$_SESSION['discount_applied'][$company_id_for_coupon]['discount'];
                $new_price = $price - $discount_amount;
                if ($new_price < 0) $new_price = 0;
                $applied = true;
            } else {
                $new_price = $price;
            }
            ?>
            <div class="trip-card" id="trip-<?= $trip['id'] ?>">
                <h3><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></h3>
                <?php if (!empty($trip['company_name'])): ?>
                    <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name']) ?></p>
                <?php endif; ?>
                <p><strong>Kalkış Saati:</strong> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
                <p><strong>Varış Saati:</strong> <?= date('d.m.Y H:i', strtotime($trip['arrival_time'])) ?></p>
                <p>
                    <strong>Fiyat:</strong>
                    <?php if ($applied): ?>
                        <span class="price-old">₺<?= htmlspecialchars(number_format($price, 2)) ?></span>
                        <span class="price-new">₺<?= htmlspecialchars(number_format($new_price, 2)) ?></span>
                        <small style="margin-left:8px;color:#444;">(₺<?= htmlspecialchars(number_format($discount_amount,2)) ?> indirim)</small>
                    <?php else: ?>
                        <span class="price-new">₺<?= htmlspecialchars(number_format($price, 2)) ?></span>
                    <?php endif; ?>
                </p>
                <p><strong>Boş Koltuk:</strong> <?= htmlspecialchars($trip['available_seats']) ?></p>

                <div class="trip-actions">
                    <?php if ($trip['company_logo']): ?>
                        <div class="company-logo"><img src="uploads/<?= htmlspecialchars($trip['company_logo']) ?>" alt="Firma Logo"></div>
                    <?php endif; ?>

                    <div class="coupon-area">
                        <?php if (!$applied && $company_id_for_coupon): ?>
                        <div class="coupon-box">
                            <form method="POST" action="#trip-<?= $trip['id'] ?>">
                                <input type="hidden" name="company_id" value="<?= $company_id_for_coupon ?>">
                                <input type="text" name="discount_code" placeholder="Kupon kodu" required>
                                <button type="submit">Uygula</button>
                            </form>
                            <?php if (isset($discount_messages[$company_id_for_coupon])): ?>
                                <span class="coupon-message <?= strpos($discount_messages[$company_id_for_coupon], '✅') !== false ? 'success' : 'error' ?>">
                                    <?= htmlspecialchars($discount_messages[$company_id_for_coupon]) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="purchase-area">
                        <a class="buy-ticket-link" href="#" data-trip-id="<?= $trip['id'] ?>" data-seat-count="<?= $trip['seat_count'] ?>" data-price="<?= $new_price ?>" data-original-price="<?= $price ?>">Koltuk Seç ve Satın Al</a>
                        <span class="buy-result" id="result-<?= $trip['id'] ?>"></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="seat-modal-overlay">
    <div class="modal" id="seat-modal">
        <div class="modal-header">
            <h2>Koltuk Seçimi</h2>
            <button class="close-btn" id="close-modal-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="seat-map" id="seat-map"></div>
        </div>
        <div class="modal-footer">
            <p>Seçilen Koltuklar: <span id="selected-seats-info">Yok</span></p>
            <p>Toplam Tutar: <span id="total-price-info">0.00 ₺</span></p>
            <button class="confirm-purchase-btn" id="confirm-purchase-btn">Satın Almayı Onayla</button>
            <div class="buy-result" id="modal-result" style="margin-top:10px; text-align: right;"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalOverlay = document.getElementById('seat-modal-overlay');
    const seatMap = document.getElementById('seat-map');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const selectedSeatsInfo = document.getElementById('selected-seats-info');
    const totalPriceInfo = document.getElementById('total-price-info');
    const confirmBtn = document.getElementById('confirm-purchase-btn');
    const modalResult = document.getElementById('modal-result');

    let currentTripId = null;
    let priceWithCoupon = 0;
    let priceWithoutCoupon = 0;
    let selectedSeats = [];

    document.querySelectorAll('.buy-ticket-link').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            currentTripId = button.dataset.tripId;
            priceWithCoupon = parseFloat(button.dataset.price);
            priceWithoutCoupon = parseFloat(button.dataset.originalPrice);
            const seatCount = parseInt(button.dataset.seatCount);

            const response = await fetch(`get_booked_seats.php?trip_id=${currentTripId}`);
            const bookedSeats = await response.json();
            
            generateSeatMap(seatCount, bookedSeats);
            resetModal();
            modalOverlay.style.display = 'flex';
        });
    });

    function generateSeatMap(seatCount, bookedSeats) {
        seatMap.innerHTML = '';
        for (let i = 1; i <= seatCount; i++) {
            const seat = document.createElement('div');
            seat.classList.add('seat');
            seat.dataset.seatNumber = i;
            seat.textContent = i;
            
            if (i % 4 === 2) {
                const corridor = document.createElement('div');
                corridor.classList.add('seat', 'corridor');
                seatMap.appendChild(corridor);
            }
            
            if (bookedSeats.includes(String(i)) || bookedSeats.includes(i)) {
                seat.classList.add('booked');
            } else {
                seat.classList.add('available');
                seat.addEventListener('click', () => toggleSeatSelection(seat));
            }
            seatMap.appendChild(seat);
        }
    }

    function toggleSeatSelection(seat) {
        seat.classList.toggle('selected');
        const seatNumber = parseInt(seat.dataset.seatNumber);
        if (seat.classList.contains('selected')) {
            selectedSeats.push(seatNumber);
        } else {
            selectedSeats = selectedSeats.filter(s => s !== seatNumber);
        }
        updateSelectedInfo();
    }
    
    function updateSelectedInfo() {
        let total = 0;
        if (selectedSeats.length > 0) {
            selectedSeats.sort((a, b) => a - b);
            selectedSeatsInfo.textContent = selectedSeats.join(', ');
            total = priceWithCoupon + ((selectedSeats.length - 1) * priceWithoutCoupon);
        } else {
            selectedSeatsInfo.textContent = 'Yok';
        }
        totalPriceInfo.textContent = total.toFixed(2) + ' ₺';
    }
    
    confirmBtn.addEventListener('click', async () => {
        if (selectedSeats.length === 0) {
            modalResult.textContent = 'Lütfen en az bir koltuk seçin.';
            modalResult.style.color = 'red';
            return;
        }
        confirmBtn.disabled = true;
        const response = await fetch('buy_ticket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: currentTripId, seats: selectedSeats })
        });
        const data = await response.json();
        
        if (data.success) {
            modalResult.style.color = 'green';
            modalResult.textContent = `Bilet alındı! Koltuklar: ${data.seat_numbers}`;
            setTimeout(() => location.reload(), 2500);
        } else {
            modalResult.style.color = 'red';
            modalResult.textContent = `Hata: ${data.message}`;
            confirmBtn.disabled = false;
        }
    });
    
    closeModalBtn.addEventListener('click', () => modalOverlay.style.display = 'none');
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
        }
    });

    function resetModal(){
        selectedSeats = [];
        updateSelectedInfo();
        modalResult.textContent = '';
        confirmBtn.disabled = false;
    }
});
</script>
</body>
</html>

