<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Önce randevuyu kontrol et
    $check_sql = "SELECT kullanici_id FROM randevu_saatleri WHERE id = ? AND aktif = 1";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $randevu = $check_stmt->get_result()->fetch_assoc();
    
    if ($randevu) {
        // Randevuyu sil (soft delete)
        $sql = "UPDATE randevu_saatleri SET aktif = 0 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Randevu başarıyla silindi.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Randevu silinirken hata oluştu.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Randevu bulunamadı.'
        ]);
    }
} 