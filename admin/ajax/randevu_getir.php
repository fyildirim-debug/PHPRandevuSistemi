<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    $sql = "SELECT * FROM randevu_saatleri WHERE id = ? AND aktif = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $randevu = $stmt->get_result()->fetch_assoc();
    
    if ($randevu) {
        echo json_encode([
            'success' => true,
            'randevu' => $randevu
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Randevu bulunamadı.'
        ]);
    }
} 