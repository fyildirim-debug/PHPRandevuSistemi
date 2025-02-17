<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['program_id'];
    $gun = trim($_POST['gun']);
    $baslangic_saat = date('H:i:00', strtotime($_POST['baslangic_saat']));
    $bitis_saat = date('H:i:00', strtotime($_POST['bitis_saat']));
    $kontenjan = (int)$_POST['kontenjan'];
    
    $sql = "UPDATE program_sablon SET 
            gun = ?,
            baslangic_saat = ?,
            bitis_saat = ?,
            kontenjan = ?
            WHERE id = ? AND aktif = 1";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssii", $gun, $baslangic_saat, $bitis_saat, $kontenjan, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Program başarıyla güncellendi.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Güncelleme sırasında hata oluştu: ' . $db->error
        ]);
    }
} 