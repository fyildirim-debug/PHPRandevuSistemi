<?php
require_once '../config.php';
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
date_default_timezone_set('Europe/Istanbul');
adminKontrol();

// Türkçe gün ve ay isimleri için yardımcı fonksiyonlar
function turkceGun($gun) {
    $gunler = [
        'Monday'    => 'Pazartesi',
        'Tuesday'   => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday'  => 'Perşembe',
        'Friday'    => 'Cuma',
        'Saturday'  => 'Cumartesi',
        'Sunday'    => 'Pazar'
    ];
    return $gunler[$gun];
}

function turkceAy($ay) {
    $aylar = [
        'January'   => 'Ocak',
        'February'  => 'Şubat',
        'March'     => 'Mart',
        'April'     => 'Nisan',
        'May'       => 'Mayıs',
        'June'      => 'Haziran',
        'July'      => 'Temmuz',
        'August'    => 'Ağustos',
        'September' => 'Eylül',
        'October'   => 'Ekim',
        'November'  => 'Kasım',
        'December'  => 'Aralık'
    ];
    return $aylar[$ay];
}

$sayfa = 'randevular';
$sayfa_basligi = 'Randevular';

// Hafta seçimi
// ISO hafta yılı için date('o') kullanılmalı, date('Y') değil
// Çünkü yıl sonu/başı geçişlerinde ISO hafta numarası farklı yıla ait olabilir
// Örn: 29 Aralık 2025 ISO haftası 2026'nın 1. haftasıdır
$current_week = isset($_GET['week']) ? (int)$_GET['week'] : (int)date('W');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('o');

// Haftanın başlangıç ve bitiş tarihlerini hesapla
$week_start = new DateTime();
$week_start->setISODate($current_year, $current_week);
$week_end = clone $week_start;
$week_end->modify('+6 days');

// Önceki ve sonraki hafta için yıl geçiş hesaplaması
$prev_week = $current_week - 1;
$prev_year = $current_year;
if ($prev_week < 1) {
    $prev_year--;
    // Önceki yılın son haftasını bul
    $prev_week = (int)date('W', strtotime("$prev_year-12-28"));
}

$next_week = $current_week + 1;
$next_year = $current_year;
// Mevcut yılın son haftasını kontrol et
$max_week = (int)date('W', strtotime("$current_year-12-28"));
if ($next_week > $max_week) {
    $next_year++;
    $next_week = 1;
}

// Randevuları getir
$sql = "SELECT rs.*, ps.gun, ps.kontenjan as sablon_kontenjan,
        (SELECT COUNT(*) FROM randevular r 
         WHERE r.tarih = rs.tarih 
         AND r.saat = rs.baslangic_saat 
         AND r.durum = 'onaylandi') as dolu_kontenjan,
        (SELECT GROUP_CONCAT(CONCAT(k.ad_soyad, ' (', k.telefon, ')') SEPARATOR '<br>')
         FROM randevular r 
         JOIN kullanicilar k ON r.kullanici_id = k.id
         WHERE r.tarih = rs.tarih 
         AND r.saat = rs.baslangic_saat 
         AND r.durum = 'onaylandi') as randevu_sahipleri
        FROM randevu_saatleri rs 
        LEFT JOIN program_sablon ps ON rs.sablon_id = ps.id 
        WHERE rs.aktif = 1 AND rs.tarih BETWEEN ? AND ?
        ORDER BY rs.tarih, rs.baslangic_saat";

$stmt = $db->prepare($sql);
$start_date = $week_start->format('Y-m-d');
$end_date = $week_end->format('Y-m-d');
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$randevular = $stmt->get_result();

// Randevuları günlere göre grupla
$haftalik_program = [];
while ($row = $randevular->fetch_assoc()) {
    $haftalik_program[$row['tarih']][] = $row;
}

include 'includes/header.php';
include 'includes/topbar.php';
?>

<style>
/* Schedule row yüksekliğini artır */
.schedule-row {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
    height: 40px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    position: relative;
}

.time-column {
    padding: 0.75rem 0.5rem !important;
    text-align: center;
    color: var(--gray);
    font-size: 0.85rem !important;
    background: rgba(255, 255, 255, 0.02);
    white-space: nowrap;
    overflow: hidden;
}

.schedule-slot {
    padding: 0.25rem;
    border-left: 1px solid rgba(255, 255, 255, 0.05);
    position: relative;
}

.program-slot {
    padding: 0.75rem;
    position: absolute;
    left: 0.25rem;
    right: 0.25rem;
    top: 0;
    z-index: 1;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    border-radius: 8px;
}

/* Randevu durumlarına göre stiller */
.program-slot.available {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.program-slot.reserved {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.slot-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.5rem;
}

.slot-info {
    line-height: 1.4;
}

.available-text {
    color: var(--success);
    font-weight: 500;
}

.reserved-text {
    color: var(--danger);
    font-weight: 500;
}

/* Tablo başlığı */
.schedule-header {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.day-column {
    padding: 1rem;
    text-align: center;
    border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.day-name {
    font-weight: 600;
    color: var(--light);
    margin-bottom: 0.25rem;
}

.date {
    font-size: 0.85rem;
    color: var(--gray);
}

/* Scroll bar stilleri */
.schedule-body {
    max-height: 600px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.schedule-body::-webkit-scrollbar {
    width: 6px;
}

.schedule-body::-webkit-scrollbar-track {
    background: transparent;
}

.schedule-body::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

/* Slot actions için stil */
.slot-actions {
    display: flex;
    gap: 0.25rem;
    justify-content: center;
    margin-top: 0.5rem;
}

.slot-actions button {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.program-slot {
    position: relative;
    overflow: visible !important;
}

.program-slot .slot-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.program-slot .slot-info {
    text-align: center;
    line-height: 1.2;
}
</style>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 text-white mb-0">Randevular</h1>
                        <p class="text-gray mb-0">Haftalık randevu programı</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-alt me-2"></i>
                            Haftalık Program
                        </div>
                        <div class="btn-group">
                            <a href="?week=<?php echo $prev_week; ?>&year=<?php echo $prev_year; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <span class="btn btn-sm btn-outline-primary">
                                <?php 
                                echo $current_week . '. Hafta (' . 
                                     date('d ', $week_start->getTimestamp()) . turkceAy(date('F', $week_start->getTimestamp())) . ' - ' . 
                                     date('d ', $week_end->getTimestamp()) . turkceAy(date('F', $week_end->getTimestamp())) . ')'; 
                                ?>
                            </span>
                            <a href="?week=<?php echo $next_week; ?>&year=<?php echo $next_year; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="weekly-schedule">
                            <div class="schedule-header">
                                <div class="time-column">Saat</div>
                                <?php
                                $current = clone $week_start;
                                for ($i = 0; $i < 7; $i++): 
                                    $date = $current->format('Y-m-d');
                                ?>
                                    <div class="day-column">
                                        <div class="day-name">
                                            <?php echo turkceGun($current->format('l')); ?>
                                        </div>
                                        <div class="date">
                                            <?php echo date('d ', $current->getTimestamp()) . 
                                                     turkceAy(date('F', $current->getTimestamp())); ?>
                                        </div>
                                    </div>
                                <?php 
                                    $current->modify('+1 day');
                                endfor; 
                                ?>
                            </div>
                            <div class="schedule-body">
                                <?php
                                $start_time = new DateTime('08:00');
                                $end_time = new DateTime('20:00');
                                $interval = new DateInterval('PT15M');

                                while ($start_time <= $end_time):
                                    $current_time = $start_time->format('H:i:00');
                                ?>
                                <div class="schedule-row">
                                    <div class="time-column"><?php echo substr($current_time, 0, 5); ?></div>
                                    <?php 
                                    $current = clone $week_start;
                                    for ($i = 0; $i < 7; $i++): 
                                        $date = $current->format('Y-m-d');
                                    ?>
                                    <div class="schedule-slot">
                                        <?php
                                        if (isset($haftalik_program[$date])) {
                                            foreach ($haftalik_program[$date] as $randevu) {
                                                if ($randevu['baslangic_saat'] == $current_time) {
                                                    $baslangic = strtotime($randevu['baslangic_saat']);
                                                    $bitis = strtotime($randevu['bitis_saat']);
                                                    $sure_farki = ($bitis - $baslangic) / 900;
                                                    $yukseklik = ($sure_farki * 40);
                                                    $durum_class = ($randevu['dolu_kontenjan'] >= $randevu['kontenjan'] ? 'reserved' : 'available');
                                                    
                                                    echo '<div class="program-slot ' . $durum_class . '" style="height: ' . $yukseklik . 'px;">';
                                                    echo '<div class="slot-content">';
                                                    echo '<div class="slot-info">';
                                                    echo '<strong>' . substr($randevu['baslangic_saat'], 0, 5) . ' - ' . substr($randevu['bitis_saat'], 0, 5) . '</strong>';
                                                    echo '<br>';
                                                    echo '<small>Kontenjan: ' . ($randevu['kontenjan'] - $randevu['dolu_kontenjan']) . '/' . $randevu['kontenjan'] . '</small>';
                                                    if ($randevu['randevu_sahipleri']) {
                                                        echo '<div class="mt-2">';
                                                        echo '<small class="text-muted">Randevu Sahipleri:</small><br>';
                                                        echo '<small>' . $randevu['randevu_sahipleri'] . '</small>';
                                                        echo '</div>';
                                                    }
                                                    echo '<div class="slot-actions mt-2">';
                                                    echo '<button class="btn btn-sm btn-warning" onclick="randevuDuzenle('.$randevu['id'].')"><i class="fas fa-edit"></i></button>';
                                                    echo '<button class="btn btn-sm btn-danger ms-1" onclick="randevuSil('.$randevu['id'].')"><i class="fas fa-trash"></i></button>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <?php
                                        $current->modify('+1 day');
                                    endfor;
                                    ?>
                                </div>
                                <?php
                                    $start_time->add($interval);
                                endwhile;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Randevu düzenleme modalı
    const randevuDuzenleModal = new bootstrap.Modal(document.getElementById('randevuDuzenleModal'));
    
    // Randevu düzenleme fonksiyonu
    window.randevuDuzenle = function(id) {
        fetch('ajax/randevu_getir.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tarihi yyyy-mm-dd formatına çevir
                const tarih = new Date(data.randevu.tarih);
                const formatliTarih = tarih.toISOString().split('T')[0];
                
                document.getElementById('duzenle_randevu_id').value = data.randevu.id;
                document.getElementById('duzenle_tarih').value = formatliTarih;
                document.getElementById('duzenle_baslangic_saat').value = data.randevu.baslangic_saat;
                document.getElementById('duzenle_bitis_saat').value = data.randevu.bitis_saat;
                document.getElementById('duzenle_kontenjan').value = data.randevu.kontenjan;
                randevuDuzenleModal.show();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu!');
        });
    };

    // Form submit işlemi
    document.getElementById('randevuDuzenleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = new URLSearchParams();
        for (const pair of formData) {
            data.append(pair[0], pair[1]);
        }

        fetch('ajax/randevu_guncelle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                randevuDuzenleModal.hide();
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu!');
        });
    });

    // Randevu silme fonksiyonu
    window.randevuSil = function(id) {
        if (confirm('Bu randevuyu silmek istediğinize emin misiniz?')) {
            fetch('ajax/randevu_sil.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Bir hata oluştu!');
            });
        }
    };
});
</script>

<!-- Randevu Düzenleme Modal -->
<div class="modal fade" id="randevuDuzenleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Randevu Düzenle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="randevuDuzenleForm">
                <input type="hidden" name="randevu_id" id="duzenle_randevu_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" name="tarih" id="duzenle_tarih" class="form-control bg-dark text-light border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Saati</label>
                        <select name="baslangic_saat" id="duzenle_baslangic_saat" class="form-select bg-dark text-light border-secondary" required>
                            <?php
                            $start = new DateTime('08:00');
                            $end = new DateTime('20:00');
                            $interval = new DateInterval('PT15M');
                            while ($start <= $end) {
                                $time = $start->format('H:i');
                                echo '<option value="'.$time.':00">'.$time.'</option>';
                                $start->add($interval);
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Saati</label>
                        <select name="bitis_saat" id="duzenle_bitis_saat" class="form-select bg-dark text-light border-secondary" required>
                            <?php
                            $start = new DateTime('08:00');
                            $end = new DateTime('20:00');
                            while ($start <= $end) {
                                $time = $start->format('H:i');
                                echo '<option value="'.$time.':00">'.$time.'</option>';
                                $start->add($interval);
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kontenjan</label>
                        <input type="number" name="kontenjan" id="duzenle_kontenjan" class="form-control bg-dark text-light border-secondary" min="1" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div> 