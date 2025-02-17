<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['randevu_id'];
    $tarih = $_POST['tarih'];
    $baslangic_saat = $_POST['baslangic_saat'];
    $bitis_saat = $_POST['bitis_saat'];
    $kontenjan = (int)$_POST['kontenjan'];
    
    // Saat formatlarını düzelt
    $baslangic_saat = date('H:i:00', strtotime($baslangic_saat));
    $bitis_saat = date('H:i:00', strtotime($bitis_saat));
    
    // Tarih formatını düzelt
    $tarih = date('Y-m-d', strtotime($tarih));
    
    // Çakışma kontrolü
    $check_sql = "SELECT COUNT(*) as sayi FROM randevu_saatleri 
                  WHERE tarih = ? 
                  AND id != ?
                  AND aktif = 1
                  AND (
                      (baslangic_saat <= ? AND bitis_saat > ?) OR
                      (baslangic_saat < ? AND bitis_saat >= ?) OR
                      (baslangic_saat >= ? AND bitis_saat <= ?)
                  )";
                  
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bind_param("sissssss", 
        $tarih, 
        $id,
        $baslangic_saat, 
        $baslangic_saat,
        $bitis_saat, 
        $bitis_saat,
        $baslangic_saat, 
        $bitis_saat
    );
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['sayi'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu saat aralığında başka bir randevu bulunmaktadır!'
        ]);
        exit;
    }
    
    // Güncelleme işlemi
    $sql = "UPDATE randevu_saatleri SET 
            tarih = ?,
            baslangic_saat = ?,
            bitis_saat = ?,
            kontenjan = ?,
            kalan_kontenjan = ?
            WHERE id = ? AND aktif = 1";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssiii", $tarih, $baslangic_saat, $bitis_saat, $kontenjan, $kontenjan, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Randevu başarıyla güncellendi.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Güncelleme sırasında hata oluştu.'
        ]);
    }
} 