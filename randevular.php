<?php
require_once 'config.php';
require_once 'includes/functions.php';
kullaniciKontrol();

$sayfa = 'randevular';
$sayfa_basligi = 'Randevu Al';

// Hafta ve yıl parametrelerini al
// ISO hafta yılı için date('o') kullanılmalı, date('Y') değil
// Çünkü yıl sonu/başı geçişlerinde ISO hafta numarası farklı yıla ait olabilir
// Örn: 29 Aralık 2025 ISO haftası 2026'nın 1. haftasıdır
$current_week = isset($_GET['week']) ? (int)$_GET['week'] : (int)date('W');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('o');

// Haftanın başlangıç ve bitiş tarihlerini hesapla
$week_start = new DateTime();
$week_start_date = $week_start->setISODate($current_year, $current_week);
$week_start_str = $week_start_date->format('Y-m-d');

$week_end = clone $week_start;
$week_end->modify('+6 days');
$week_end_str = $week_end->format('Y-m-d');

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

// Haftalık limit kontrolü
$haftalik_limit_sql = "SELECT haftalik_randevu_limiti FROM ayarlar LIMIT 1";
$haftalik_limit = $db->query($haftalik_limit_sql)->fetch_assoc()['haftalik_randevu_limiti'];

$haftalik_randevu_sql = "SELECT COUNT(*) as sayi FROM randevular 
                         WHERE kullanici_id = ? 
                         AND tarih BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
                         AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
                         AND durum = 'onaylandi'";

$stmt = $db->prepare($haftalik_randevu_sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$haftalik_randevu = $stmt->get_result()->fetch_assoc()['sayi'];
$haftalik_limit_doldu = ($haftalik_randevu >= $haftalik_limit);

// Haftalık programı getir
$sql = "SELECT rs.*, 
        (SELECT COUNT(*) FROM randevular r 
         WHERE r.tarih = rs.tarih 
         AND r.saat = rs.baslangic_saat 
         AND r.durum = 'onaylandi') as dolu_kontenjan,
        (SELECT COUNT(*) FROM randevular r 
         WHERE r.tarih = rs.tarih 
         AND r.saat = rs.baslangic_saat 
         AND r.kullanici_id = ? 
         AND r.durum = 'onaylandi') as kullanici_randevusu
        FROM randevu_saatleri rs 
        WHERE rs.tarih BETWEEN ? AND ?
        AND rs.aktif = 1
        AND rs.tarih >= CURDATE()
        ORDER BY rs.tarih, rs.baslangic_saat";

$stmt = $db->prepare($sql);
$stmt->bind_param("iss", $_SESSION['kullanici_id'], $week_start_str, $week_end_str);
$stmt->execute();
$result = $stmt->get_result();

$haftalik_program = [];
while ($row = $result->fetch_assoc()) {
    $haftalik_program[$row['tarih']][] = $row;
}

include 'includes/header.php';
include 'includes/topbar.php';
?>

<style>
.schedule-container {
    overflow-x: auto;
    margin-top: 1rem;
}

.schedule-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
}

.schedule-table th {
    background: rgba(255, 255, 255, 0.05);
    padding: 1rem;
    text-align: center;
    font-weight: 500;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 10;
}

.schedule-table td {
    padding: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    vertical-align: top;
    height: 60px;
    position: relative;
}

.time-cell {
    width: 80px;
    text-align: center;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.02);
    padding: 0.5rem !important;
    position: sticky;
    left: 0;
    z-index: 5;
}

.slot-cell {
    position: relative;
    min-width: 150px;
}

.randevu-slot {
    position: absolute;
    left: 0;
    right: 0;
    padding: 0.5rem;
    margin: 2px;
    border-radius: 8px;
    transition: all 0.3s ease;
    z-index: 1;
}

.randevu-slot.long-slot {
    min-height: 120px; /* 2 saat için */
}

.randevu-slot.available {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.randevu-slot.reserved {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.randevu-slot.my-appointment {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.my-appointment .slot-status {
    color: var(--primary);
}

.slot-time {
    font-weight: 500;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.slot-status {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.available .slot-status {
    color: var(--success);
}

.reserved .slot-status {
    color: var(--danger);
}

.slot-actions {
    margin-top: 0.5rem;
}

/* Takvim başlığı stilleri */
.day-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.day-date {
    font-size: 0.875rem;
    color: var(--gray);
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .slot-cell {
        min-width: 120px;
    }
    
    .time-cell {
        width: 60px;
    }
    
    .slot-time {
        font-size: 0.8rem;
    }
}
</style>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 text-white mb-0">Randevu Al</h1>
                        <p class="text-gray mb-0">Uygun randevu saatlerini görüntüleyip randevu alabilirsiniz</p>
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
                    <div class="schedule-container">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Saat</th>
                                    <?php
                                    $current = clone $week_start;
                                    for ($i = 0; $i < 7; $i++): 
                                    ?>
                                    <th>
                                        <div class="day-name"><?php echo turkceGun($current->format('l')); ?></div>
                                        <div class="day-date">
                                            <?php echo $current->format('d') . ' ' . turkceAy($current->format('F')); ?>
                                        </div>
                                    </th>
                                    <?php
                                        $current->modify('+1 day');
                                    endfor;
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $start_time = new DateTime('08:00');
                                $end_time = new DateTime('20:00');
                                $interval = new DateInterval('PT15M');

                                while ($start_time <= $end_time):
                                    $current_time = $start_time->format('H:i:00');
                                ?>
                                <tr>
                                    <td class="time-cell"><?php echo $start_time->format('H:i'); ?></td>
                                    <?php
                                    $current = clone $week_start;
                                    for ($i = 0; $i < 7; $i++):
                                        $date = $current->format('Y-m-d');
                                    ?>
                                    <td class="slot-cell">
                                        <?php
                                        if (isset($haftalik_program[$date])) {
                                            foreach ($haftalik_program[$date] as $randevu) {
                                                if ($randevu['baslangic_saat'] == $current_time) {
                                                    // Süre hesaplama
                                                    $baslangic = new DateTime($randevu['baslangic_saat']);
                                                    $bitis = new DateTime($randevu['bitis_saat']);
                                                    $sure_farki = $bitis->diff($baslangic);
                                                    $slot_height = (int)($sure_farki->h * 60 + $sure_farki->i) / 15 * 60; // 15 dakika = 60px
                                                    
                                                    // Geçmiş saat kontrolü
                                                    $randevu_zamani = new DateTime($randevu['tarih'] . ' ' . $randevu['baslangic_saat']);
                                                    $simdiki_zaman = new DateTime();
                                                    $gecmis_saat = false;
                                                    
                                                    if ($randevu_zamani <= $simdiki_zaman) {
                                                        $gecmis_saat = true;
                                                    }
                                                    
                                                    $durum_class = 'available';
                                                    $durum_text = 'Müsait';
                                                    
                                                    if ($gecmis_saat) {
                                                        $durum_class = 'reserved';
                                                        $durum_text = 'Geçmiş Saat';
                                                    } elseif ($randevu['kullanici_randevusu'] > 0) {
                                                        $durum_class = 'my-appointment';
                                                        $durum_text = 'Randevunuz Var';
                                                    } elseif ($randevu['dolu_kontenjan'] >= $randevu['kontenjan']) {
                                                        $durum_class = 'reserved';
                                                        $durum_text = 'Dolu';
                                                    }

                                                    // Başlangıç ve bitiş saatlerini düzgün formatlama
                                                    $baslangic = substr($randevu['baslangic_saat'], 0, 5);
                                                    $bitis = substr($randevu['bitis_saat'], 0, 5);
                                                    ?>
                                                    <div class="randevu-slot <?php echo $durum_class; ?>" style="height: <?php echo $slot_height; ?>px;">
                                                        <div class="slot-time">
                                                            <?php echo $baslangic . ' - ' . $bitis; ?>
                                                        </div>
                                                        <div class="slot-status"><?php echo $durum_text; ?></div>
                                                        <?php if ($randevu['dolu_kontenjan'] < $randevu['kontenjan'] && $randevu['kullanici_randevusu'] == 0 && !$gecmis_saat): ?>
                                                            <small>Kontenjan: <?php echo ($randevu['kontenjan'] - $randevu['dolu_kontenjan']) . '/' . $randevu['kontenjan']; ?></small>
                                                            <?php if ($randevu['kalan_kontenjan'] > 0 && !$haftalik_limit_doldu): ?>
                                                                <div class="slot-actions">
                                                                    <button class="btn btn-sm btn-success" onclick="randevuAl(<?php echo $randevu['id']; ?>)">
                                                                        <i class="fas fa-check me-1"></i>Randevu Al
                                                                    </button>
                                                                </div>
                                                            <?php elseif ($haftalik_limit_doldu): ?>
                                                                <div class="mt-2">
                                                                    <small class="text-warning">Haftalık randevu limitiniz dolmuştur</small>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php elseif ($randevu['kullanici_randevusu'] > 0): ?>
                                                            <div class="mt-2">
                                                                <small class="text-success">Randevunuz Onaylandı</small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                    <?php
                                        $current->modify('+1 day');
                                    endfor;
                                    ?>
                                </tr>
                                <?php
                                    $start_time->add($interval);
                                endwhile;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
function randevuAl(id) {
    if (confirm('Bu randevuyu almak istediğinize emin misiniz?')) {
        $.ajax({
            url: 'ajax/randevu_al.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            }
        });
    }
}
</script> 