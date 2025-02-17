<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['randevu_id'];
    $kontenjan = (int)$_POST['kontenjan'];
    
    $sql = "UPDATE randevu_saatleri SET 
            kalan_kontenjan = ?
            WHERE id = ? AND aktif = 1";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $kontenjan, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Kontenjan başarıyla güncellendi.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Güncelleme sırasında hata oluştu.'
        ]);
    }
} 