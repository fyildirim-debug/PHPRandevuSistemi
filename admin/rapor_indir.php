<?php
require_once '../config.php';
adminKontrol();

// Tarih aralığını al
$baslangic = $_GET['baslangic'] ?? date('Y-m-d');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

// Randevuları getir
$sql = "SELECT 
            rs.tarih,
            rs.saat,
            k.tc_no,
            k.ad_soyad,
            k.telefon,
            k.email,
            DATE_FORMAT(rs.created_at, '%d.%m.%Y %H:%i') as kayit_tarihi
        FROM randevu_saatleri rs
        LEFT JOIN kullanicilar k ON k.id = rs.kullanici_id
        WHERE rs.aktif = 1 
        AND rs.kullanici_id IS NOT NULL
        AND rs.tarih BETWEEN ? AND ?
        ORDER BY rs.tarih, rs.saat";

$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $baslangic, $bitis);
$stmt->execute();
$result = $stmt->get_result();

// Excel başlıkları
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=randevu_raporu.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Excel içeriği
echo "Randevu Tarihi\tRandevu Saati\tTC Kimlik No\tAd Soyad\tTelefon\tEmail\tKayıt Tarihi\n";

while ($row = $result->fetch_assoc()) {
    echo $row['tarih'] . "\t";
    echo $row['saat'] . "\t";
    echo $row['tc_no'] . "\t";
    echo $row['ad_soyad'] . "\t";
    echo $row['telefon'] . "\t";
    echo $row['email'] . "\t";
    echo $row['kayit_tarihi'] . "\n";
} 