<?php
require_once '../config.php';
kullaniciKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $randevu_id = (int)$_POST['id'];
    $kullanici_id = $_SESSION['kullanici_id'];
    
    // Randevu bilgilerini al
    $randevu_sql = "SELECT r.*, rs.baslangic_saat 
                    FROM randevular r
                    JOIN randevu_saatleri rs ON r.tarih = rs.tarih AND r.saat = rs.baslangic_saat
                    WHERE r.id = ? AND r.kullanici_id = ? AND r.durum = 'onaylandi'";
    
    $stmt = $db->prepare($randevu_sql);
    $stmt->bind_param("ii", $randevu_id, $kullanici_id);
    $stmt->execute();
    $randevu = $stmt->get_result()->fetch_assoc();
    
    if (!$randevu) {
        echo json_encode([
            'success' => false,
            'message' => 'Randevu bulunamadı veya size ait değil.'
        ]);
        exit;
    }
    
    // Zaman kontrolü
    $randevu_zamani = new DateTime($randevu['tarih'] . ' ' . $randevu['baslangic_saat']);
    $simdiki_zaman = new DateTime();
    $zaman_farki = $randevu_zamani->getTimestamp() - $simdiki_zaman->getTimestamp();
    
    if ($zaman_farki <= 7200) { // 2 saat = 7200 saniye
        echo json_encode([
            'success' => false,
            'message' => 'Randevuya 2 saatten az kaldığı için iptal edilemez.'
        ]);
        exit;
    }
    
    // Transaction başlat
    $db->begin_transaction();
    
    try {
        // Randevu durumunu güncelle
        $guncelle_sql = "UPDATE randevular SET durum = 'iptal' WHERE id = ?";
        $stmt = $db->prepare($guncelle_sql);
        $stmt->bind_param("i", $randevu_id);
        $stmt->execute();
        
        // Randevu saatinin kontenjanını artır
        $kontenjan_sql = "UPDATE randevu_saatleri SET 
                         kalan_kontenjan = kalan_kontenjan + 1
                         WHERE tarih = ? AND baslangic_saat = ?";
        $stmt = $db->prepare($kontenjan_sql);
        $stmt->bind_param("ss", $randevu['tarih'], $randevu['baslangic_saat']);
        $stmt->execute();
        
        // Transaction'ı onayla
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Randevu başarıyla iptal edildi.'
        ]);
    } catch (Exception $e) {
        // Hata durumunda transaction'ı geri al
        $db->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Randevu iptal edilirken bir hata oluştu.'
        ]);
    }
} 