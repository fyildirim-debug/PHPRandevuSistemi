<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Programı soft delete yapalım
    $sql = "UPDATE program_sablon SET aktif = 0 WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Silme işlemi başarısız oldu.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
} 