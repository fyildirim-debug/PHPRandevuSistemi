<?php
require_once '../../config.php';
adminKontrol();

try {
    $db->begin_transaction();
    
    // Önce randevu saatlerini temizle
    $sql = "DELETE FROM randevu_saatleri WHERE sablon_id IN (SELECT id FROM program_sablon)";
    $db->query($sql);
    
    // Sonra program şablonunu temizle
    $sql = "DELETE FROM program_sablon WHERE 1";
    $db->query($sql);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Program şablonu ve ilişkili randevular başarıyla temizlendi.'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Hata oluştu: ' . $e->getMessage()
    ]);
} 