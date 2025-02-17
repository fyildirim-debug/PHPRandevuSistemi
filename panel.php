<?php
require_once 'config.php';
kullaniciKontrol();
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
date_default_timezone_set('Europe/Istanbul');

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
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark text-light mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-xl position-relative">
                                <i class="fas fa-user-circle fa-4x"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="h-100">
                                <h5 class="mb-1"><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></h5>
                                <p class="mb-0 font-weight-normal text-sm">
                                    <?php echo htmlspecialchars($kullanici['email']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card bg-dark text-light">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Toplam Randevu</p>
                                <h5 class="font-weight-bolder mb-0">
                                    <?php echo $kullanici['toplam_randevu']; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="fas fa-calendar-check text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card bg-dark text-light">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Haftalık Hak</p>
                                <h5 class="font-weight-bolder mb-0">
                                    <?php echo $haftalik['haftalik_randevu'] . ' / ' . $kullanici['haftalik_limit']; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                <i class="fas fa-clock text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card bg-dark text-light">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Kayıt Tarihi</p>
                                <h5 class="font-weight-bolder mb-0">
                                    <?php echo date('d.m.Y', strtotime($kullanici['kayit_tarihi'])); ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                                <i class="fas fa-user-plus text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card bg-dark text-light">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Gelecek Randevu</p>
                                <h5 class="font-weight-bolder mb-0">
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
                            <div class="icon icon-shape bg-gradient-danger shadow text-center border-radius-md">
                                <i class="fas fa-calendar-alt text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 