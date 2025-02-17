<?php
require_once '../config.php';
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
setlocale(LC_ALL, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Istanbul');
adminKontrol();

$sayfa = 'program';
$sayfa_basligi = 'Program Şablonu';

// Günleri tanımla
$gunler = ['pazartesi', 'sali', 'carsamba', 'persembe', 'cuma', 'cumartesi', 'pazar'];
$gunler_tr = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

// Türkçe gün isimleri için yardımcı fonksiyon
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

// Program şablonu kaydetme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['program_kaydet'])) {
    $gun = trim($_POST['gun']);
    $baslangic_saat = date('H:i:00', strtotime($_POST['baslangic_saat']));
    $bitis_saat = date('H:i:00', strtotime($_POST['bitis_saat']));
    $kontenjan = (int)$_POST['kontenjan'];
    
    // Debug için
    error_log("Eklenen program: Gün=$gun, Başlangıç=$baslangic_saat, Bitiş=$bitis_saat");
    
    // SQL sorgusunu güncelle ve aktif alanını ekle
    $sql = "INSERT INTO program_sablon (gun, baslangic_saat, bitis_saat, kontenjan, aktif) 
            VALUES (?, ?, ?, ?, 1)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssi", $gun, $baslangic_saat, $bitis_saat, $kontenjan);
    
    if ($stmt->execute()) {
        $mesaj = ['tur' => 'success', 'metin' => 'Program şablonu başarıyla eklendi.'];
        // Debug için
        error_log("Program başarıyla eklendi. ID: " . $stmt->insert_id);
    } else {
        $mesaj = ['tur' => 'danger', 'metin' => 'Program eklenirken hata oluştu: ' . $db->error];
        error_log("Program eklenirken hata: " . $db->error);
    }
}

// Mevcut şablonları getir (Debug ekleyelim)
$sql = "SELECT * FROM program_sablon WHERE aktif = 1 
        ORDER BY FIELD(gun, 'pazartesi', 'sali', 'carsamba', 'persembe', 'cuma', 'cumartesi', 'pazar'), 
        baslangic_saat";
$program = $db->query($sql);

// Debug için verileri kontrol et
error_log("Program sorgusu: " . $sql);
error_log("Bulunan program sayısı: " . $program->num_rows);

// Şablonları günlere göre grupla
$program_sablon = [];
while ($row = $program->fetch_assoc()) {
    $program_sablon[$row['gun']][] = $row;
    // Debug için
    error_log("Program yüklendi: Gün={$row['gun']}, Saat={$row['baslangic_saat']}-{$row['bitis_saat']}");
}

include 'includes/header.php';
include 'includes/topbar.php';
?>

<style>
/* Mevcut stiller... */

.info-card {
    max-width: 400px;
    border: 1px solid rgba(13, 202, 240, 0.2);
}

.info-card ul {
    list-style: none;
    padding-left: 1.5rem;
}

.info-card ul li {
    position: relative;
}

.info-card ul li:before {
    content: "•";
    position: absolute;
    left: -1rem;
    color: var(--info);
}

/* Program tablosu düzenlemeleri */
.program-table th {
    background: rgba(255, 255, 255, 0.05);
    font-weight: 600;
    padding: 1rem;
}

.program-table td {
    padding: 1rem;
    vertical-align: middle;
}

.program-table tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

/* Info box stili */
.info-box {
    border: 1px solid rgba(13, 202, 240, 0.2);
}

.info-box h6 {
    color: var(--gray);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-box #hedefHafta {
    font-size: 1.1rem;
    font-weight: 500;
}

/* Popover özelleştirmesi */
.popover {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.popover-body {
    color: var(--light);
    padding: 1rem;
}

.bs-popover-start .popover-arrow::before {
    border-left-color: rgba(15, 23, 42, 0.95);
}

/* Schedule row yüksekliğini düzenle */
.schedule-row {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
    height: 40px; /* Yüksekliği azalt */
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    position: relative;
}

.time-column {
    padding: 0.25rem !important;
    text-align: center;
    color: var(--gray);
    font-size: 0.85rem !important;
    background: rgba(255, 255, 255, 0.02);
    white-space: nowrap;
    overflow: hidden;
    line-height: 30px; /* Dikey hizalama için */
}

.schedule-slot {
    padding: 0.25rem;
    border-left: 1px solid rgba(255, 255, 255, 0.05);
    position: relative;
    min-height: 40px; /* Minimum yükseklik */
}

.program-slot {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 8px;
    padding: 0.35rem;
    position: absolute;
    left: 0.25rem;
    right: 0.25rem;
    top: 0;
    z-index: 1;
    transition: all 0.3s ease;
    font-size: 0.8rem;
}

/* Slot içeriği için stil */
.slot-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center; /* Dikey ortalama */
    gap: 0.25rem;
}

.slot-info {
    line-height: 1.2;
}

/* Scroll bar stilleri */
.schedule-body {
    max-height: 600px; /* Yüksekliği azalt */
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

/* Schedule header düzenlemeleri */
.schedule-header {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.day-column {
    padding: 0.75rem 0.5rem; /* Padding'i azalt */
    text-align: center;
    border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.day-name {
    font-size: 0.85rem; /* Font boyutunu küçült */
    font-weight: 600;
    color: var(--light);
    margin-bottom: 0.25rem;
}

.date {
    font-size: 0.75rem; /* Font boyutunu küçült */
    color: var(--gray);
}

/* Card padding düzenlemesi */
.card-body {
    padding: 0 !important;
}

.card-header {
    padding: 1rem 1.25rem; /* Padding'i azalt */
}

/* Footer düzenlemesi */
.card-footer {
    padding: 1rem 1.25rem; /* Padding'i azalt */
    background: transparent;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}
</style>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 text-white mb-0">Program Şablonu</h1>
                        <p class="text-gray mb-0">Haftalık program şablonunu oluşturun</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#programEkleModal">
                        <i class="fas fa-plus me-2"></i>Yeni Saat Ekle
                    </button>
                </div>

                <?php if (isset($mesaj)): ?>
                <div class="alert alert-<?php echo $mesaj['tur']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mesaj['metin']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Haftalık Program Şablonu
                    </div>
                    <div class="card-body p-0">
                        <div class="weekly-schedule">
                            <div class="schedule-header">
                                <div class="time-column">Saat</div>
                                <?php foreach ($gunler_tr as $gun): ?>
                                    <div class="day-column">
                                        <div class="day-name"><?php echo $gun; ?></div>
                                    </div>
                                <?php endforeach; ?>
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
                                    <?php foreach ($gunler as $gun): ?>
                                    <div class="schedule-slot">
                                        <?php
                                        if (isset($program_sablon[$gun])) {
                                            foreach ($program_sablon[$gun] as $slot) {
                                                if ($slot['baslangic_saat'] == $current_time) {
                                                    $baslangic = strtotime($slot['baslangic_saat']);
                                                    $bitis = strtotime($slot['bitis_saat']);
                                                    $sure_farki = ($bitis - $baslangic) / 900;
                                                    $yukseklik = ($sure_farki * 40); // Her 15 dakika için 40px

                                                    echo '<div class="program-slot" style="height: ' . $yukseklik . 'px;">';
                                                    echo '<div class="slot-content">';
                                                    echo '<div class="slot-info">';
                                                    echo date('H:i', $baslangic) . ' - ' . date('H:i', $bitis);
                                                    echo '<br>';
                                                    echo 'Kontenjan: ' . $slot['kontenjan'];
                                                    echo '</div>';
                                                    echo '<div class="slot-actions">';
                                                    echo '<button class="btn btn-sm btn-danger ms-1" onclick="programSil('.$slot['id'].')">
                                                          <i class="fas fa-trash"></i></button>';
                                                    echo '</div>';
                                                    echo '</div>'; // slot-content end
                                                    echo '</div>'; // program-slot end
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                    $start_time->add($interval);
                                endwhile;
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-4">
                                <button type="button" class="btn btn-success" onclick="programiBaslat()">
                                    <i class="fas fa-calendar-plus me-2"></i>Şablonu Programa Dönüştür
                                </button>
                                
                                <div class="text-gray small">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span class="me-2">•</span> Şablon belirtilen haftaya uygulanır
                                    <span class="me-2">•</span> O hafta doluysa sonraki haftaya geçer
                                    <span class="me-2">•</span> Mevcut programları <a href="randevular.php" class="text-info">Randevular</a> sayfasından düzenleyebilirsiniz
                                </div>
                            </div>
                            
                            <div class="info-box bg-dark bg-opacity-50 rounded p-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-week text-info me-3 fa-2x"></i>
                                    <div>
                                        <h6 class="mb-1">Hedef Program Haftası:</h6>
                                        <div id="hedefHafta" class="text-info">
                                            <?php
                                            // Son programlı haftayı kontrol et
                                            $check_sql = "SELECT MAX(tarih) as son_tarih FROM randevu_saatleri WHERE aktif = 1";
                                            $result = $db->query($check_sql);
                                            $row = $result->fetch_assoc();
                                            $son_tarih = $row['son_tarih'];
                                            
                                            if ($son_tarih) {
                                                $hedef_tarih = date('d.m.Y', strtotime('next monday', strtotime($son_tarih)));
                                                echo $hedef_tarih . ' haftası';
                                            } else {
                                                $hedef_tarih = date('d.m.Y', strtotime('monday this week'));
                                                echo $hedef_tarih . ' haftası (Bu hafta)';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Program Ekleme Modal -->
        <div class="modal fade" id="programEkleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">Yeni Program Ekle</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="program_kaydet" value="1">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Gün</label>
                                <select name="gun" class="form-select bg-dark text-light border-secondary" required>
                                    <?php foreach ($gunler as $i => $gun): ?>
                                        <option value="<?php echo $gun; ?>"><?php echo $gunler_tr[$i]; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Başlangıç Saati</label>
                                <select name="baslangic_saat" class="form-select bg-dark text-light border-secondary" required>
                                    <?php
                                    $start = new DateTime('08:00');
                                    $end = new DateTime('20:00');
                                    $interval = new DateInterval('PT15M');
                                    while ($start <= $end) {
                                        $time = $start->format('H:i');
                                        $minutes = (int)$start->format('i');
                                        // Sadece 00, 15, 30, 45 dakikaları göster
                                        if ($minutes == 0 || $minutes == 15 || $minutes == 30 || $minutes == 45) {
                                            echo '<option value="'.$time.'">'.$time.'</option>';
                                        }
                                        $start->add($interval);
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bitiş Saati</label>
                                <select name="bitis_saat" class="form-select bg-dark text-light border-secondary" required>
                                    <?php
                                    $start = new DateTime('08:00');
                                    $end = new DateTime('20:00');
                                    $interval = new DateInterval('PT15M');
                                    while ($start <= $end) {
                                        $time = $start->format('H:i');
                                        $minutes = (int)$start->format('i');
                                        // Sadece 00, 15, 30, 45 dakikaları göster
                                        if ($minutes == 0 || $minutes == 15 || $minutes == 30 || $minutes == 45) {
                                            echo '<option value="'.$time.'">'.$time.'</option>';
                                        }
                                        $start->add($interval);
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kontenjan</label>
                                <input type="number" name="kontenjan" class="form-control bg-dark text-light border-secondary" min="1" value="1" required>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="program_kaydet" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable initialization
    $('#programListTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        },
        order: [[0, 'asc'], [1, 'asc']],
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });

    // Görünüm değiştirme butonları için click eventi
    $('.btn-group .btn').on('click', function() {
        const view = $(this).data('view');
        
        // Butonların aktiflik durumunu değiştir
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
        
        // Tüm görünümleri gizle
        $('.view-content').hide().removeClass('fade-in');
        
        // Seçilen görünümü göster
        if (view === 'weekly') {
            $('#weeklyView').show().addClass('fade-in');
        } else {
            $('#listView').show().addClass('fade-in');
        }
    });

    // Sayfa yüklendiğinde haftalık görünümü aktif et
    $('#weeklyView').show().addClass('fade-in');
    $('#listView').hide();
    $('.btn-group .btn[data-view="weekly"]').addClass('active');
});

// Program düzenleme fonksiyonu
function programDuzenle(id) {
    // AJAX ile program bilgilerini getir
    $.ajax({
        url: 'ajax/program_getir.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            // Modal içeriğini doldur ve göster
            // Bu kısmı daha sonra implement edeceğiz
        }
    });
}

// Program silme fonksiyonu
function programSil(id) {
    if (confirm('Bu programı silmek istediğinize emin misiniz?')) {
        $.ajax({
            url: 'ajax/program_sil.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                // Başarılı silme durumunda sayfayı yenile
                location.reload();
            }
        });
    }
}

function programiBaslat() {
    if (confirm('Haftalık programı başlatmak istediğinize emin misiniz?')) {
        $.ajax({
            url: 'ajax/program_baslat.php',
            type: 'POST',
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            }
        });
    }
}
</script> 