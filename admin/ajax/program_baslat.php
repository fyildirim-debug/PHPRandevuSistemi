<?php
require_once '../../config.php';
adminKontrol();

function ingilizceGun($turkceGun) {
    $gunler = [
        'pazartesi' => 'monday',
        'sali' => 'tuesday',
        'carsamba' => 'wednesday',
        'persembe' => 'thursday',
        'cuma' => 'friday',
        'cumartesi' => 'saturday',
        'pazar' => 'sunday'
    ];
    return $gunler[$turkceGun] ?? $turkceGun;
}

try {
    // Debug log başlat
    $debug = [];
    
    // Aktif programı kontrol et
    $check_sql = "SELECT MAX(tarih) as son_tarih FROM randevu_saatleri WHERE aktif = 1";
    $result = $db->query($check_sql);
    $row = $result->fetch_assoc();
    $son_tarih = $row['son_tarih'];
    $debug[] = "Son tarih: " . ($son_tarih ?? 'Yok');

    // Başlangıç tarihini belirle
    if ($son_tarih) {
        // Son tarihten sonraki pazartesi
        $baslangic = date('Y-m-d', strtotime('next monday', strtotime($son_tarih)));
    } else {
        // Bu haftanın pazartesi günü
        $baslangic = date('Y-m-d', strtotime('monday this week'));
    }

    // Bitiş tarihi (1 hafta)
    $bitis = date('Y-m-d', strtotime($baslangic . ' +6 days'));
    $debug[] = "Tarih aralığı: $baslangic - $bitis";

    // Transaction başlat
    $db->begin_transaction();

    // Program şablonundan randevuları oluştur
    $sql = "SELECT * FROM program_sablon WHERE aktif = 1";
    $program = $db->query($sql);
    $debug[] = "Aktif şablon sayısı: " . $program->num_rows;

    $eklenen = 0;
    while ($sablon = $program->fetch_assoc()) {
        $debug[] = "Şablon işleniyor: Gün=" . $sablon['gun'] . ", Saat=" . $sablon['baslangic_saat'];
        
        $tarih = $baslangic;
        while (strtotime($tarih) <= strtotime($bitis)) {
            $gun_adi = strtolower(date('l', strtotime($tarih)));
            $debug[] = "  Tarih kontrol: $tarih ($gun_adi)";
            
            // Gün adını karşılaştır
            if ($gun_adi == ingilizceGun($sablon['gun'])) {
                $debug[] = "    Gün eşleşti: " . $sablon['gun'] . " = " . $gun_adi;
                
                // Önce bu tarih ve saatte randevu var mı kontrol et
                $check_sql = "SELECT id FROM randevu_saatleri 
                            WHERE tarih = ? AND baslangic_saat = ? AND aktif = 1";
                $check_stmt = $db->prepare($check_sql);
                $check_stmt->bind_param("ss", $tarih, $sablon['baslangic_saat']);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->num_rows > 0;
                $debug[] = "    Randevu kontrolü: " . ($exists ? "Mevcut" : "Yok");

                if (!$exists) {
                    $sql = "INSERT INTO randevu_saatleri (
                                tarih, baslangic_saat, bitis_saat, 
                                kontenjan, kalan_kontenjan, sablon_id, aktif
                            ) VALUES (?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $db->prepare($sql);
                    $kalan_kontenjan = $sablon['kontenjan'];
                    $stmt->bind_param(
                        "sssiis", 
                        $tarih, 
                        $sablon['baslangic_saat'], 
                        $sablon['bitis_saat'],
                        $sablon['kontenjan'],
                        $kalan_kontenjan,
                        $sablon['id']
                    );
                    
                    if ($stmt->execute()) {
                        $eklenen++;
                        $debug[] = "    Randevu eklendi: $tarih " . $sablon['baslangic_saat'];
                    } else {
                        $debug[] = "    HATA: " . $stmt->error;
                    }
                }
            } else {
                $debug[] = "    Gün eşleşmedi: " . $sablon['gun'] . " != " . $gun_adi;
            }
            $tarih = date('Y-m-d', strtotime($tarih . ' +1 day'));
        }
    }

    // Transaction'ı onayla
    $db->commit();

    // Debug bilgilerini kontrol et
    error_log("Program Başlatma Debug:\n" . implode("\n", $debug));

    // Başarılı yanıt döndür
    $mesaj = $son_tarih ? 
        date('d.m.Y', strtotime($baslangic)) . ' - ' . date('d.m.Y', strtotime($bitis)) . ' tarihleri için yeni program oluşturuldu.' :
        'Bu haftanın programı oluşturuldu.';
    
    echo json_encode([
        'success' => true,
        'message' => $mesaj . ' (' . $eklenen . ' randevu eklendi)',
        'baslangic' => $baslangic,
        'bitis' => $bitis,
        'debug' => $debug // Debug bilgilerini yanıta ekle
    ]);

} catch (Exception $e) {
    // Hata durumunda transaction'ı geri al
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Program oluşturulurken bir hata oluştu: ' . $e->getMessage(),
        'debug' => $debug ?? []
    ]);
} 