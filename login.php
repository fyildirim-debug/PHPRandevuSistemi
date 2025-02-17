<?php
require_once 'config.php';

$sayfa_basligi = 'Giriş Yap';

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['kullanici_id'])) {
    if ($_SESSION['rol'] == 'admin') {
        header('Location: admin/');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

// Giriş kontrolü
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tc_no = trim($_POST['tc_no']); // TC No ile giriş
    $sifre = trim($_POST['sifre']);
    
    // Cloudflare Turnstile doğrulama
    $token = $_POST['cf-turnstile-response'];
    $verify_url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $verify_response = file_get_contents($verify_url, false, $context);
    $turnstile_success = json_decode($verify_response);
    
    if (!$turnstile_success->success) {
        $hata = "Lütfen doğrulamayı tamamlayın.";
    } else {
        // TC'yi şifrele ve ara
        $tc_sifrelenmis = sifrele($tc_no);
        
        $sql = "SELECT * FROM kullanicilar WHERE tc_no = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $tc_sifrelenmis);
        $stmt->execute();
        $kullanici = $stmt->get_result()->fetch_assoc();
        
        if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            if ($kullanici['durum'] == 'onayli' || $kullanici['rol'] == 'admin') {
                // Güvenli oturum başlat
                guvenliOturumBaslat($kullanici, $db);
                
                // Son giriş zamanını güncelle
                $update_sql = "UPDATE kullanicilar SET son_giris = CURRENT_TIMESTAMP WHERE id = ?";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->bind_param("i", $kullanici['id']);
                $update_stmt->execute();

                // Rolüne göre yönlendir
                if ($kullanici['rol'] == 'admin') {
                    header('Location: admin/');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $hata = "Hesabınız henüz onaylanmamış.";
            }
        } else {
            $hata = 'Geçersiz TC No veya şifre!';
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title text-center mb-0">Giriş Yap</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['kayit_basarili'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['kayit_basarili']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                        </div>
                        <?php unset($_SESSION['kayit_basarili']); ?>
                    <?php endif; ?>

                    <?php if (isset($hata)): ?>
                        <div class="alert alert-danger"><?php echo $hata; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="tc_no" class="form-label">TC Kimlik No</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-id-card"></i>
                                </span>
                                <input type="text" class="form-control" id="tc_no" name="tc_no" 
                                       required pattern="[0-9]{11}" maxlength="11">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="sifre" class="form-label">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="sifre" name="sifre" required>
                            </div>
                        </div>
                        <div class="mb-4 d-flex justify-content-center">
                            <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="dark"></div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </button>
                            <a href="kayit.php" class="btn btn-outline-light">
                                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 