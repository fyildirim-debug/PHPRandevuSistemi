<?php
require_once 'config.php';
require_once 'includes/functions.php';
kullaniciKontrol();

$sayfa = 'profil';
$sayfa_basligi = 'Profil Bilgileri';

// Kullanıcı bilgilerini getir
$sql = "SELECT * FROM kullanicilar WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $_SESSION['kullanici_id']);
$stmt->execute();
$kullanici = $stmt->get_result()->fetch_assoc();

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mesaj = [];
    
    // Email değişikliği
    if (isset($_POST['email']) && $_POST['email'] != $kullanici['email']) {
        $yeni_email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if ($yeni_email) {
            // Email kullanımda mı kontrol et
            $kontrol = $db->prepare("SELECT id FROM kullanicilar WHERE email = ? AND id != ?");
            $kontrol->bind_param("si", $yeni_email, $_SESSION['kullanici_id']);
            $kontrol->execute();
            if ($kontrol->get_result()->num_rows == 0) {
                $sql = "UPDATE kullanicilar SET email = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("si", $yeni_email, $_SESSION['kullanici_id']);
                if ($stmt->execute()) {
                    $mesaj['success'][] = 'E-posta adresi güncellendi.';
                }
            } else {
                $mesaj['error'][] = 'Bu e-posta adresi kullanılıyor.';
            }
        } else {
            $mesaj['error'][] = 'Geçersiz e-posta adresi.';
        }
    }
    
    // Telefon değişikliği
    if (isset($_POST['telefon']) && $_POST['telefon'] != $kullanici['telefon']) {
        $yeni_telefon = preg_replace('/[^0-9]/', '', $_POST['telefon']);
        if (strlen($yeni_telefon) == 10) {
            $sql = "UPDATE kullanicilar SET telefon = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $yeni_telefon, $_SESSION['kullanici_id']);
            if ($stmt->execute()) {
                $mesaj['success'][] = 'Telefon numarası güncellendi.';
            }
        } else {
            $mesaj['error'][] = 'Geçersiz telefon numarası.';
        }
    }
    
    // Şifre değişikliği
    if (!empty($_POST['mevcut_sifre']) && !empty($_POST['yeni_sifre'])) {
        if (password_verify($_POST['mevcut_sifre'], $kullanici['sifre'])) {
            if (strlen($_POST['yeni_sifre']) >= 6) {
                $yeni_sifre_hash = password_hash($_POST['yeni_sifre'], PASSWORD_DEFAULT);
                $sql = "UPDATE kullanicilar SET sifre = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("si", $yeni_sifre_hash, $_SESSION['kullanici_id']);
                if ($stmt->execute()) {
                    $mesaj['success'][] = 'Şifre başarıyla güncellendi.';
                }
            } else {
                $mesaj['error'][] = 'Yeni şifre en az 6 karakter olmalıdır.';
            }
        } else {
            $mesaj['error'][] = 'Mevcut şifre hatalı.';
        }
    }
    
    // Kullanıcı bilgilerini yeniden getir
    $stmt->execute();
    $kullanici = $stmt->get_result()->fetch_assoc();
}

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
                        <h1 class="h3 text-white mb-0">Profil Bilgileri</h1>
                        <p class="text-gray mb-0">Kişisel bilgilerinizi güncelleyebilirsiniz</p>
                    </div>
                </div>

                <?php if (!empty($mesaj)): ?>
                    <?php if (!empty($mesaj['success'])): ?>
                        <?php foreach ($mesaj['success'] as $basari): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $basari; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($mesaj['error'])): ?>
                        <?php foreach ($mesaj['error'] as $hata): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $hata; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <!-- Profil Kartı -->
                        <div class="profile-card">
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
                    </div>

                    <div class="col-lg-8">
                        <!-- Bilgi Güncelleme Formu -->
                        <div class="form-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-edit me-2"></i>
                                    Bilgileri Güncelle
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="post" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Ad Soyad</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" disabled>
                                            <small class="text-muted">Ad soyad bilgisi değiştirilemez</small>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">E-posta</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($kullanici['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Telefon</label>
                                            <input type="tel" name="telefon" class="form-control" value="<?php echo htmlspecialchars($kullanici['telefon']); ?>" required>
                                            <small class="text-muted">Örnek: 5XX XXX XX XX</small>
                                        </div>
                                    </div>

                                    <div class="divider"></div>

                                    <h6 class="mb-4">Şifre Değiştir</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Mevcut Şifre</label>
                                            <input type="password" name="mevcut_sifre" class="form-control">
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Yeni Şifre</label>
                                            <input type="password" name="yeni_sifre" class="form-control">
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-save">
                                            <i class="fas fa-save me-2"></i>
                                            Değişiklikleri Kaydet
                                        </button>
                                    </div>
                                </form>
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
// Telefon formatı
document.querySelector('input[name="telefon"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) value = value.slice(0, 10);
    if (value.length >= 3) value = value.slice(0, 3) + ' ' + value.slice(3);
    if (value.length >= 7) value = value.slice(0, 7) + ' ' + value.slice(7);
    if (value.length >= 9) value = value.slice(0, 9) + ' ' + value.slice(9);
    e.target.value = value;
});
</script> 