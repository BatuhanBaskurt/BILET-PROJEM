<?php
// 1. ADIM: TCPDF Kütüphanesini Dahil Et
require_once('tcpdf/tcpdf.php');

include 'db.php';
session_start();

// 2. ADIM: GÜVENLİK - Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 3. ADIM: GÜVENLİK - Gelen bilet ID'sini ve session'daki kullanıcı ID'sini al
$ticket_id = $_GET['ticket_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// 4. ADIM: VERİTABANI - Güvenli sorgu ile bilet bilgilerini çek
$stmt = $pdo->prepare("
    SELECT 
        t.id AS ticket_id, t.seat_number, t.total_price,
        u.full_name AS user_full_name,
        tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, -- <<< 1. DEĞİŞİKLİK BURADA
        bc.name AS company_name, bc.logo_path
    FROM Tickets t
    JOIN User u ON t.user_id = u.id
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

// 5. ADIM: GÜVENLİK - Bilet bulunamazsa işlemi durdur
if (!$ticket) {
    die("Hata: Bilet bulunamadı veya bu bilete erişim yetkiniz yok.");
}

// 6. ADIM: PDF OLUŞTURMA - TCPDF ile PDF'i hazırlayalım

// TCPDF nesnesini oluştur
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Döküman bilgilerini ayarla
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sizin Site Adınız');
$pdf->SetTitle('Yolcu Bileti - ' . $ticket['ticket_id']);
$pdf->SetSubject('Elektronik Yolcu Bileti');

// Header ve footer'ı kaldır
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Sayfa kenar boşluklarını ayarla
$pdf->SetMargins(15, 15, 15);

// Yeni bir sayfa ekle
$pdf->AddPage();

// Türkçe karakterler için fontu ayarla
$pdf->SetFont('dejavusans', '', 12);

// Tarihleri formatlayalım
$departure_date = new DateTime($ticket['departure_time']);
$arrival_date = new DateTime($ticket['arrival_time']); // <<< 2. DEĞİŞİKLİK BURADA

// Biletin HTML içeriğini hazırlıyoruz
$html = '
<style>
    .ticket-box {
        border: 1px solid #ccc;
        padding: 15px;
        font-family: dejavusans, sans-serif;
    }
    .company-header {
        text-align: center;
        padding-bottom: 10px;
        border-bottom: 1px dashed #ccc;
    }
    .company-header img {
        max-width: 140px;
        max-height: 45px;
    }
    .company-header h2 {
        margin: 0;
        font-size: 18px;
    }
    .ticket-details {
        width: 100%;
        margin-top: 20px;
    }
    .ticket-details td {
        padding: 6px;
        font-size: 12px;
    }
    .label {
        font-weight: bold;
    }
</style>

<div class="ticket-box">
    <div class="company-header">
        ' . ($ticket['logo_path'] ? '<img src="uploads/' . htmlspecialchars($ticket['logo_path']) . '">' : '') . '
        <h2>' . htmlspecialchars($ticket['company_name']) . '</h2>
    </div>
    <h3 style="text-align:center;">Elektronik Yolcu Bileti</h3>
    <table class="ticket-details">
        <tr>
            <td class="label">Yolcu:</td>
            <td>' . htmlspecialchars($ticket['user_full_name']) . '</td>
        </tr>
        <tr>
            <td class="label">Güzergah:</td>
            <td><b>' . htmlspecialchars($ticket['departure_city']) . '</b> &rarr; <b>' . htmlspecialchars($ticket['destination_city']) . '</b></td>
        </tr>
        <tr>
            <td class="label">Kalkış Zamanı:</td>
            <td>' . $departure_date->format('d.m.Y - H:i') . '</td>
        </tr>
        <tr>
            <td class="label">Varış Zamanı (Tahmini):</td>
            <td>' . $arrival_date->format('d.m.Y - H:i') . '</td>
        </tr>
        <tr>
            <td class="label">Koltuk No:</td>
            <td>' . htmlspecialchars($ticket['seat_number']) . '</td>
        </tr>
        <tr>
            <td class="label">Fiyat:</td>
            <td>' . htmlspecialchars(number_format($ticket['total_price'], 2, ',', '.')) . ' TL</td>
        </tr>
        <tr>
            <td class="label">Bilet Numarası:</td>
            <td>' . htmlspecialchars($ticket['ticket_id']) . '</td>
        </tr>
    </table>
    <p style="text-align:center; margin-top:20px; font-size:10px;">İyi yolculuklar dileriz!</p>
</div>
';

// Hazırlanan HTML'i PDF'e yazdır
$pdf->writeHTML($html, true, false, true, false, '');

// 7. ADIM: PDF'i ÇIKTI OLARAK VER
$pdf->Output('bilet-'.$ticket_id.'.pdf', 'D');

exit;
?>
