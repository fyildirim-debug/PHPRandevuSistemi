<?php
require_once '../config.php';
adminKontrol();
guvenlikKontrol($db);

$sayfa = 'panel';
$sayfa_basligi = 'Kontrol Paneli';

// Admin bilgilerini getir
$admin_sql = "SELECT *, DATE_FORMAT(son_giris, '%d.%m.%Y %H:%i') as son_giris_formated 
              FROM kullanicilar WHERE id = ? AND rol = 'admin'";
$stmt = $db->prepare($admin_sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// TC'yi çöz
if ($admin) {
    $admin['tc_no'] = sifreCoz($admin['tc_no']);
}

// İstatistikleri getir
$hafta_baslangic = date('Y-m-d', strtotime('monday this week'));
$hafta_bitis = date('Y-m-d', strtotime('sunday this week'));

$istatistikler = [
    'toplam_randevu' => $db->query("SELECT COUNT(*) as sayi FROM randevu_saatleri 
        WHERE aktif = 1 
        AND tarih BETWEEN '$hafta_baslangic' AND '$hafta_bitis'")->fetch_assoc()['sayi'],
        
    'bugun_randevu' => $db->query("SELECT COUNT(*) as sayi FROM randevu_saatleri 
        WHERE aktif = 1 
        AND tarih = CURDATE()")->fetch_assoc()['sayi'],
        
    'dolu_randevu' => $db->query("SELECT COUNT(*) as sayi FROM randevu_saatleri 
        WHERE aktif = 1 
        AND kullanici_id IS NOT NULL 
        AND tarih BETWEEN '$hafta_baslangic' AND '$hafta_bitis'")->fetch_assoc()['sayi'],
        
    'bekleyen_kullanici' => $db->query("SELECT COUNT(*) as sayi FROM kullanicilar 
        WHERE durum = 'beklemede'")->fetch_assoc()['sayi']
];

// Bu haftanın randevu istatistiklerini getir
$hafta_baslangic = date('Y-m-d', strtotime('monday this week'));
$hafta_bitis = date('Y-m-d', strtotime('sunday this week'));

$haftalik_istatistik_sql = "
    SELECT 
        DATE_FORMAT(rs.tarih, '%W') as gun,
        COUNT(*) as toplam_slot,
        SUM(CASE WHEN rs.kullanici_id IS NOT NULL THEN 1 ELSE 0 END) as dolu_slot,
        SUM(rs.kontenjan) as toplam_kontenjan,
        SUM(rs.kalan_kontenjan) as kalan_kontenjan
    FROM randevu_saatleri rs
    WHERE rs.aktif = 1 
    AND rs.tarih BETWEEN ? AND ?
    GROUP BY rs.tarih
    ORDER BY DAYOFWEEK(rs.tarih)";

$stmt = $db->prepare($haftalik_istatistik_sql);
$stmt->bind_param("ss", $hafta_baslangic, $hafta_bitis);
$stmt->execute();
$haftalik_istatistik = $stmt->get_result();

include 'includes/header.php';
include 'includes/topbar.php';
?>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 text-white mb-0">Kontrol Paneli</h1>
                        <p class="text-gray mb-0">Sistem durumu ve istatistikler</p>
                    </div>
                </div>

                <!-- Admin Bilgi Kartı -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar bg-primary rounded-circle">
                                    <i class="fas fa-user-shield fa-2x text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1"><?php echo $admin['ad_soyad']; ?></h5>
                                <p class="text-gray mb-0">
                                    <i class="fas fa-envelope me-2"></i><?php echo $admin['email']; ?>
                                </p>
                                <small class="text-gray">
                                    <i class="fas fa-clock me-2"></i>Son giriş: <?php echo $admin['son_giris_formated']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row">
                    <!-- Toplam Randevu -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-gray mb-1">Bu Haftaki Açık Randevu</div>
                                        <div class="h3 mb-0 text-primary"><?php echo $istatistikler['toplam_randevu']; ?></div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bugünkü Randevu -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-gray mb-1">Bugünkü Açık Randevu</div>
                                        <div class="h3 mb-0 text-success"><?php echo $istatistikler['bugun_randevu']; ?></div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dolu Randevu -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-gray mb-1">Bu Haftaki Dolu Randevu</div>
                                        <div class="h3 mb-0 text-warning"><?php echo $istatistikler['dolu_randevu']; ?></div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bekleyen Kullanıcı -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-gray mb-1">Bekleyen Kullanıcı</div>
                                        <div class="h3 mb-0 text-danger"><?php echo $istatistikler['bekleyen_kullanici']; ?></div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Haftalık İstatistik Grafiği -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-chart-line me-2"></i>
                            Haftalık Randevu Grafiği
                        </div>
                        <div class="text-gray">
                            <?php echo date('d.m.Y', strtotime($hafta_baslangic)) . ' - ' . date('d.m.Y', strtotime($hafta_bitis)); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="randevuGrafik" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<!-- Chart.js kütüphanesini ekle -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gunler = {
        'Monday': 'Pazartesi',
        'Tuesday': 'Salı',
        'Wednesday': 'Çarşamba',
        'Thursday': 'Perşembe',
        'Friday': 'Cuma',
        'Saturday': 'Cumartesi',
        'Sunday': 'Pazar'
    };
    
    const labels = [];
    const randevuSayilari = [];
    const dolulukOranlari = [];
    
    <?php
    $haftalik_istatistik->data_seek(0);
    while ($gun = $haftalik_istatistik->fetch_assoc()) {
        echo "labels.push('" . $gunler[$gun['gun']] . "');\n";
        echo "randevuSayilari.push(" . $gun['dolu_slot'] . ");\n";
        $doluluk = $gun['toplam_kontenjan'] > 0 ? 
            round((($gun['toplam_kontenjan'] - $gun['kalan_kontenjan']) / $gun['toplam_kontenjan']) * 100) : 0;
        echo "dolulukOranlari.push(" . $doluluk . ");\n";
    }
    ?>
    
    const ctx = document.getElementById('randevuGrafik').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Randevu Sayısı',
                    data: randevuSayilari,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Doluluk Oranı (%)',
                    data: dolulukOranlari,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Randevu Sayısı',
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Doluluk Oranı (%)',
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: 'rgba(255, 255, 255, 0.9)',
                    bodyColor: 'rgba(255, 255, 255, 0.7)',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 10,
                    bodySpacing: 5,
                    titleSpacing: 10
                }
            }
        }
    });
});
</script> 