<?php
require_once '../config.php';
adminKontrol();

// Transaction başlat
$db->begin_transaction();

try {
    // Mevcut randevuları al
    $sql = "SELECT * FROM randevu_saatleri WHERE kullanici_id IS NOT NULL AND aktif = 1";
    $result = $db->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        // Randevular tablosuna ekle
        $ekle_sql = "INSERT INTO randevular (kullanici_id, tarih, saat, durum) 
                     VALUES (?, ?, ?, 'onaylandi')";
        $stmt = $db->prepare($ekle_sql);
        $stmt->bind_param("iss", $row['kullanici_id'], $row['tarih'], $row['baslangic_saat']);
        $stmt->execute();
    }
    
    // Randevu_saatleri tablosundaki kullanici_id'leri NULL yap
    $sql = "UPDATE randevu_saatleri SET kullanici_id = NULL WHERE kullanici_id IS NOT NULL";
    $db->query($sql);
    
    // Kalan kontenjanları güncelle
    $sql = "UPDATE randevu_saatleri rs SET 
            rs.kalan_kontenjan = rs.kontenjan - (
                SELECT COUNT(*) FROM randevular r 
                WHERE r.tarih = rs.tarih 
                AND r.saat = rs.baslangic_saat 
                AND r.durum = 'onaylandi'
            )
            WHERE rs.aktif = 1";
    $db->query($sql);
    
    // Transaction'ı onayla
    $db->commit();
    
    echo "Randevular başarıyla aktarıldı!";
} catch (Exception $e) {
    // Hata durumunda geri al
    $db->rollback();
    echo "Hata oluştu: " . $e->getMessage();
} 