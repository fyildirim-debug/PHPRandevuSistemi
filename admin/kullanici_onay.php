<?php
require_once '../config.php';
adminKontrol();

$sayfa = 'kullanici_onay';
$sayfa_basligi = 'Kullanıcı Onay';

// Onay/Red işlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kullanici_id'], $_POST['islem'])) {
    $kullanici_id = (int)$_POST['kullanici_id'];
    $islem = $_POST['islem'];
    $durum = ($islem == 'onayla') ? 'onayli' : 'reddedildi';
    
    $sql = "UPDATE kullanicilar SET durum = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $durum, $kullanici_id);
    
    if ($stmt->execute()) {
        $mesaj = ['tur' => 'success', 'metin' => 'Kullanıcı durumu güncellendi.'];
    } else {
        $mesaj = ['tur' => 'danger', 'metin' => 'İşlem sırasında hata oluştu.'];
    }
}

// Bekleyen kullanıcıları getir
$sql = "SELECT * FROM kullanicilar WHERE durum = 'beklemede' ORDER BY created_at DESC";
$bekleyen_kullanicilar = $db->query($sql);

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
                        <h1 class="h3 text-white mb-0">Kullanıcı Onay</h1>
                        <p class="text-gray mb-0">Bekleyen kullanıcı kayıtları</p>
                    </div>
                </div>

                <?php if (isset($mesaj)): ?>
                <div class="alert alert-<?php echo $mesaj['tur']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mesaj['metin']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-clock me-2"></i>
                        Onay Bekleyen Kullanıcılar
                    </div>
                    <div class="card-body">
                        <?php if ($bekleyen_kullanicilar->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>T.C. No</th>
                                            <th>Ad Soyad</th>
                                            <th>E-posta</th>
                                            <th>Telefon</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($kullanici = $bekleyen_kullanicilar->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo sifreCoz($kullanici['tc_no']); ?></td>
                                            <td><?php echo $kullanici['ad_soyad']; ?></td>
                                            <td><?php echo $kullanici['email']; ?></td>
                                            <td><?php echo $kullanici['telefon']; ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($kullanici['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="kullanici_id" value="<?php echo $kullanici['id']; ?>">
                                                    <button type="submit" name="islem" value="onayla" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i>Onayla
                                                    </button>
                                                    <button type="submit" name="islem" value="reddet" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times me-1"></i>Reddet
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-gray py-4">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p class="mb-0">Onay bekleyen kullanıcı bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div> 