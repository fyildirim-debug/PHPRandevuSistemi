<?php
require_once '../config.php';
kullaniciKontrol();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $randevu_id = (int)$_POST['id'];
    $kullanici_id = $_SESSION['kullanici_id'];
    
    // Haftalık limit kontrolü
    $haftalik_limit_sql = "SELECT haftalik_randevu_limiti FROM ayarlar LIMIT 1";
    $haftalik_limit = $db->query($haftalik_limit_sql)->fetch_assoc()['haftalik_randevu_limiti'];
    
    $haftalik_randevu_sql = "SELECT COUNT(*) as sayi FROM randevular 
                             WHERE kullanici_id = ? 
                             AND tarih BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
                             AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
                             AND durum = 'onaylandi'";
    
    $stmt = $db->prepare($haftalik_randevu_sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $haftalik_randevu = $stmt->get_result()->fetch_assoc()['sayi'];
    
    if ($haftalik_randevu >= $haftalik_limit) {
        echo json_encode([
            'success' => false,
            'message' => 'Haftalık randevu limitiniz dolmuştur.'
        ]);
        exit;
    }
    
    // Randevu müsaitlik kontrolü
    $kontrol_sql = "SELECT * FROM randevu_saatleri WHERE id = ? AND aktif = 1 AND kalan_kontenjan > 0";
    $stmt = $db->prepare($kontrol_sql);
    $stmt->bind_param("i", $randevu_id);
    $stmt->execute();
    $randevu = $stmt->get_result()->fetch_assoc();
    
    if (!$randevu) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu randevu alınamaz.'
        ]);
        exit;
    }

    // Geçmiş tarih kontrolü
    $bugun = new DateTime();
    $bugun->setTime(0, 0); // Bugünün başlangıcı
    $randevu_tarihi = new DateTime($randevu['tarih'] . ' ' . $randevu['baslangic_saat']);
    
    if ($randevu_tarihi < $bugun) {
        echo json_encode([
            'success' => false,
            'message' => 'Geçmiş tarihlere randevu alınamaz.'
        ]);
        exit;
    }

    // Aynı gün için saat kontrolü
    if ($randevu['tarih'] == $bugun->format('Y-m-d')) {
        $simdiki_saat = new DateTime();
        if ($randevu_tarihi < $simdiki_saat) {
            echo json_encode([
                'success' => false,
                'message' => 'Geçmiş saatlere randevu alınamaz.'
            ]);
            exit;
        }
    }

    // Kullanıcının aynı saatte başka randevusu var mı kontrolü
    $ayni_saat_kontrol = "SELECT COUNT(*) as sayi FROM randevular 
                          WHERE kullanici_id = ? 
                          AND tarih = ? 
                          AND saat = ? 
                          AND durum = 'onaylandi'";
    $stmt = $db->prepare($ayni_saat_kontrol);
    $stmt->bind_param("iss", $kullanici_id, $randevu['tarih'], $randevu['baslangic_saat']);
    $stmt->execute();
    $ayni_saat_randevu = $stmt->get_result()->fetch_assoc()['sayi'];

    if ($ayni_saat_randevu > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu saatte zaten bir randevunuz bulunmaktadır.'
        ]);
        exit;
    }
    
    // Transaction başlat
    $db->begin_transaction();
    
    try {
        // Randevular tablosuna kayıt ekle
        $randevu_ekle_sql = "INSERT INTO randevular (kullanici_id, tarih, saat, durum) VALUES (?, ?, ?, 'onaylandi')";
        $stmt = $db->prepare($randevu_ekle_sql);
        $stmt->bind_param("iss", $kullanici_id, $randevu['tarih'], $randevu['baslangic_saat']);
        $stmt->execute();
        
        // Randevu saatinin kontenjanını güncelle
        $sql = "UPDATE randevu_saatleri SET 
                kalan_kontenjan = kalan_kontenjan - 1
                WHERE id = ? AND aktif = 1";
                
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $randevu_id);
        $stmt->execute();
        
        // Transaction'ı onayla
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Randevu başarıyla alındı.'
        ]);
    } catch (Exception $e) {
        // Hata durumunda transaction'ı geri al
        $db->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Randevu alınırken bir hata oluştu.'
        ]);
    }
} 