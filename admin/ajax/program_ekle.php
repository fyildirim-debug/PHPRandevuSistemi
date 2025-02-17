<?php
require_once '../../config.php';
adminKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gun = $_POST['gun'] ?? '';
    $baslangic_saat = $_POST['baslangic_saat'] ?? '';
    $bitis_saat = $_POST['bitis_saat'] ?? '';
    $kontenjan = (int)($_POST['kontenjan'] ?? 1);
    
    // Gün adını küçük harfe çevir
    $gun = mb_strtolower($gun, 'UTF-8');
    
    // Türkçe karakter düzeltmeleri
    $gun = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $gun);
    
    $sql = "INSERT INTO program_sablon (gun, baslangic_saat, bitis_saat, kontenjan, aktif) 
            VALUES (?, ?, ?, ?, 1)";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssi", $gun, $baslangic_saat, $bitis_saat, $kontenjan);
    
    if ($stmt->execute()) {
        // Başarılı yanıt
        echo json_encode([
            'success' => true,
            'message' => 'Program saati başarıyla eklendi.',
            'data' => [
                'id' => $stmt->insert_id,
                'gun' => ucfirst($gun),
                'baslangic_saat' => $baslangic_saat,
                'bitis_saat' => $bitis_saat,
                'kontenjan' => $kontenjan
            ]
        ]);
    } else {
        // Hata yanıtı
        echo json_encode([
            'success' => false,
            'message' => 'Program saati eklenirken bir hata oluştu: ' . $db->error
        ]);
    }
} 