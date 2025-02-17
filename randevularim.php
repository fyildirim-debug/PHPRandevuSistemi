<?php
require_once 'config.php';
require_once 'includes/functions.php';
kullaniciKontrol();

$sayfa = 'randevularim';
$sayfa_basligi = 'Randevularım';

// Gelecek randevuları getir
$gelecek_sql = "SELECT r.*, DATE_FORMAT(r.tarih, '%d.%m.%Y') as tarih_format,
                rs.baslangic_saat, rs.bitis_saat, rs.kontenjan,
                DATE_FORMAT(r.created_at, '%d.%m.%Y %H:%i') as randevu_alma_zamani,
                (SELECT COUNT(*) FROM randevular WHERE tarih = r.tarih AND saat = r.saat AND durum = 'onaylandi') as dolu_kontenjan
                FROM randevular r
                JOIN randevu_saatleri rs ON r.tarih = rs.tarih AND r.saat = rs.baslangic_saat
                WHERE r.kullanici_id = ? 
                AND r.tarih >= CURDATE()
                AND r.durum = 'onaylandi'
                ORDER BY r.tarih ASC, r.saat ASC";

$stmt = $db->prepare($gelecek_sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$gelecek_randevular = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Geçmiş randevuları getir
$gecmis_sql = "SELECT r.*, DATE_FORMAT(r.tarih, '%d.%m.%Y') as tarih_format,
               rs.baslangic_saat, rs.bitis_saat, rs.kontenjan,
               DATE_FORMAT(r.created_at, '%d.%m.%Y %H:%i') as randevu_alma_zamani,
               (SELECT COUNT(*) FROM randevular WHERE tarih = r.tarih AND saat = r.saat AND durum = 'onaylandi') as dolu_kontenjan
               FROM randevular r
               JOIN randevu_saatleri rs ON r.tarih = rs.tarih AND r.saat = rs.baslangic_saat
               WHERE r.kullanici_id = ? 
               AND (r.tarih < CURDATE() OR (r.tarih = CURDATE() AND r.saat < TIME(NOW())))
               AND r.durum = 'onaylandi'
               ORDER BY r.tarih DESC, r.saat DESC
               LIMIT 10";

$stmt = $db->prepare($gecmis_sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$gecmis_randevular = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
include 'includes/topbar.php';
?>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 text-white mb-0">Randevularım</h1>
                        <p class="text-gray mb-0">Gelecek ve geçmiş randevularınızı görüntüleyebilirsiniz</p>
                    </div>
                </div>

                <style>
                .randevu-card {
                    border-radius: 8px;
                    margin-bottom: 1rem;
                    transition: all 0.3s ease;
                }

                .randevu-card.gelecek {
                    background: rgba(59, 130, 246, 0.1);
                    border: 1px solid rgba(59, 130, 246, 0.2);
                }

                .randevu-card.gecmis {
                    background: rgba(107, 114, 128, 0.1);
                    border: 1px solid rgba(107, 114, 128, 0.2);
                }

                .randevu-info {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    margin-bottom: 0.5rem;
                }

                .randevu-info i {
                    width: 20px;
                    text-align: center;
                    color: var(--gray);
                }

                .badge-outline {
                    background: transparent;
                    border: 1px solid currentColor;
                }

                .badge-outline.badge-success {
                    color: var(--success);
                }

                .badge-outline.badge-warning {
                    color: var(--warning);
                }

                .badge-outline.badge-info {
                    color: var(--info);
                }
                </style>

                <!-- Gelecek Randevular -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Gelecek Randevular
                    </div>
                    <div class="card-body">
                        <?php if (empty($gelecek_randevular)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p class="mb-0">Gelecek randevunuz bulunmamaktadır.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($gelecek_randevular as $randevu): 
                                $randevu_zamani = new DateTime($randevu['tarih'] . ' ' . $randevu['baslangic_saat']);
                                $simdiki_zaman = new DateTime();
                                $zaman_farki = $randevu_zamani->getTimestamp() - $simdiki_zaman->getTimestamp();
                                $iptal_edilebilir = $zaman_farki > 7200; // 2 saat = 7200 saniye
                            ?>
                                <div class="randevu-card gelecek p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?php echo $randevu['tarih_format']; ?></h5>
                                            <div class="randevu-info">
                                                <i class="far fa-clock"></i>
                                                <span><?php echo substr($randevu['baslangic_saat'], 0, 5) . ' - ' . substr($randevu['bitis_saat'], 0, 5); ?></span>
                                            </div>
                                            <div class="randevu-info">
                                                <i class="far fa-calendar-check"></i>
                                                <span>Randevu alınma: <?php echo $randevu['randevu_alma_zamani']; ?></span>
                                            </div>
                                            <div class="randevu-info">
                                                <i class="fas fa-users"></i>
                                                <span>Kontenjan durumu: <?php echo $randevu['dolu_kontenjan'] . '/' . $randevu['kontenjan']; ?></span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($iptal_edilebilir): ?>
                                                <span class="badge badge-outline badge-success mb-2">İptal Edilebilir</span><br>
                                                <button class="btn btn-sm btn-danger" onclick="randevuIptal(<?php echo $randevu['id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>İptal Et
                                                </button>
                                            <?php else: ?>
                                                <span class="badge badge-outline badge-warning">İptal Edilemez</span><br>
                                                <small class="text-muted">Randevuya 2 saatten az kaldı</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Geçmiş Randevular -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-1"></i>
                        Geçmiş Randevular
                    </div>
                    <div class="card-body">
                        <?php if (empty($gecmis_randevular)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history fa-3x mb-3"></i>
                                <p class="mb-0">Geçmiş randevunuz bulunmamaktadır.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($gecmis_randevular as $randevu): ?>
                                <div class="randevu-card gecmis p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?php echo $randevu['tarih_format']; ?></h5>
                                            <div class="randevu-info">
                                                <i class="far fa-clock"></i>
                                                <span><?php echo substr($randevu['baslangic_saat'], 0, 5) . ' - ' . substr($randevu['bitis_saat'], 0, 5); ?></span>
                                            </div>
                                            <div class="randevu-info">
                                                <i class="far fa-calendar-check"></i>
                                                <span>Randevu alınma: <?php echo $randevu['randevu_alma_zamani']; ?></span>
                                            </div>
                                            <div class="randevu-info">
                                                <i class="fas fa-users"></i>
                                                <span>Kontenjan durumu: <?php echo $randevu['dolu_kontenjan'] . '/' . $randevu['kontenjan']; ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge badge-outline badge-info">Tamamlandı</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
function randevuIptal(id) {
    if (confirm('Bu randevuyu iptal etmek istediğinize emin misiniz?')) {
        $.ajax({
            url: 'ajax/randevu_iptal.php',
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