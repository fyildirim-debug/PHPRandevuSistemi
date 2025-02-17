<?php
require_once 'config.php';
require_once 'includes/functions.php';
kullaniciKontrol();
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
date_default_timezone_set('Europe/Istanbul');

$sayfa = 'panel';
$sayfa_basligi = 'Kontrol Paneli';

// Kullanıcı bilgilerini al
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM randevu_saatleri WHERE kullanici_id = u.id AND aktif = 1) as toplam_randevu,
        (SELECT MIN(tarih) FROM randevu_saatleri WHERE kullanici_id = u.id AND tarih >= CURDATE() AND aktif = 1) as gelecek_randevu,
        (SELECT haftalik_randevu_limiti FROM ayarlar LIMIT 1) as haftalik_limit
        FROM kullanicilar u 
        WHERE u.id = ?";
        
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$kullanici = $stmt->get_result()->fetch_assoc();

// Bu hafta alınan randevu sayısını hesapla
$sql = "SELECT COUNT(*) as haftalik_randevu FROM randevu_saatleri 
        WHERE kullanici_id = ? 
        AND tarih BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
        AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
        AND aktif = 1";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$haftalik = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';
include 'includes/topbar.php';
?>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <!-- Profil Kartı -->
                <div class="profile-card mb-4">
                    <div class="profile-header">
                        <div class="avatar-circle">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4 class="text-white mb-1"><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></h4>
                        <p class="text-light mb-2 opacity-75"><?php echo htmlspecialchars($kullanici['email']); ?></p>
                        <div class="profile-status">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $kullanici['durum']; ?>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row">
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="numbers">
                                            <p class="text-sm mb-0 text-uppercase font-weight-bold text-muted">Toplam Randevu</p>
                                            <h5 class="font-weight-bolder text-light mb-0">
                                                <?php echo $kullanici['toplam_randevu']; ?>
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                            <i class="fas fa-calendar-check text-lg opacity-10"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="numbers">
                                            <p class="text-sm mb-0 text-uppercase font-weight-bold text-muted">Haftalık Hak</p>
                                            <h5 class="font-weight-bolder text-light mb-0">
                                                <?php echo $haftalik['haftalik_randevu'] . ' / ' . $kullanici['haftalik_limit']; ?>
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                            <i class="fas fa-clock text-lg opacity-10"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="numbers">
                                            <p class="text-sm mb-0 text-uppercase font-weight-bold text-muted">Kayıt Tarihi</p>
                                            <h5 class="font-weight-bolder text-light mb-0">
                                                <?php echo date('d.m.Y', strtotime($kullanici['created_at'])); ?>
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                            <i class="fas fa-user-plus text-lg opacity-10"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="numbers">
                                            <p class="text-sm mb-0 text-uppercase font-weight-bold text-muted">Gelecek Randevu</p>
                                            <h5 class="font-weight-bolder text-light mb-0">
                                                <?php 
                                                if ($kullanici['gelecek_randevu']) {
                                                    echo date('d.m.Y', strtotime($kullanici['gelecek_randevu']));
                                                } else {
                                                    echo 'Randevu Yok';
                                                }
                                                ?>
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                                            <i class="fas fa-calendar-alt text-lg opacity-10"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-body p-4">
                                <h5 class="mb-4"><i class="fas fa-bolt me-2"></i>Hızlı İşlemler</h5>
                                <div class="d-grid gap-3">
                                    <a href="randevular.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>Yeni Randevu Al
                                    </a>
                                    <a href="randevularim.php" class="btn btn-info">
                                        <i class="fas fa-calendar-check me-2"></i>Randevularımı Görüntüle
                                    </a>
                                    <a href="profil.php" class="btn btn-warning">
                                        <i class="fas fa-user-edit me-2"></i>Profili Düzenle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div> 