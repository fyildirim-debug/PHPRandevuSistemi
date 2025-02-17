<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    $sql = "SELECT * FROM program_sablon WHERE id = ? AND aktif = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    
    if ($program) {
        echo json_encode([
            'success' => true,
            'program' => $program
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Program bulunamadı.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]);
} 