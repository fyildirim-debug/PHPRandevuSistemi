<?php
require_once '../../config.php';
adminKontrol();

try {
    $db->begin_transaction();
    
    // Randevu saatlerini temizle (kullanıcı_id'leri NULL yap)
    $sql = "UPDATE randevu_saatleri SET 
            kullanici_id = NULL, 
            kalan_kontenjan = kontenjan 
            WHERE kullanici_id IS NOT NULL";
    $db->query($sql);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Randevu kayıtları başarıyla temizlendi.'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Hata oluştu: ' . $e->getMessage()
    ]);
} 